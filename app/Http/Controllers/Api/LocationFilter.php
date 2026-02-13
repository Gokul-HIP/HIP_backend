<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LocationMaster;
use App\Models\Hospital;

class LocationFilter extends Controller
{
    
    public function byLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $lat = $request->latitude;
        $lng = $request->longitude;
        $radius = 15;
        
        // Nearest area (AREA-based)
        $nearestArea = LocationMaster::selectRaw("
            id,
            area,
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

        // Hospitals (HOSPITAL-based distance)
        $hospitals = Hospital::query()
            ->leftJoin('location_masters as lm', 'lm.id', '=', 'hospitals.location_id')
            ->selectRaw("
                hospitals.*,
                COALESCE(lm.area, 'Unknown Area') as area,
                lm.zipcode,
        
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
            ", [$lat, $lng, $lat, $nearestArea->id])
            ->where('hospitals.status', 'active')
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->orderBy('area_priority')
            ->get();
    
            return response()->json(
                // 'nearest_area' => $nearestArea,
                $hospitals->map(function ($hospital) {
                    return [
                        'id'            => $hospital->id,
                        'hospital_name' => $hospital->name,
                        'hospital_about' => $hospital->about,
                        'subtitle'      => $hospital->subtitle,
                        'distance_km'   => round($hospital->distance, 2),
                        'area_priority' => $hospital->area_priority,
                        'hospital_rating' => '4.5',
                        'logo'          => $hospital->logo ? url('storage/hospital/' . $hospital->logo): null,
                        'is_promoted'   => $hospital->is_promoted,
                    ];
                }),
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
