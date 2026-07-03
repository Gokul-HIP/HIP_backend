<?php

namespace App\Services;

use App\Models\HIPUser;
use App\Models\Hospital;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PharmacistScopeService
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
            ->get(['id', 'name']);
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

    public function hospitalsKeyed(): Collection
    {
        return $this->accessibleHospitals()->keyBy('id');
    }
}
