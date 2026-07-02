<?php

namespace App\Services;

use App\Models\DiagnosticTestBooking;
use App\Models\Hospital;
use App\Models\TechnicianCredential;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TechnicianDiagnosticScopeService
{
    public function organizationId(): ?string
    {
        $user = Auth::user();

        if (filled($user?->organization_id)) {
            return (string) $user->organization_id;
        }

        return TechnicianCredential::query()
            ->where('hip_user_id', $user?->id)
            ->value('organization_id');
    }

    /**
     * @return array<int, int>
     */
    public function diagnosticCenterIds(?string $hospitalFilter = 'all'): array
    {
        $organizationId = $this->organizationId();

        if (! $organizationId) {
            return [];
        }

        $query = Hospital::query()
            ->where('organization_id', $organizationId)
            ->whereNotNull('diagnostic_center_id');

        if ($hospitalFilter !== 'all' && filled($hospitalFilter)) {
            $query->where('id', $hospitalFilter);
        }

        return $query
            ->pluck('diagnostic_center_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function accessibleHospitals(): Collection
    {
        $organizationId = $this->organizationId();

        if (! $organizationId) {
            return collect();
        }

        return Hospital::query()
            ->where('organization_id', $organizationId)
            ->whereNotNull('diagnostic_center_id')
            ->orderBy('name')
            ->get(['id', 'name', 'diagnostic_center_id']);
    }

    public function scopedBookingsQuery(?string $hospitalFilter = 'all'): Builder
    {
        $diagnosticIds = $this->diagnosticCenterIds($hospitalFilter);

        return DiagnosticTestBooking::query()
            ->whereIn('diagnostic_center_id', $diagnosticIds);
    }

    public function findScopedBooking(int $id, ?string $hospitalFilter = 'all'): ?DiagnosticTestBooking
    {
        return $this->scopedBookingsQuery($hospitalFilter)->find($id);
    }
}
