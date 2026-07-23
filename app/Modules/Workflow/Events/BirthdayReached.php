<?php

namespace App\Modules\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;

class BirthdayReached
{
    use Dispatchable;

    public function __construct(
        public int $patientId,
        public array $context = [],
    ) {}
}
