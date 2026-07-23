<?php

namespace App\Modules\Workflow\Executors\Actions;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Executors\AbstractNodeExecutor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\Services\Runtime\TemplateManager;
use App\Modules\Workflow\Services\Runtime\VariableResolver;
use Illuminate\Support\Facades\Log;

abstract class AbstractMessagingExecutor extends AbstractNodeExecutor
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
        $title = $this->resolveTitle($data, $channel);
        $manualBody = $this->resolveManualBody($data, $channel);

        // Prefer live WorkflowContext payload (Eloquent models on first pass),
        // then persisted execution context (arrays after JSON cast / resume).
        $payload = array_merge(
            is_array($execution->context) ? $execution->context : [],
            is_array($context->payload) ? $context->payload : [],
            ['variables' => $context->variables]
        );

        if ($templateId !== null) {
            $message = $this->templateManager->render($templateId, $channel, $payload, $manualBody);
        } else {
            // Manual content: resolve variables only — do not auto-pick a DB template.
            $message = $this->variableResolver->resolve($manualBody, $payload);
        }

        // Log::info('Workflow messaging payload before ChannelManager::send', [
        //     'execution_id' => $execution->id,
        //     'node_id' => $node->id,
        //     'channel' => $channel,
        //     'templateId' => $templateId,
        //     'title' => $title,
        //     'subject' => $data['subject'] ?? null,
        //     'body' => $data['body'] ?? null,
        //     'messageTemplate' => $data['messageTemplate'] ?? $data['message_template'] ?? null,
        //     'rendered_message' => $message,
        // ]);

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
                ?? ''
            ),
            'email', 'sendEmail' => (string) (
                $data['body']
                ?? $data['messageTemplate']
                ?? $data['message_template']
                ?? ''
            ),
            default => (string) (
                $data['messageTemplate']
                ?? $data['message_template']
                ?? $data['body']
                ?? ''
            ),
        };
    }
}
