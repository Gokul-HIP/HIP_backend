<?php

namespace App\Livewire\Admin\DoctorBooking;

use App\Livewire\Concerns\ReschedulesDoctorBooking;
use App\Models\DoctorBooking;
use Livewire\Component;

class Reschedule extends Component
{
    use ReschedulesDoctorBooking;

    protected function findRescheduleBooking(int $id): ?DoctorBooking
    {
        return DoctorBooking::query()->find($id);
    }

    public function render()
    {
        return view('livewire.admin.doctor-booking.reschedule');
    }
}
