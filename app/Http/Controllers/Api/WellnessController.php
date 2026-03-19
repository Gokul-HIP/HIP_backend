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

        // Return only types that actually have wellness centres mapped to them.
        // Mapping rule is the same as wellnessList(): wc.centre_type can be the category id (stored as string)
        // or the parent_category string.
        $wellnessTypes = MasterWellnessCategories::query()
            ->select('id', 'parent_category')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('wellness_centres as wc')
                    ->whereIn('wc.status', ['active', 'pending'])
                    ->where(function ($w) {
                        $w->whereRaw('wc.centre_type = CAST(master_wellness_categories.id AS CHAR)')
                            ->orWhereColumn('wc.centre_type', 'master_wellness_categories.parent_category');
                    });
            })
            ->orderBy('id')
            ->get();

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

    /** Format working_days array to "Monday to Saturday" or "Monday to Friday". */
    private static function formatWorkingDays(?array $days): string
    {
        if (empty($days) || !is_array($days)) {
            return '';
        }
        $order = ['monday' => 0, 'tuesday' => 1, 'wednesday' => 2, 'thursday' => 3, 'friday' => 4, 'saturday' => 5, 'sunday' => 6];
        $sorted = collect($days)->filter(fn ($d) => isset($order[strtolower($d)]))->sortBy(fn ($d) => $order[strtolower($d)])->values();
        if ($sorted->isEmpty()) {
            return '';
        }
        $first = ucfirst($sorted->first());
        $last  = ucfirst($sorted->last());
        return $first === $last ? $first : $first . ' to ' . $last;
    }

    /** Format working_hours ['open' => '08:00', 'close' => '19:00'] to "8am to 7pm". */
    private static function formatWorkingHours(?array $hours): string
    {
        if (empty($hours) || !is_array($hours)) {
            return '';
        }
        $open  = $hours['open'] ?? $hours[0] ?? '';
        $close  = $hours['close'] ?? $hours[1] ?? '';
        if ($open === '' && $close === '') {
            return '';
        }
        $format = function ($time) {
            if ($time === '' || $time === null) {
                return '';
            }
            $time = preg_replace('/^(\d{1,2}):(\d{2})/', '$1:$2', (string) $time);
            $parts = explode(':', $time);
            $h = (int) ($parts[0] ?? 0);
            $m = (int) ($parts[1] ?? 0);
            $ampm = $h >= 12 ? 'pm' : 'am';
            $h12 = $h % 12;
            if ($h12 === 0) {
                $h12 = 12;
            }
            return $h12 . ($m > 0 ? ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) : '') . $ampm;
        };
        $openStr  = $format($open);
        $closeStr = $format($close);
        if ($openStr === '' || $closeStr === '') {
            return $openStr ?: $closeStr;
        }
        return $openStr . ' to ' . $closeStr;
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
                ->selectRaw("wc.id, wc.centre_name, wc.centre_type, cat.parent_category as centre_type_name, wc.operating_mode, wc.languages_supported, wc.image, ({$distSql}) AS distance", [$lat, $lng, $lat])
                ->having('distance', '<=', $radius)->orderBy('distance')
                ->paginate($perPage);

            $areasByCentre = [];
            $collection = $centres->getCollection();
            foreach (DB::table('wellness_centres')->whereIn('id', $collection->pluck('id'))->whereNotNull('latitude')->whereNotNull('longitude')->select('id', 'latitude', 'longitude','operating_mode')->get() as $row) {
                $areasByCentre[(int) $row->id] = DB::table('location_masters')->whereNotNull('latitude')->whereNotNull('longitude')
                    ->selectRaw('area, (' . self::haversineSql('latitude', 'longitude') . ') AS d', [$row->latitude, $row->longitude, $row->latitude])
                    ->orderBy('d')->limit(1)->value('area') ?? '';
            }

            $data = $collection->map(fn ($c) => [
                'id'             => (int) $c->id,
                'centre_name'    => $c->centre_name,
                'languages'      => explode(',', $c->languages_supported),
                'centre_type'    => $c->centre_type_name ?? null,
                'operating_mode' => $c->operating_mode,
                'distance_km'    => round((float) $c->distance, 2),
                'center_area'    => $areasByCentre[(int) $c->id] ?? '',
                'image'          => $c->image ? url('storage/wellness-centers/images/' . $c->image) : null,
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

    public function wellnessDetails($id){

        $wellnessCenter = WellnessCenters::with('wellnessCategory')->find($id);
        if(!$wellnessCenter){
            return response()->json([
                'status' => 404,
                'message' => 'Wellness center not found',
            ], 404);
        }
        return response()->json([
            'status' => 200,
            'message' => 'Wellness center details fetched successfully',
            'data' => [
                'id' => $wellnessCenter->id,
                'centre_name' => $wellnessCenter->centre_name,
                'centre_type' => $wellnessCenter->wellnessCategory->parent_category ?? null,
                'image' => $wellnessCenter->image ? url('storage/wellness-centers/images/' . $wellnessCenter->image) : null,
                'languages' => explode(',', $wellnessCenter->languages_supported),
                'age_group_served' => $wellnessCenter->age_group_served,
                'address_line_1' => $wellnessCenter->address_line_1,
                'address_line_2' => $wellnessCenter->address_line_2,
                'working_since' => $wellnessCenter->working_since,
                'working_days' => self::formatWorkingDays($wellnessCenter->working_days ?? []),
                'working_hours' => self::formatWorkingHours($wellnessCenter->working_hours ?? []),
                'city' => $wellnessCenter->city,
                'rating' => '4.5',
                'state' => $wellnessCenter->state,
                'pincode' => $wellnessCenter->pincode,
                'target_audience' => $wellnessCenter->target_audience,
                'about' => $wellnessCenter->description,
                'operating_mode' => $wellnessCenter->operating_mode,
                'center_website' => $wellnessCenter->centre_website,
                'center_instagram_links' => $wellnessCenter->centre_instagram_links,
                'center_facebook_links' => $wellnessCenter->centre_facebook_links,
                'center_linkedin_links' => $wellnessCenter->centre_linkedin_links,
                'center_twitter_links' => $wellnessCenter->centre_twitter_links,
                'center_youtube_links' => $wellnessCenter->centre_youtube_links,
            ],
        ]);
    }

}
