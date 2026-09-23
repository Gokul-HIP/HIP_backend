<?php

namespace App\Modules\Workflow\Jobs;

use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ContinueWorkflowExecutionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  string  $nodeId  Resume target node id (the next node after a delay, or a parallel branch start).
     */
    public function __construct(
        public int $executionId,
        public string $nodeId,
    ) {}

    public function handle(WorkflowExecutor $workflowExecutor): void
    {
        $execution = WorkflowExecution::query()->find($this->executionId);

        if (! $execution) {
            return;
        }

        $status = (string) $execution->status;
        $resumable = in_array($status, [
            WorkflowExecutionStatus::Waiting->value,
            WorkflowExecutionStatus::Running->value,
            WorkflowExecutionStatus::Started->value,
        ], true);

        if (! $resumable) {
            return;
        }

        $workflowExecutor->resume($execution, $this->nodeId);
    }
}
