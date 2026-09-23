<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateRecordNodeProcessor extends AbstractNodeProcessor
{
    public function type(): string
    {
        return 'createRecord';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $table = (string) ($node->data['table'] ?? '');
        $values = is_array($node->data['values'] ?? null) ? $node->data['values'] : [];

        if ($table === '' || $values === []) {
            return NodeExecutionResult::failed('createRecord requires table and values.');
        }

        try {
            $id = DB::table($table)->insertGetId($values);
            $context->setVariable('created_record_id', $id);
        } catch (\Throwable $e) {
            Log::warning('createRecord failed', ['error' => $e->getMessage()]);

            return NodeExecutionResult::failed($e->getMessage());
        }

        return NodeExecutionResult::continue();
    }
}
