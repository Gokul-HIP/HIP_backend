<?php

namespace App\Modules\Workflow\Executors\Actions;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\Services\Runtime\TemplateManager;
use App\Modules\Workflow\Services\Runtime\VariableResolver;
use App\Services\OpenRouterService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Frontend catalog node: sendAiChat.
 *
 * Starts an AI chat interaction for the configured recipient:
 * validate config → resolve prompt/template variables → OpenRouter chat completions
 * → deliver generated chat content via ChannelManager (outbound) OR return text only (chatbot).
 *
 * Distinct from ai/aiPrompt (general AI summary). Not an alias.
 * Uses OPENROUTER_* config via OpenRouterService — does not require WORKFLOW_AI_ENDPOINT.
 */
class SendAiChatExecutor extends AbstractMessagingExecutor
{
    /** @var list<string> */
    private const SUPPORTED_DELIVERY_CHANNELS = ['whatsapp', 'sms', 'email', 'push'];

    /** @var list<string> */
    private const SUPPORTED_RECIPIENTS = ['patient', 'doctor', 'caregiver', 'custom'];

    public function __construct(
        ActionDispatcher $actionDispatcher,
        ChannelManager $channelManager,
        TemplateManager $templateManager,
        VariableResolver $variableResolver,
        protected OpenRouterService $openRouter,
    ) {
        parent::__construct($actionDispatcher, $channelManager, $templateManager, $variableResolver);
    }

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

        $execContext = is_array($execution->context) ? $execution->context : [];
        $payloadContext = is_array($context->payload) ? $context->payload : [];
        $meta = array_merge(
            is_array($execContext['meta'] ?? null) ? $execContext['meta'] : [],
            is_array($payloadContext['meta'] ?? null) ? $payloadContext['meta'] : []
        );
        $responseOnly = $this->isResponseOnlyMode($meta);

        $templateChannel = strtolower(trim((string) ($template->channel ?? '')));
        $deliveryChannel = $this->normalizeDeliveryChannel($templateChannel);

        if (! $responseOnly) {
            if ($deliveryChannel === null) {
                return NodeExecutionResult::failed(
                    "sendAiChat template {$templateId} must use channel whatsapp, sms, email, or push"
                );
            }
        } else {
            if ($templateChannel === '') {
                return NodeExecutionResult::failed(
                    "sendAiChat chatbot template {$templateId} requires a channel (ai, whatsapp, sms, email, or push)"
                );
            }
            if ($deliveryChannel === null && $templateChannel !== 'ai') {
                return NodeExecutionResult::failed(
                    "sendAiChat chatbot template {$templateId} must use channel ai, whatsapp, sms, email, or push"
                );
            }
        }

        $templateBody = trim((string) ($template->body ?? ''));
        if ($templateBody === '') {
            return NodeExecutionResult::failed(
                "sendAiChat template {$templateId} has an empty body"
            );
        }

        $payload = array_merge(
            $execContext,
            $payloadContext,
            ['variables' => $context->variables]
        );
        $payload['meta'] = $meta;

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

        $channelForOutput = $deliveryChannel ?? $templateChannel;

        Log::info('sendAiChat invoking OpenRouter', [
            'workflow_id' => $execution->workflow_id,
            'execution_id' => $execution->id,
            'node_id' => $node->id,
            'node_type' => 'sendAiChat',
            'template_id' => $templateId,
            'delivery_channel' => $channelForOutput,
            'response_only' => $responseOnly,
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
                'template_channel' => $templateChannel,
            ]);
        }

        return NodeExecutionResult::continue([
            'text' => $message,
            'type' => 'ai_chat',
            'node_id' => $node->id,
            'template_id' => $templateId,
            'channel' => $channelForOutput,
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
     * Call OpenRouter chat completions using OPENROUTER_* configuration.
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
        $apiKey = trim((string) config('services.openrouter.api_key'));
        if ($apiKey === '') {
            return [
                'message' => null,
                'error' => 'sendAiChat OpenRouter is not configured (services.openrouter.api_key / OPENROUTER_API_KEY)',
            ];
        }

        $messages = $this->buildOpenRouterMessages($prompt, $templateContext, $execution);

        try {
            $result = $this->openRouter->chatCompletions($messages, [
                'temperature' => $temperature,
                'stream' => false,
            ]);
        } catch (RuntimeException $e) {
            Log::warning('sendAiChat OpenRouter provider failed', [
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
        } catch (\Throwable $e) {
            Log::warning('sendAiChat OpenRouter provider failed', [
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

        $message = trim((string) ($result['content'] ?? ''));
        if ($message === '') {
            return [
                'message' => null,
                'error' => 'sendAiChat AI provider returned an invalid/empty response',
            ];
        }

        return ['message' => $message, 'error' => null];
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    protected function buildOpenRouterMessages(
        string $prompt,
        string $templateContext,
        WorkflowExecution $execution,
    ): array {
        $systemParts = array_values(array_filter([
            $templateContext !== '' ? $templateContext : null,
            $prompt !== '' ? $prompt : null,
        ]));

        $messages = [];
        if ($systemParts !== []) {
            $messages[] = [
                'role' => 'system',
                'content' => implode("\n\n", $systemParts),
            ];
        }

        $execContext = is_array($execution->context) ? $execution->context : [];
        $history = $execContext['messages'] ?? null;

        if (is_array($history) && $history !== []) {
            foreach ($history as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $role = strtolower(trim((string) ($row['role'] ?? '')));
                $content = trim((string) ($row['content'] ?? ''));
                if ($content === '' || ! in_array($role, ['user', 'assistant', 'system'], true)) {
                    continue;
                }
                if ($role === 'system') {
                    continue;
                }
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }

        $hasUser = false;
        foreach ($messages as $row) {
            if (($row['role'] ?? null) === 'user') {
                $hasUser = true;
                break;
            }
        }

        if (! $hasUser) {
            $userMessage = trim((string) ($execContext['chat_message'] ?? $execContext['user_message'] ?? ''));
            if ($userMessage === '') {
                $userMessage = $prompt;
            }
            if ($userMessage !== '') {
                $messages[] = ['role' => 'user', 'content' => $userMessage];
            }
        }

        return $messages;
    }
}
