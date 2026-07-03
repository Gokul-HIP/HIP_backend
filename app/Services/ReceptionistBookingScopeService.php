<?php

namespace App\Services;

use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\SecondOpinion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ReceptionistBookingScopeService
{
    public function organizationId(): ?string
    {
        $user = Auth::user();

        if ($user instanceof HIPUser && filled($user->organization_id)) {
            return (string) $user->organization_id;
        }

        return null;
    }

    public function defaultHospitalId(): ?int
    {
        $user = Auth::user();

        if ($user instanceof HIPUser && filled($user->hospital_id)) {
            return (int) $user->hospital_id;
        }

        return null;
    }

    public function defaultHospitalFilter(): string
    {
        $id = $this->defaultHospitalId();

        return $id ? (string) $id : 'all';
    }

    public function accessibleHospitals(): Collection
    {
        $organizationId = $this->organizationId();

        if (! $organizationId) {
            return collect();
        }

        return Hospital::query()
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name', 'diagnostic_center_id']);
    }

    /**
     * @return array<int, int>
     */
    public function hospitalIds(string $hospitalFilter): array
    {
        $accessible = $this->accessibleHospitals()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($hospitalFilter === 'all') {
            return $accessible;
        }

        $id = (int) $hospitalFilter;

        if (in_array($id, $accessible, true)) {
            return [$id];
        }

        $default = $this->defaultHospitalId();

        return $default ? [$default] : $accessible;
    }

    /**
     * @return array<int, int>
     */
    public function diagnosticCenterIds(string $hospitalFilter): array
    {
        $hospitalIds = $this->hospitalIds($hospitalFilter);

        return Hospital::query()
            ->whereIn('id', $hospitalIds)
            ->whereNotNull('diagnostic_center_id')
            ->pluck('diagnostic_center_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function hospitalsKeyed(): Collection
    {
        return $this->accessibleHospitals()->keyBy('id');
    }

    public function findDoctorBooking(int $id): ?DoctorBooking
    {
        return DoctorBooking::query()
            ->whereIn('hospital_id', $this->hospitalIds('all'))
            ->find($id);
    }

    public function findSecondOpinion(int $id): ?SecondOpinion
    {
        return SecondOpinion::query()
            ->whereIn('branch_id', $this->hospitalIds('all'))
            ->find($id);
    }

    public function findDiagnosticBooking(int $id): ?DiagnosticTestBooking
    {
        $hospitalIds = $this->hospitalIds('all');
        $diagnosticIds = $this->diagnosticCenterIds('all');

        return DiagnosticTestBooking::query()
            ->where(function ($query) use ($hospitalIds, $diagnosticIds) {
                $query->whereIn('branch_id', $hospitalIds);
                if ($diagnosticIds !== []) {
                    $query->orWhereIn('diagnostic_center_id', $diagnosticIds);
                }
            })
            ->find($id);
    }

    public function doctorBookingBelongsToScope(int $bookingId): bool
    {
        return $this->findDoctorBooking($bookingId) !== null;
    }

    public function secondOpinionBelongsToScope(int $bookingId): bool
    {
        return $this->findSecondOpinion($bookingId) !== null;
    }

    public function diagnosticBookingBelongsToScope(int $bookingId): bool
    {
        return $this->findDiagnosticBooking($bookingId) !== null;
    }
}
