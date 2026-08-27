<?php

namespace App\Modules\Workflow\Executors\Actions;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowMessageTemplate;

/**
 * Frontend catalog node: sendTemplate.
 *
 * Requires templateId + channel from saved React Flow node data.
 * Renders the exact selected template (no silent auto-pick fallback),
 * resolves workflow variables, and dispatches via ChannelManager.
 */
class SendTemplateExecutor extends AbstractMessagingExecutor
{
    /** @var list<string> */
    private const SUPPORTED_CHANNELS = ['whatsapp', 'sms', 'email', 'push'];

    public function type(): string
    {
        return 'sendTemplate';
    }

    /**
     * AbstractMessagingExecutor requires channel(); sendTemplate reads channel from node data in run().
     */
    protected function channel(): string
    {
        return 'whatsapp';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $data = is_array($node->data) ? $node->data : [];

        $templateId = $this->resolveTemplateId($data);
        if ($templateId === null) {
            return NodeExecutionResult::failed('sendTemplate requires a valid templateId');
        }

        $channel = $this->resolveConfiguredChannel($data);
        if ($channel === null) {
            return NodeExecutionResult::failed(
                'sendTemplate requires a supported channel (whatsapp, sms, email, push)'
            );
        }

        $template = WorkflowMessageTemplate::query()
            ->where('id', $templateId)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return NodeExecutionResult::failed(
                "sendTemplate template not found or inactive: {$templateId}"
            );
        }

        $body = trim((string) ($template->body ?? ''));
        if ($body === '') {
            return NodeExecutionResult::failed(
                "sendTemplate template {$templateId} has an empty body"
            );
        }

        $payload = array_merge(
            is_array($execution->context) ? $execution->context : [],
            is_array($context->payload) ? $context->payload : [],
            ['variables' => $context->variables]
        );

        $message = trim($this->variableResolver->resolve($body, $payload));
        if ($message === '') {
            return NodeExecutionResult::failed(
                'sendTemplate resolved to an empty message; refusing to send'
            );
        }

        $title = $this->variableResolver->resolve(
            $this->resolveTitle($data, $channel),
            $payload
        );

        $result = $this->channelManager->send(
            channel: $channel,
            execution: $execution,
            nodeId: $node->id,
            message: $message,
            context: $payload,
            subject: $title,
            recipient: $data['recipient'] ?? null,
        );

        if (! ($result['success'] ?? false)) {
            return NodeExecutionResult::failed($result['response'] ?? 'Channel delivery failed');
        }

        return NodeExecutionResult::continue();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveConfiguredChannel(array $data): ?string
    {
        $raw = strtolower(trim((string) ($data['channel'] ?? '')));

        if ($raw === '') {
            return null;
        }

        // Accept FE option values and occasional messaging nodeType aliases.
        $normalized = match ($raw) {
            'sendwhatsapp' => 'whatsapp',
            'sendsms', 'send_sms' => 'sms',
            'sendemail' => 'email',
            'sendpush' => 'push',
            default => $raw,
        };

        return in_array($normalized, self::SUPPORTED_CHANNELS, true) ? $normalized : null;
    }
}
