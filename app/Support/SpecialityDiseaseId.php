<?php

namespace App\Support;

use App\Models\SpecialitiesMaster;

final class SpecialityDiseaseId
{
    private const MULTIPLIER = 1000;

    public static function encode(int $specialityId, int $index): int
    {
        return ($specialityId * self::MULTIPLIER) + $index;
    }

    /**
     * @return array{speciality_id: int, index: int|null}
     */
    public static function resolve(int $referenceId): array
    {
        if ($referenceId < self::MULTIPLIER) {
            return [
                'speciality_id' => $referenceId,
                'index'         => null,
            ];
        }

        return [
            'speciality_id' => intdiv($referenceId, self::MULTIPLIER),
            'index'         => $referenceId % self::MULTIPLIER,
        ];
    }

    public static function isValid(int $referenceId): bool
    {
        $resolved = self::resolve($referenceId);
        $speciality = SpecialitiesMaster::query()
            ->where('status', 'active')
            ->find($resolved['speciality_id']);

        if (! $speciality) {
            return false;
        }

        if ($resolved['index'] === null) {
            return true;
        }

        $entries = $speciality->normalizedDiseaseEntries();

        return array_key_exists($resolved['index'], $entries);
    }
}
