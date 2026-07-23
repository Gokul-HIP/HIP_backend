<?php

namespace App\Modules\Workflow\Executors\Actions;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Executors\AbstractNodeExecutor;
use App\Modules\Workflow\Models\WorkflowExecution;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookExecutor extends AbstractNodeExecutor
{
    public function type(): string
    {
        return 'webhook';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $url = (string) ($node->data['url'] ?? '');

        if ($url === '') {
            return NodeExecutionResult::failed('Webhook URL is required.');
        }

        try {
            $response = Http::timeout(30)->post($url, [
                'execution_id' => $execution->id,
                'workflow_id' => $execution->workflow_id,
                'context' => $execution->context,
                'variables' => $context->variables,
            ]);

            if (! $response->successful()) {
                return NodeExecutionResult::failed('Webhook returned HTTP '.$response->status());
            }
        } catch (\Throwable $e) {
            Log::warning('Webhook execution failed', ['error' => $e->getMessage()]);

            return NodeExecutionResult::failed($e->getMessage());
        }

        return NodeExecutionResult::continue();
    }
}
