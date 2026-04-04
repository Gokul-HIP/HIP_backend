<?php

namespace App\Services\HospitalAdmin;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Hospital;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DoctorProfileService
{
    /**
     * Get all hospital IDs for the authenticated organization.
     *
     * @return int[]
     */
    public function organizationHospitalIds(): array
    {
        return Hospital::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Resolve a doctor belonging to the current organization and its hospitals.
     */
    public function getDoctor(string $doctorId): ?Doctor
    {
        $hospitalIds = $this->organizationHospitalIds();

        if ($hospitalIds === []) {
            return null;
        }

        return Doctor::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->whereKey($doctorId)
            ->where(function ($query) use ($hospitalIds) {
                foreach ($hospitalIds as $hospitalId) {
                    $query->orWhereJsonContains('hospital_ids', $hospitalId);
                }
            })
            ->first();
    }

    /**
     * Linked hospitals for a doctor within the current organization.
     */
    public function linkedHospitals(Doctor $doctor): Collection
    {
        $hospitalIds = array_map('intval', $doctor->hospital_ids ?? []);

        return Hospital::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->whereIn('id', $hospitalIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Doctor schedules for a given month, formatted for the view.
     */
    public function getDoctorSchedules(string $doctorId, int $year, int $month): Collection
    {
        return DoctorSchedule::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->where('doctor_id', $doctorId)
            ->whereYear('schedule_date', $year)
            ->whereMonth('schedule_date', $month)
            ->orderBy('schedule_date')
            ->get()
            ->map(function (DoctorSchedule $schedule) {
                return [
                    'id' => $schedule->id,
                    'date' => $schedule->schedule_date?->toDateString(),
                    'slots' => collect($schedule->time_slots ?? [])
                        ->map(fn ($slot) => [
                            'from' => Carbon::createFromFormat('H:i', (string) ($slot['from'] ?? '00:00'))->format('h:i A'),
                            'to' => Carbon::createFromFormat('H:i', (string) ($slot['to'] ?? '00:00'))->format('h:i A'),
                        ])
                        ->values()
                        ->all(),
                ];
            });
    }

    /**
     * Find a schedule scoped to current organization and doctor.
     */
    public function findScopedSchedule(string $doctorId, int $scheduleId): ?DoctorSchedule
    {
        return DoctorSchedule::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->where('doctor_id', $doctorId)
            ->find($scheduleId);
    }
}

