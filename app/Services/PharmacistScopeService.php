<?php

namespace App\Services;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\PharmacistCredential;
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

    /**
     * Hospitals for this pharmacist: credential hospital ids union HIPUser.hospital_id.
     * preferred_branch_id is ignored.
     * Named hospitalIdsFor because hospitalIds(string) already selects the panel filter.
     *
     * @return array<int, int>
     */
    public function hospitalIdsFor(HIPUser $user): array
    {
        $ids = [];

        $fromUser = $this->positiveHospitalId($user->hospital_id);
        if ($fromUser !== null) {
            $ids[$fromUser] = $fromUser;
        }

        $credentialIds = PharmacistCredential::query()
            ->where('hip_user_id', $user->id)
            ->pluck('hospital_id');

        foreach ($credentialIds as $hospitalId) {
            $id = $this->positiveHospitalId($hospitalId);
            if ($id !== null) {
                $ids[$id] = $id;
            }
        }

        $values = array_values($ids);
        sort($values);

        return $values;
    }

    /**
     * Pharmacy ids linked on those hospitals via hospitals.pharmacy_ids.
     *
     * @return array<int, int>
     */
    public function pharmacyIds(HIPUser $user): array
    {
        $hospitalIds = $this->hospitalIdsFor($user);

        if ($hospitalIds === []) {
            return [];
        }

        $ids = [];
        $hospitals = Hospital::query()
            ->whereIn('id', $hospitalIds)
            ->get(['id', 'pharmacy_ids']);

        foreach ($hospitals as $hospital) {
            foreach ($hospital->pharmacy_ids ?? [] as $pharmacyId) {
                $id = (int) $pharmacyId;
                if ($id > 0) {
                    $ids[$id] = $id;
                }
            }
        }

        $values = array_values($ids);
        sort($values);

        return $values;
    }

    private function positiveHospitalId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
