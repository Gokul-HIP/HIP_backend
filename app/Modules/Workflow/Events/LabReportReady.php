<?php

namespace App\Modules\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;

class LabReportReady
{
    use Dispatchable;

    public function __construct(
        public int $reportId,
        public array $context = [],
    ) {}
}
