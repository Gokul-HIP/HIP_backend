<?php

namespace App\Modules\Workflow\Executors\Actions;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Executors\AbstractNodeExecutor;
use App\Modules\Workflow\Models\WorkflowExecution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateRecordExecutor extends AbstractNodeExecutor
{
    public function type(): string
    {
        return 'databaseUpdate';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $table = (string) ($node->data['table'] ?? '');
        $where = is_array($node->data['where'] ?? null) ? $node->data['where'] : [];
        $values = is_array($node->data['values'] ?? null) ? $node->data['values'] : [];

        if ($table === '' || $values === []) {
            return NodeExecutionResult::failed('databaseUpdate requires table and values.');
        }

        try {
            $query = DB::table($table);

            foreach ($where as $column => $value) {
                $query->where($column, $value);
            }

            $query->update($values);
        } catch (\Throwable $e) {
            Log::warning('databaseUpdate failed', ['error' => $e->getMessage()]);

            return NodeExecutionResult::failed($e->getMessage());
        }

        return NodeExecutionResult::continue();
    }
}
