<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Api\GlobalSearchApiService;

class GlobalSearchController extends Controller
{
    protected GlobalSearchApiService $globalSearchService;

    public function __construct(GlobalSearchApiService $globalSearchService)
    {
        $this->globalSearchService = $globalSearchService;
    }

    public function globalSearch(Request $request)
    {

        $request->validate([
            'search'    => 'nullable|string',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_km' => 'nullable|integer|min:1|max:100',
            'per_page'  => 'nullable|integer|min:1|max:100',
        ]);

        $search  = $request->search;
        $lat     = (float) $request->latitude;
        $lng     = (float) $request->longitude;
        $radius  = (int) ($request->radius_km ?? 15);
        $perPage = (int) ($request->per_page ?? 10);

        $paginator = $this->globalSearchService->searchNearbyProcedures(
            $search,
            $lat,
            $lng,
            $radius,
            $perPage
        );

        if ($paginator->isEmpty()) {
            return response()->json([
                'status'     => 200,
                'message'    => 'No procedures found for nearby hospitals',
                'data'       => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page'     => $perPage,
                    'total'        => 0,
                    'last_page'    => 1,
                ],
            ], 200);
        }

        $data = collect($paginator->items())->map(function ($procedure) {
            return [
                'id'                  => $procedure->id,
                'procedure_name'      => $procedure->procedure_name,
                'description'         => $procedure->description,
                'cost'                => $procedure->cost,
                'duration'            => $procedure->estimated_time,
                'image'               => $procedure->image ? url('storage/procedures/' . $procedure->image) : null,
                'hospital_id'         => $procedure->hospital_id,
                // 'hospital_name'       => $procedure->hospital_name ?? null,
                // 'hospital_logo'       => $procedure->hospital_logo ? url('storage/hospital/' . $procedure->hospital_logo) : null,
                // 'distance_km'         => isset($procedure->distance) ? round((float) $procedure->distance, 2) : null,
                'recovery_time'       => $procedure->recovery_time,
                'success_rate'        => $procedure->success_rate ? $procedure->success_rate . ' %' : null,
                'hospitalization_days'=> $procedure->hospitalization_days ? $procedure->hospitalization_days . ' days' : null,
            ];
        });

        return response()->json([
            'status'     => 200,
            'message'    => 'Procedures fetched successfully',
            'data'       => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ], 200);
    }

}
