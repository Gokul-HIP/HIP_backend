<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Frontend catalog node: sendAiVoice.
 *
 * Places an AI voice call:
 * validate → resolve template OR prompt → VariableResolver → ChannelManager(ai_voice)
 * → AiVoiceCallService (WORKFLOW_AI_VOICE_ENDPOINT).
 *
 * Distinct from sendAiChat / sendIvr / aiPrompt. Not an alias.
 */
class SendAiVoiceNodeProcessor extends AbstractMessagingNodeProcessor
{
    /** @var list<string> */
    private const SUPPORTED_RECIPIENTS = ['patient', 'doctor', 'caregiver', 'custom'];

    /** @var list<string> */
    private const SUPPORTED_VOICE_PROVIDERS = ['default', 'openai', 'azure', 'custom'];

    /** @var list<string> */
    private const SUPPORTED_LANGUAGES = ['en', 'hi', 'ta', 'te', 'ar'];

    /** @var list<string> */
    private const SUPPORTED_GENDERS = ['neutral', 'female', 'male'];

    public function type(): string
    {
        return 'sendAiVoice';
    }

    protected function channel(): string
    {
        return 'ai_voice';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $data = is_array($node->data) ? $node->data : [];

        $recipient = trim((string) ($data['recipient'] ?? ''));
        if ($recipient === '') {
            return NodeExecutionResult::failed('sendAiVoice requires a recipient');
        }

        $logical = strtolower($recipient);
        if (
            in_array($logical, self::SUPPORTED_RECIPIENTS, true) === false
            && ! $this->looksLikeConcreteRecipient($recipient)
        ) {
            return NodeExecutionResult::failed(
                'sendAiVoice recipient must be patient, doctor, caregiver, custom, or a concrete phone number'
            );
        }

        if ($logical === 'custom') {
            $customRaw = trim((string) ($data['customRecipient'] ?? $data['custom_recipient'] ?? ''));
            if ($customRaw === '') {
                return NodeExecutionResult::failed(
                    'sendAiVoice recipient=custom requires a non-empty customRecipient'
                );
            }
        }

        $voiceProvider = strtolower(trim((string) ($data['voiceProvider'] ?? $data['voice_provider'] ?? '')));
        if ($voiceProvider === '' || ! in_array($voiceProvider, self::SUPPORTED_VOICE_PROVIDERS, true)) {
            return NodeExecutionResult::failed(
                'sendAiVoice requires voiceProvider (default, openai, azure, custom)'
            );
        }

        $language = strtolower(trim((string) ($data['language'] ?? 'en')));
        if ($language === '') {
            $language = 'en';
        }
        if (! in_array($language, self::SUPPORTED_LANGUAGES, true)) {
            return NodeExecutionResult::failed(
                'sendAiVoice language must be one of: '.implode(', ', self::SUPPORTED_LANGUAGES)
            );
        }

        $gender = strtolower(trim((string) ($data['gender'] ?? 'neutral')));
        if ($gender === '') {
            $gender = 'neutral';
        }
        if (! in_array($gender, self::SUPPORTED_GENDERS, true)) {
            return NodeExecutionResult::failed(
                'sendAiVoice gender must be neutral, female, or male'
            );
        }

        $retryCount = $this->resolveRetryCount($data);
        if ($retryCount['error'] !== null) {
            return NodeExecutionResult::failed($retryCount['error']);
        }

        $scriptResult = $this->resolveSpokenScript($data, $execution, $context);
        if ($scriptResult['error'] !== null) {
            return NodeExecutionResult::failed($scriptResult['error']);
        }

        $script = $scriptResult['script'];
        $templateId = $scriptResult['template_id'];

        $payload = array_merge(
            is_array($execution->context) ? $execution->context : [],
            is_array($context->payload) ? $context->payload : [],
            ['variables' => $context->variables]
        );

        $recipientForSend = $recipient;
        if ($logical === 'custom') {
            $recipientForSend = trim($this->variableResolver->resolve(
                (string) ($data['customRecipient'] ?? $data['custom_recipient'] ?? ''),
                $payload
            ));
            if ($recipientForSend === '') {
                return NodeExecutionResult::failed(
                    'sendAiVoice customRecipient resolved to empty; refusing to place call'
                );
            }
        }

        $voiceName = trim((string) ($data['voice'] ?? ''));

        $sendPayload = $payload;
        $sendPayload['meta'] = array_merge(
            is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            [
                'node_type' => 'sendAiVoice',
                'template_id' => $templateId,
                'voice_provider' => $voiceProvider,
                'language' => $language,
                'voice' => $voiceName,
                'gender' => $gender,
                'retry_count' => $retryCount['value'],
            ]
        );

        // Optional ChannelManager retry/fallback from shared messaging defaults.
        if (! empty($data['repeatReminder'])) {
            $sendPayload['retry'] = true;
            $sendPayload['max_retries'] = max(1, (int) ($data['maxRetryCount'] ?? 2));
            $fallback = trim((string) ($data['fallbackChannel'] ?? ''));
            if ($fallback !== '') {
                $sendPayload['fallback_channel'] = $fallback;
            }
        }

        Log::info('sendAiVoice placing AI voice call', [
            'workflow_id' => $execution->workflow_id,
            'execution_id' => $execution->id,
            'node_id' => $node->id,
            'node_type' => 'sendAiVoice',
            'voice_provider' => $voiceProvider,
            'language' => $language,
            'template_id' => $templateId,
        ]);

        $title = $this->variableResolver->resolve(
            (string) ($data['label'] ?? 'AI Voice Call'),
            $payload
        );

        $result = $this->channelManager->send(
            channel: 'ai_voice',
            execution: $execution,
            nodeId: $node->id,
            message: $script,
            context: $sendPayload,
            subject: $title,
            recipient: $recipientForSend,
        );

        if (! ($result['success'] ?? false)) {
            return NodeExecutionResult::failed($result['response'] ?? 'AI voice call failed');
        }

        $context->setVariable('ai_voice_script', $script);
        $context->setVariable('ai_voice_provider', $voiceProvider);
        $context->setVariable('ai_voice_language', $language);
        if (! empty($result['call_id'])) {
            $context->setVariable('ai_voice_call_id', (string) $result['call_id']);
        }

        return NodeExecutionResult::continue([
            'type' => 'ai_voice',
            'node_id' => $node->id,
            'voice_provider' => $voiceProvider,
            'language' => $language,
            'template_id' => $templateId,
            'call_id' => $result['call_id'] ?? null,
        ]);
    }

