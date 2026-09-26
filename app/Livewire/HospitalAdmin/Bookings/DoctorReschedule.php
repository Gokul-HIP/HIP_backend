<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Livewire\Concerns\ReschedulesDoctorBooking;
use App\Models\DoctorBooking;
use App\Models\Hospital;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DoctorReschedule extends Component
{
    use ReschedulesDoctorBooking;

    protected function findRescheduleBooking(int $id): ?DoctorBooking
    {
        $hospitalIds = Hospital::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->pluck('id')
            ->map(fn ($hospitalId) => (int) $hospitalId)
            ->all();

        return DoctorBooking::query()
            ->whereIn('hospital_id', $hospitalIds)
            ->find($id);
    }

    public function render()
    {
        return view('livewire.admin.doctor-booking.reschedule');
    }
}
