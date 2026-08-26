<?php

namespace App\Modules\HospitalAutomation\Events;

use App\Models\DoctorBooking;
use App\Modules\HospitalAutomation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised when a DoctorBooking reaches confirmed status.
 *
 * The booking model is serialized by Laravel's SerializesModels trait so the
 * queue worker automatically rehydrates it from the database using the booking
 * ID — it does NOT carry a stale in-memory snapshot.  The listener then calls
 * fromAppointment() which eager-loads doctor/hospital/patient afresh, so the
 * automation context is always built from the live database row.
 */
class AppointmentBooked implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public DoctorBooking $appointment) {}

    public function triggerType(): string
    {
        return 'appointmentBooked';
    }

    public function payload(): array
    {
        return ['appointment' => $this->appointment];
    }
}
