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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiPromptNodeProcessor extends AbstractNodeProcessor
{
    public function __construct(
        \App\Modules\Workflow\Services\Runtime\ActionDispatcher $actionDispatcher,
        protected TemplateManager $templateManager,
        protected VariableResolver $variableResolver,
        protected ChannelManager $channelManager,
    ) {
        parent::__construct($actionDispatcher);
    }

    public function type(): string
    {
        return 'aiPrompt';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $data = $node->data;
        $promptTemplateId = isset($data['templateId']) ? (int) $data['templateId'] : null;
        $promptType = (string) ($data['promptType'] ?? 'patient_summary');
        $fallbackPrompt = (string) ($data['prompt'] ?? 'Summarize the patient context.');

        $payload = array_merge(
            is_array($execution->context) ? $execution->context : [],
            ['variables' => $context->variables]
        );

        $prompt = $this->templateManager->render($promptTemplateId, 'ai', $payload, $fallbackPrompt);
        $summary = $this->generateSummary($prompt, $promptType);

        $context->setVariable('ai_summary', $summary);
        $context->setVariable('ai_prompt_type', $promptType);

        $outputChannel = $data['outputChannel'] ?? null;
        if (is_string($outputChannel) && $outputChannel !== '') {
            $this->channelManager->send(
                channel: $outputChannel,
                execution: $execution,
                nodeId: $node->id,
                message: $summary,
                context: $payload,
                subject: (string) ($data['subject'] ?? 'AI Summary'),
            );
        }

        return NodeExecutionResult::continue();
    }

    protected function generateSummary(string $prompt, string $promptType): string
    {
        $endpoint = config('services.workflow_ai.endpoint');

        if ($endpoint) {
            try {
                $response = Http::timeout(30)->post($endpoint, [
                    'prompt' => $prompt,
                    'type' => $promptType,
                ]);

                if ($response->successful()) {
                    return (string) ($response->json('summary') ?? $response->body());
                }
            } catch (\Throwable $e) {
                Log::warning('AI prompt provider failed', ['error' => $e->getMessage()]);
            }
        }

        return '[AI Summary — configure services.workflow_ai.endpoint] '
            .mb_substr($prompt, 0, 500);
    }
}
