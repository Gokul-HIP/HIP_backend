<?php

namespace App\Modules\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AppointmentBooked
{
    use Dispatchable;

    public function __construct(
        public int $appointmentId,
        public array $context = [],
    ) {}
}