    /**
     * Frontend contract: template OR prompt (at least one required).
     *
     * @param  array<string, mixed>  $data
     * @return array{script: string, template_id: int|null, error: string|null}
     */
    protected function resolveSpokenScript(
        array $data,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): array {
        $payload = array_merge(
            is_array($execution->context) ? $execution->context : [],
            is_array($context->payload) ? $context->payload : [],
            ['variables' => $context->variables]
        );

        $templateId = $this->resolveTemplateId($data);
        $promptRaw = trim((string) ($data['prompt'] ?? ''));

        if ($templateId === null && $promptRaw === '') {
            return [
                'script' => '',
                'template_id' => null,
                'error' => 'sendAiVoice requires a templateId or a non-empty prompt',
            ];
        }

        if ($templateId !== null) {
            $template = WorkflowMessageTemplate::query()
                ->where('id', $templateId)
                ->where('is_active', true)
                ->first();

            if (! $template) {
                return [
                    'script' => '',
                    'template_id' => $templateId,
                    'error' => "sendAiVoice template not found or inactive: {$templateId}",
                ];
            }

            $body = trim((string) ($template->body ?? ''));
            if ($body === '') {
                return [
                    'script' => '',
                    'template_id' => $templateId,
                    'error' => "sendAiVoice template {$templateId} has an empty body",
                ];
            }

            $script = trim($this->variableResolver->resolve($body, $payload));
            if ($script === '') {
                return [
                    'script' => '',
                    'template_id' => $templateId,
                    'error' => 'sendAiVoice template resolved to empty text; refusing to place call',
                ];
            }

            // Optional prompt can prepend as AI instruction context when both are set.
            if ($promptRaw !== '') {
                $resolvedPrompt = trim($this->variableResolver->resolve($promptRaw, $payload));
                if ($resolvedPrompt !== '') {
                    $script = $resolvedPrompt."\n\n".$script;
                }
            }

            return ['script' => $script, 'template_id' => $templateId, 'error' => null];
        }

        $script = trim($this->variableResolver->resolve($promptRaw, $payload));
        if ($script === '') {
            return [
                'script' => '',
                'template_id' => null,
                'error' => 'sendAiVoice prompt resolved to empty text; refusing to place call',
            ];
        }

        return ['script' => $script, 'template_id' => null, 'error' => null];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{value: int, error: string|null}
     */
    protected function resolveRetryCount(array $data): array
    {
        if (! array_key_exists('retryCount', $data) && ! array_key_exists('retry_count', $data)) {
            return ['value' => 1, 'error' => null];
        }

        $raw = $data['retryCount'] ?? $data['retry_count'];
        if ($raw === null || $raw === '') {
            return ['value' => 1, 'error' => null];
        }

        if (! is_numeric($raw)) {
            return ['value' => 0, 'error' => 'sendAiVoice retryCount must be a number between 0 and 5'];
        }

        $value = (int) $raw;
        if ($value < 0 || $value > 5) {
            return ['value' => $value, 'error' => 'sendAiVoice retryCount must be between 0 and 5'];
        }

        return ['value' => $value, 'error' => null];
    }

    protected function looksLikeConcreteRecipient(string $value): bool
    {
        $digits = preg_replace('/\D+/', '', $value);

        return is_string($digits) && strlen($digits) >= 7;
    }
}
