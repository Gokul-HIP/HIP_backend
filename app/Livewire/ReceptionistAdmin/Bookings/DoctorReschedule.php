<?php

namespace App\Livewire\ReceptionistAdmin\Bookings;

use App\Livewire\Concerns\ReschedulesDoctorBooking;
use App\Livewire\ReceptionistAdmin\Concerns\ScopesReceptionistBookings;
use App\Models\DoctorBooking;
use Livewire\Component;

class DoctorReschedule extends Component
{
    use ReschedulesDoctorBooking;
    use ScopesReceptionistBookings;

    public function mount(): void
    {
        $this->refreshEvent = 'refreshReceptionistDoctorBookings';
    }

    protected function findRescheduleBooking(int $id): ?DoctorBooking
    {
        return $this->scopeService()->findDoctorBooking($id);
    }

    public function render()
    {
        return view('livewire.admin.doctor-booking.reschedule');
    }
}
