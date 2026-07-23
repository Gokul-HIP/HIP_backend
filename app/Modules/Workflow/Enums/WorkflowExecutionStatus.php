<?php

namespace App\Modules\Workflow\Enums;

enum WorkflowExecutionStatus: string
{
    case Started = 'started';
    case Running = 'running';
    case Waiting = 'waiting';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
}
