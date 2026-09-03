<?php

namespace App\Modules\Workflow\Executors\Actions;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Frontend catalog node: sendAiChat.
 *
 * Starts an AI chat interaction for the configured recipient:
 * validate config → resolve prompt/template variables → call workflow AI endpoint
 * → deliver generated chat content via ChannelManager.
 *
 * Distinct from ai/aiPrompt (general AI summary). Not an alias.
 */
class SendAiChatExecutor extends AbstractMessagingExecutor
{
    /** @var list<string> */
    private const SUPPORTED_DELIVERY_CHANNELS = ['whatsapp', 'sms', 'email', 'push'];

    /** @var list<string> */
    private const SUPPORTED_RECIPIENTS = ['patient', 'doctor', 'caregiver', 'custom'];

    public function type(): string
    {
        return 'sendAiChat';
    }

    /**
     * Channel is taken from the selected WorkflowMessageTemplate at runtime.
     */
    protected function channel(): string
    {
        return 'push';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $data = is_array($node->data) ? $node->data : [];

        $recipient = trim((string) ($data['recipient'] ?? ''));
        if ($recipient === '') {
            return NodeExecutionResult::failed('sendAiChat requires a recipient');
        }

        $logical = strtolower($recipient);
        if (
            in_array($logical, self::SUPPORTED_RECIPIENTS, true) === false
            && ! $this->looksLikeConcreteRecipient($recipient)
        ) {
            // Allow concrete custom contacts; reject unknown logical roles.
            return NodeExecutionResult::failed(
                'sendAiChat recipient must be patient, doctor, caregiver, custom, or a concrete contact'
            );
        }

        $templateId = $this->resolveTemplateId($data);
        if ($templateId === null) {
            return NodeExecutionResult::failed('sendAiChat requires a valid templateId');
        }

        $promptRaw = trim((string) ($data['prompt'] ?? ''));
        if ($promptRaw === '') {
            return NodeExecutionResult::failed('sendAiChat requires a non-empty prompt');
        }

        $temperatureResult = $this->resolveTemperature($data);
        if ($temperatureResult['error'] !== null) {
            return NodeExecutionResult::failed($temperatureResult['error']);
        }
        $temperature = $temperatureResult['value'];

        $template = WorkflowMessageTemplate::query()
            ->where('id', $templateId)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return NodeExecutionResult::failed(
                "sendAiChat template not found or inactive: {$templateId}"
            );
        }

        $deliveryChannel = $this->normalizeDeliveryChannel((string) ($template->channel ?? ''));
        if ($deliveryChannel === null) {
            return NodeExecutionResult::failed(
                "sendAiChat template {$templateId} must use channel whatsapp, sms, email, or push"
            );
        }

        $templateBody = trim((string) ($template->body ?? ''));
        if ($templateBody === '') {
            return NodeExecutionResult::failed(
                "sendAiChat template {$templateId} has an empty body"
            );
        }

        $payload = array_merge(
            is_array($execution->context) ? $execution->context : [],
            is_array($context->payload) ? $context->payload : [],
            ['variables' => $context->variables]
        );

        $prompt = trim($this->variableResolver->resolve($promptRaw, $payload));
        if ($prompt === '') {
            return NodeExecutionResult::failed(
                'sendAiChat prompt resolved to empty text; refusing to call AI'
            );
        }

        $resolvedTemplate = trim($this->variableResolver->resolve($templateBody, $payload));
        if ($resolvedTemplate === '') {
            return NodeExecutionResult::failed(
                'sendAiChat template resolved to empty text; refusing to send'
            );
        }

        Log::info('sendAiChat invoking AI provider', [
            'workflow_id' => $execution->workflow_id,
            'execution_id' => $execution->id,
            'node_id' => $node->id,
            'node_type' => 'sendAiChat',
            'template_id' => $templateId,
            'delivery_channel' => $deliveryChannel,
            'temperature' => $temperature,
        ]);

        $aiMessage = $this->generateAiChatMessage(
            prompt: $prompt,
            templateContext: $resolvedTemplate,
            temperature: $temperature,
            execution: $execution,
            nodeId: $node->id,
        );

        if ($aiMessage['error'] !== null) {
            return NodeExecutionResult::failed($aiMessage['error']);
        }

        $message = trim((string) $aiMessage['message']);
        if ($message === '') {
            return NodeExecutionResult::failed(
                'sendAiChat AI response was empty; refusing to send'
            );
        }

        $context->setVariable('ai_chat_message', $message);
        $context->setVariable('ai_chat_prompt', $prompt);
        $context->setVariable('ai_chat_temperature', $temperature);
        $context->setVariable('ai_chat_node_id', $node->id);

        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $responseOnly = $this->isResponseOnlyMode($meta);

