<?php

namespace App\Modules\Automation\Console\Commands;

use App\Modules\Automation\Services\MissedAppointmentDetector;
use Illuminate\Console\Command;

class DispatchMissedAppointmentsCommand extends Command
{
    protected $signature = 'hospital-automation:dispatch-missed-appointments';

    protected $description = 'Mark confirmed DoctorBooking rows as missed after scheduled date+time and dispatch AppointmentMissed.';

    public function handle(MissedAppointmentDetector $detector): int
    {
        $result = $detector->detectAndDispatch();

        $this->info(sprintf(
            'Marked %d missed appointment(s); skipped %d invalid schedule(s).',
            $result['marked'],
            $result['skipped']
        ));

        return self::SUCCESS;
    }
}
