<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationMaster;
use Illuminate\Http\Request;
use App\Models\MasterWellnessCategories;
use App\Models\WellnessCenters;
use Illuminate\Support\Facades\DB;

class WellnessController extends Controller
{
    
    public function wellnessTypes(){

        $wellnessTypes = MasterWellnessCategories::select('id', 'parent_category')->get();

        return response()->json([
            'status' => 200,
            'message' => 'Wellness types fetched successfully',
            'data' => $wellnessTypes->map(function ($type) {
                return [
                    'id' => $type->id,
                    'parent_category' => $type->parent_category,
                ];
            }),
        ], 200);

    }

    /** Haversine (km), LEAST/GREATEST to avoid acos domain errors. Placeholders: lat, lng, lat. */
    private static function haversineSql(string $latCol, string $lngCol): string
    {
        return "6371 * acos(LEAST(1.0, GREATEST(-1.0,
            cos(radians(?)) * cos(radians({$latCol})) * cos(radians({$lngCol}) - radians(?))
            + sin(radians(?)) * sin(radians({$latCol}))
        )))";
    }

    public function wellnessList(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'id' => 'nullable|integer',
            'per_page'  => 'nullable|integer|min:1|max:50',
        ]);

        $lat = (float) $request->latitude;
        $lng = (float) $request->longitude;
        $perPage = (int) ($request->per_page ?? 10);
        $radius = 15;

        try {
            $nearestArea = LocationMaster::whereNotNull('latitude')->whereNotNull('longitude')
                ->selectRaw('area, (' . self::haversineSql('latitude', 'longitude') . ') AS distance', [$lat, $lng, $lat])
                ->orderBy('distance')->first();

            $distSql = self::haversineSql('wc.latitude', 'wc.longitude');
            $centres = DB::table('wellness_centres as wc')
                ->join('master_wellness_categories as cat', fn ($j) => $j->on(DB::raw('wc.centre_type'), '=', DB::raw('CAST(cat.id AS CHAR)'))->orOn('wc.centre_type', '=', 'cat.parent_category'))
                ->when($request->id ?? null, function ($query) use ($request) {
                    $query->where('cat.id', $request->id ?? null);
                })
                ->whereIn('wc.status', ['active', 'pending'])
                ->whereNotNull('wc.latitude')->whereNotNull('wc.longitude')
                ->selectRaw("wc.id, wc.centre_name, wc.centre_type, wc.languages_supported, wc.image, ({$distSql}) AS distance", [$lat, $lng, $lat])
                ->having('distance', '<=', $radius)->orderBy('distance')
                ->paginate($perPage);

            $areasByCentre = [];
            $collection = $centres->getCollection();
            foreach (DB::table('wellness_centres')->whereIn('id', $collection->pluck('id'))->whereNotNull('latitude')->whereNotNull('longitude')->select('id', 'latitude', 'longitude')->get() as $row) {
                $areasByCentre[(int) $row->id] = DB::table('location_masters')->whereNotNull('latitude')->whereNotNull('longitude')
                    ->selectRaw('area, (' . self::haversineSql('latitude', 'longitude') . ') AS d', [$row->latitude, $row->longitude, $row->latitude])
                    ->orderBy('d')->limit(1)->value('area') ?? '';
            }

            $data = $collection->map(fn ($c) => [
                'id' => (int) $c->id,
                'centre_name' => $c->centre_name,
                'languages' => explode(',', $c->languages_supported),
                'centre_type' => (int) $c->centre_type,
                'distance_km' => round((float) $c->distance, 2),
                'center_area' => $areasByCentre[(int) $c->id] ?? '',
                'image' => $c->image ? url('storage/wellness-centers/images/' . $c->image) : null,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['status' => 500, 'message' => 'Error fetching wellness centres.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Nearby wellness centers fetched successfully',
            'current_area' => $nearestArea?->area,
            'data' => $data,
            'pagination' => ['current_page' => $centres->currentPage(), 'per_page' => $centres->perPage(), 'total' => $centres->total(), 'last_page' => $centres->lastPage()],
        ]);
    }

}
