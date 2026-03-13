<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LocationMaster;
use App\Models\Hospital;
use App\Models\HospitalReview;

class LocationFilter extends Controller
{
    
    public function byLocation(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'page'      => 'nullable|integer|min:1',
            'per_page'  => 'nullable|integer|min:1|max:50',
            'is_promoted' => 'required|in:0,1',
        ]);
        $lat      = $request->latitude;
        $lng      = $request->longitude;
        $radius   = 15;
        $perPage  = $request->per_page ?? 10;
        $isPromoted = $request->is_promoted;

        $nearestArea = LocationMaster::selectRaw("
            id,
            area,
            latitude,
            longitude,
            (6371 * acos(
                cos(radians(?))
                * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?))
                * sin(radians(latitude))
            )) AS distance
        ", [$lat, $lng, $lat])
            ->orderBy('distance')
            ->first();

        $nearestAreaId = $nearestArea?->id;

        $hospitals = Hospital::query()
            ->leftJoin('location_masters as lm', 'lm.id', '=', 'hospitals.location_id')
            ->leftJoinSub(
                HospitalReview::query()
                    ->selectRaw('hospital_id, ROUND(AVG(rating), 1) as avg_rating')
                    ->where('status', 'active')
                    ->groupBy('hospital_id'),
                'hr',
                'hr.hospital_id',
                '=',
                'hospitals.id'
            )
            ->selectRaw("
                hospitals.*,
                COALESCE(lm.area, 'Unknown Area') as area,
                lm.zipcode,
                COALESCE(hr.avg_rating, 0) as hospital_rating,

                (6371 * acos(
                    cos(radians(?))
                    * cos(radians(
                        CASE
                            WHEN hospitals.admin_latitude BETWEEN -90 AND 90
                            THEN hospitals.admin_latitude
                            ELSE lm.latitude
                        END
                    ))
                    * cos(radians(
                        CASE
                            WHEN hospitals.admin_longitude BETWEEN -180 AND 180
                            THEN hospitals.admin_longitude
                            ELSE lm.longitude
                        END
                    ) - radians(?))
                    + sin(radians(?))
                    * sin(radians(
                        CASE
                            WHEN hospitals.admin_latitude BETWEEN -90 AND 90
                            THEN hospitals.admin_latitude
                            ELSE lm.latitude
                        END
                    ))
                )) AS distance,

                CASE
                    WHEN lm.id = ? THEN 0
                    ELSE 1
                END AS area_priority
            ", [$lat, $lng, $lat, $nearestAreaId])
            ->where('hospitals.status', 'active')
            ->where('hospitals.is_promoted', $isPromoted)
            ->whereRaw("
                (6371 * acos(
                    cos(radians(?))
                    * cos(radians(
                        CASE
                            WHEN hospitals.admin_latitude BETWEEN -90 AND 90
                            THEN hospitals.admin_latitude
                            ELSE lm.latitude
                        END
                    ))
                    * cos(radians(
                        CASE
                            WHEN hospitals.admin_longitude BETWEEN -180 AND 180
                            THEN hospitals.admin_longitude
                            ELSE lm.longitude
                        END
                    ) - radians(?))
                    + sin(radians(?))
                    * sin(radians(
                        CASE
                            WHEN hospitals.admin_latitude BETWEEN -90 AND 90
                            THEN hospitals.admin_latitude
                            ELSE lm.latitude
                        END
                    ))
                )) <= ?
            ", [$lat, $lng, $lat, $radius])
            
            ->orderBy('area_priority')
            ->orderBy('distance')
            ->paginate($perPage);

        return response()->json([
            'status'  => 200,
            'message' => 'Hospitals fetched successfully',
            'data'    => $hospitals->getCollection()->map(function ($hospital) {
                    return [
                        'id'               => $hospital->id,
                        'hospital_name'    => $hospital->name,
                        'hospital_about'   => $hospital->about,
                        'subtitle'         => $hospital->subtitle,
                        'distance_km'      => round($hospital->distance, 2),
                        'area_priority'    => $hospital->area_priority,
                        'hospital_rating'  => (string) ($hospital->hospital_rating ?? '0'),
                        'logo'             => $hospital->logo
                            ? url('storage/hospital/' . $hospital->logo)
                            : null,
                        'is_promoted'      => $hospital->is_promoted,
                    ];
                }),
                'current_page' => $hospitals->currentPage(),
                'per_page'     => $hospitals->perPage(),
                'count'        => $hospitals->count(),
                'total'        => $hospitals->total(),
                'last_page'    => $hospitals->lastPage(),
                'is_promoted'  => $hospitals->where('is_promoted', true)->count(),
            ],
        );
    }

    public function searchArea(Request $request)
    {
        $request->validate([
            'search' => 'required|string',
        ]);
    
        $search = trim($request->search);
    
        if (strlen($search) < 2) {
            return response()->json(['areas' => []]);
        }
    
        $areas = LocationMaster::where('area', 'like', "%$search%")
            ->orWhere('city', 'like', "%$search%")
            ->orWhere('state', 'like', "%$search%")
            ->orWhere('zipcode', 'like', "%$search%")
            ->select('id', 'area', 'area_code', 'zipcode', 'city', 'state', 'latitude', 'longitude')
            ->limit(10)
            ->get();
    
        return response()->json([
            'areas' => $areas
        ]);
    }

}
