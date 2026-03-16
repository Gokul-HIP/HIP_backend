<?php

namespace App\Services\Api;

use App\Models\Procedure;
use App\Models\Hospital;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GlobalSearchApiService
{
    /**
     * Get procedures for nearby hospitals, optionally filtered by search term.
     *
     * When $search is null/empty, returns all procedures for hospitals within the radius.
     * When $search is provided, filters by procedure name / description.
     */
    public function searchNearbyProcedures(
        ?string $search,
        float $lat,
        float $lng,
        int $radiusKm = 15,
        int $perPage = 10
    ): LengthAwarePaginator {
        // Reuse distance logic from hospitals: compute distance from hospital admin coords
        $query = Procedure::query()
            ->join('hospitals', 'hospitals.id', '=', 'procedures.hospital_id')
            ->where('hospitals.status', 'active')
            ->whereNotNull('hospitals.admin_latitude')
            ->whereNotNull('hospitals.admin_longitude')
            ->selectRaw("
                procedures.*,
                hospitals.name as hospital_name,
                hospitals.logo as hospital_logo,
                (
                    6371 * acos(
                        cos(radians(?))
                        * cos(radians(hospitals.admin_latitude))
                        * cos(radians(hospitals.admin_longitude) - radians(?))
                        + sin(radians(?))
                        * sin(radians(hospitals.admin_latitude))
                    )
                ) AS distance
            ", [$lat, $lng, $lat])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');

        if ($search !== null && trim($search) !== '') {
            $searchLike = '%' . trim($search) . '%';
            $query->where(function ($q) use ($searchLike) {
                $q->where('procedures.procedure_name', 'like', $searchLike)
                    ->orWhere('procedures.description', 'like', $searchLike);
            });
        }

        return $query->paginate($perPage);
    }
}

