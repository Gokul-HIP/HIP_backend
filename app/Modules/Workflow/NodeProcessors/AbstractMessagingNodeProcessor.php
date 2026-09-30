<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\Services\Runtime\TemplateManager;
use App\Modules\Workflow\Services\Runtime\VariableResolver;
use Illuminate\Support\Facades\Log;

abstract class AbstractMessagingNodeProcessor extends AbstractNodeProcessor
{
    public function __construct(
        \App\Modules\Workflow\Services\Runtime\ActionDispatcher $actionDispatcher,
        protected ChannelManager $channelManager,
        protected TemplateManager $templateManager,
        protected VariableResolver $variableResolver,
    ) {
        parent::__construct($actionDispatcher);
    }

    abstract protected function channel(): string;

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $data = is_array($node->data) ? $node->data : [];
        $channel = $this->channel();
        $templateId = $this->resolveTemplateId($data);
        $payload = array_merge(
            is_array($execution->context) ? $execution->context : [],
            is_array($context->payload) ? $context->payload : [],
            ['variables' => $context->variables]
        );

        $logPrefix = match ($context->triggerType) {
            'appointmentBooked' => '[appointment-booked]',
            'labTestOrdered' => '[lab-test-ordered]',
            default => null,
        };

        if ($logPrefix !== null && $channel === 'push') {
            Log::info($logPrefix.' send push started', [
                'workflow_execution_id' => $execution->id,
                'workflow_id' => $execution->workflow_id,
                'trigger_type' => $context->triggerType,
                'hospital_id' => $context->payload['hospital_id'] ?? null,
                'member_present' => filled($payload['member_id'] ?? null),
            ]);
        }

        $title = $this->variableResolver->resolve($this->resolveTitle($data, $channel), $payload);
        $manualBody = $this->resolveManualBody($data, $channel);

        if ($templateId !== null) {
            $message = $this->templateManager->render($templateId, $channel, $payload, $manualBody);
        } else {
            // Manual content: resolve variables only — do not auto-pick a DB template.
            $message = $this->variableResolver->resolve($manualBody, $payload);
        }

        try {
            $attachments = $this->resolveAttachments($data, $payload);
        } catch (\RuntimeException $e) {
            return NodeExecutionResult::failed($e->getMessage());
        }

        $result = $this->channelManager->send(
            channel: $channel,
            execution: $execution,
            nodeId: $node->id,
            message: $message,
            context: $payload,
            subject: $title,
            recipient: $data['recipient'] ?? null,
            attachments: $attachments,
            options: $this->channelOptions($data, $payload),
        );

        if (! ($result['success'] ?? false)) {
            return NodeExecutionResult::failed($result['response'] ?? 'Channel delivery failed');
        }

        if ($logPrefix !== null && $channel === 'push') {
            Log::info($logPrefix.' send push completed', [
                'workflow_execution_id' => $execution->id,
                'workflow_id' => $execution->workflow_id,
                'trigger_type' => $context->triggerType,
            ]);
        }

        return NodeExecutionResult::continue();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveTemplateId(array $data): ?int
    {
        if (! isset($data['templateId']) && ! isset($data['template_id'])) {
            return null;
        }

        $raw = $data['templateId'] ?? $data['template_id'];

        if ($raw === null || $raw === '' || $raw === false) {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }

    /**
     * Channel-specific title / subject used by ChannelManager as the notification title.
     *
     * @param  array<string, mixed>  $data
     */
    protected function resolveTitle(array $data, string $channel): string
    {
        return match ($channel) {
            'push', 'sendPush' => (string) ($data['title'] ?? $data['subject'] ?? 'Notification'),
            'email', 'sendEmail' => (string) ($data['subject'] ?? $data['title'] ?? 'Notification'),
            default => (string) ($data['subject'] ?? $data['title'] ?? 'Notification'),
        };
    }

    /**
     * Manual body text when no template is selected.
     *
     * @param  array<string, mixed>  $data
     */
    protected function resolveManualBody(array $data, string $channel): string
    {
        return match ($channel) {
            'push', 'sendPush' => (string) (
                $data['body']
                ?? $data['messageTemplate']
                ?? $data['message_template']
                ?? $data['message']
                ?? ''
            ),
            'email', 'sendEmail' => (string) (
                $data['body']
                ?? $data['messageTemplate']
                ?? $data['message_template']
                ?? $data['message']
                ?? ''
            ),
            default => (string) (
                $data['messageTemplate']
                ?? $data['message_template']
                ?? $data['message']
                ?? $data['body']
                ?? ''
            ),
        };
    }

    /**
     * Optional binary attachments. Email may attach a generated invoice PDF.
     * Never inject attachment data into the message body.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     * @return list<array{filename: string, content: string, mime: string}>
     */
    protected function resolveAttachments(array $data, array $payload): array
    {
        return [];
    }

    /**
     * Extra provider options (WhatsApp template/media). Never include credentials.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function channelOptions(array $data, array $payload): array
    {
        return [];
    }
}
