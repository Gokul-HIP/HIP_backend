<?php

namespace App\Services\Hms;

use App\Exceptions\HmsClientException;
use App\Models\HmsExternalIdMap;
use App\Models\HmsIntegration;
use App\Models\Hospital;

class HmsHospitalResolver
{
    public function resolve(HmsIntegration $integration, string $hospitalCode): Hospital
    {
        $hospitalCode = trim($hospitalCode);
        $hospital = null;

        $mapped = HmsExternalIdMap::query()
            ->where('integration_id', $integration->getKey())
            ->where('entity_type', HmsExternalIdMap::TYPE_HOSPITAL)
            ->where('external_id', $hospitalCode)
            ->first();

        if ($mapped) {
            $hospital = Hospital::query()->find($mapped->local_id);
        } elseif (ctype_digit($hospitalCode)) {
            $hospital = Hospital::query()->find((int) $hospitalCode);
        }

        if (! $hospital) {
            throw new HmsClientException('not_found', 'Hospital was not found.', 404);
        }

        $allowed = array_map('intval', $integration->hospital_ids ?? []);
        if (! in_array((int) $hospital->getKey(), $allowed, true)) {
            throw new HmsClientException(
                'forbidden_hospital',
                'This integration cannot access that hospital.',
                403
            );
        }

        return $hospital;
    }
}
