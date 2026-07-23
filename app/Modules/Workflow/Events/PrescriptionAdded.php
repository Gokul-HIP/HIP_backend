<?php

namespace App\Modules\Workflow\Events;

use App\Models\Prescription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrescriptionAdded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Prescription $prescription
    ) {}
}
