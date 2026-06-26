<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class PatientRecordScope
{
    /**
     * Scope records to a specific patient when patient_id exists.
     * Falls back to member_id only when patient_id is unavailable.
     */
    public static function apply(
        Builder $query,
        ?string $patientId,
        ?string $memberId,
        string $patientColumn = 'patient_id',
        string $memberColumn = 'member_id'
    ): Builder {
        if (filled($patientId)) {
            return $query->where($patientColumn, $patientId);
        }

        if (filled($memberId)) {
            return $query->where($memberColumn, $memberId);
        }

        return $query->whereRaw('1 = 0');
    }
}