        // Chatbot HTTP callers need the AI text back without outbound SMS/email/push.
        // Outbound hospital-automation workflows keep ChannelManager delivery unchanged.
        if (! $responseOnly) {
            $sendPayload = $payload;
            $sendPayload['meta'] = array_merge($meta, [
                'node_type' => 'sendAiChat',
                'template_id' => $templateId,
            ]);

            if (! empty($data['repeatReminder'])) {
                $sendPayload['retry'] = true;
                $sendPayload['max_retries'] = max(1, (int) ($data['maxRetryCount'] ?? 2));
                $fallback = trim((string) ($data['fallbackChannel'] ?? ''));
                if ($fallback !== '') {
                    $sendPayload['fallback_channel'] = $fallback;
                }
            }

            $title = $this->variableResolver->resolve(
                (string) ($data['label'] ?? $data['subject'] ?? 'AI Chat'),
                $payload
            );

            $result = $this->channelManager->send(
                channel: $deliveryChannel,
                execution: $execution,
                nodeId: $node->id,
                message: $message,
                context: $sendPayload,
                subject: $title,
                recipient: $recipient,
            );

            if (! ($result['success'] ?? false)) {
                return NodeExecutionResult::failed($result['response'] ?? 'Channel delivery failed');
            }
        } else {
            Log::info('sendAiChat response-only mode (skipping ChannelManager)', [
                'workflow_id' => $execution->workflow_id,
                'execution_id' => $execution->id,
                'node_id' => $node->id,
                'node_type' => 'sendAiChat',
                'source' => $meta['source'] ?? null,
            ]);
        }

        return NodeExecutionResult::continue([
            'text' => $message,
            'type' => 'ai_chat',
            'node_id' => $node->id,
            'template_id' => $templateId,
            'channel' => $deliveryChannel,
            'response_only' => $responseOnly,
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function isResponseOnlyMode(array $meta): bool
    {
        if (($meta['response_mode'] ?? null) === true || ($meta['response_mode'] ?? null) === 'response') {
            return true;
        }

        return ($meta['source'] ?? null) === 'chatbot';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{value: float, error: string|null}
     */
    protected function resolveTemperature(array $data): array
    {
        if (! array_key_exists('temperature', $data) || $data['temperature'] === null || $data['temperature'] === '') {
            return ['value' => 0.7, 'error' => null];
        }

        if (! is_numeric($data['temperature'])) {
            return ['value' => 0.7, 'error' => 'sendAiChat temperature must be a number between 0 and 2'];
        }

        $value = (float) $data['temperature'];
        if ($value < 0.0 || $value > 2.0) {
            return ['value' => $value, 'error' => 'sendAiChat temperature must be between 0 and 2'];
        }

        return ['value' => $value, 'error' => null];
    }

    protected function normalizeDeliveryChannel(string $channel): ?string
    {
        $raw = strtolower(trim($channel));
        $normalized = match ($raw) {
            'sendwhatsapp' => 'whatsapp',
            'sendsms', 'send_sms' => 'sms',
            'sendemail' => 'email',
            'sendpush' => 'push',
            default => $raw,
        };

        return in_array($normalized, self::SUPPORTED_DELIVERY_CHANNELS, true) ? $normalized : null;
    }

    protected function looksLikeConcreteRecipient(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
            return true;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return is_string($digits) && strlen($digits) >= 7;
    }

    /**
     * Reuses the same workflow AI endpoint as AiPromptExecutor, with strict failure
     * (no stub summary) and temperature support for sendAiChat.
     *
     * @return array{message: string|null, error: string|null}
     */
    protected function generateAiChatMessage(
        string $prompt,
        string $templateContext,
        float $temperature,
        WorkflowExecution $execution,
        string $nodeId,
    ): array {
        $endpoint = config('services.workflow_ai.endpoint');

        if (! is_string($endpoint) || trim($endpoint) === '') {
            return [
                'message' => null,
                'error' => 'sendAiChat AI endpoint is not configured (services.workflow_ai.endpoint)',
            ];
        }

        $requestBody = [
            'prompt' => $prompt,
            'template' => $templateContext,
            'type' => 'ai_chat',
            'temperature' => $temperature,
        ];

        $execContext = is_array($execution->context) ? $execution->context : [];
        $userMessage = trim((string) ($execContext['chat_message'] ?? $execContext['user_message'] ?? ''));
        if ($userMessage !== '') {
            $requestBody['user_message'] = $userMessage;
        }
        if (is_array($execContext['messages'] ?? null)) {
            $requestBody['messages'] = $execContext['messages'];
        }

        try {
            $response = Http::timeout(30)->post($endpoint, $requestBody);
        } catch (\Throwable $e) {
            Log::warning('sendAiChat AI provider failed', [
                'workflow_id' => $execution->workflow_id,
                'execution_id' => $execution->id,
                'node_id' => $nodeId,
                'node_type' => 'sendAiChat',
                'error' => $e->getMessage(),
            ]);

            return [
                'message' => null,
                'error' => 'sendAiChat AI provider request failed: '.$e->getMessage(),
            ];
        }

        if (! $response->successful()) {
            return [
                'message' => null,
                'error' => 'sendAiChat AI provider returned HTTP '.$response->status(),
            ];
        }

        $raw = $response->json('message')
            ?? $response->json('summary')
            ?? $response->json('content');

        if (is_string($raw)) {
            $message = trim($raw);
            if ($message === '') {
                return [
                    'message' => null,
                    'error' => 'sendAiChat AI provider returned an invalid/empty response',
                ];
            }

            return ['message' => $message, 'error' => null];
        }

        $body = trim((string) $response->body());
        if ($body === '' || $body === '[]' || $body === '{}') {
            return [
                'message' => null,
                'error' => 'sendAiChat AI provider returned an invalid/empty response',
            ];
        }

        return ['message' => $body, 'error' => null];
    }
}
