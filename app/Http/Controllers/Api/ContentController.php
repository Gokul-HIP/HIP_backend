<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentLike;
use App\Models\ContentModeration;
use App\Models\LocationMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{

    public function contentList(Request $request)
    {
        $request->validate([
            'latitude'  => 'nullable|string',
            'longitude' => 'nullable|string'
        ]);

        $lat     = $request->latitude;
        $lng     = $request->longitude;

        $userAreaId    = null;
        $nearbyAreaIds = [];

        if ($lat !== null && $lng !== null) {
            $userArea = LocationMaster::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->selectRaw("
                    id,
                    (6371 * acos(
                        LEAST(1, GREATEST(-1,
                            cos(radians(?))
                            * cos(radians(latitude))
                            * cos(radians(longitude) - radians(?))
                            + sin(radians(?))
                            * sin(radians(latitude))
                        ))
                    )) AS distance
                ", [$lat, $lng, $lat])
                ->orderBy('distance')
                ->first();

            $userAreaId = $userArea?->id;

            if ($userAreaId) {
                $nearbyAreaIds = LocationMaster::whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->selectRaw("
                        id,
                        (6371 * acos(
                            LEAST(1, GREATEST(-1,
                                cos(radians(?))
                                * cos(radians(latitude))
                                * cos(radians(longitude) - radians(?))
                                + sin(radians(?))
                                * sin(radians(latitude))
                            ))
                        )) AS distance
                    ", [$lat, $lng, $lat])
                    ->having('distance', '<=', 10)
                    ->pluck('id')
                    ->toArray();
            }
        }

        $query = ContentModeration::query()
            ->where('status', 'active')
            ->where('is_published', true);

        if ($userAreaId) {

            $nearbySql = count($nearbyAreaIds)
                ? implode(',', $nearbyAreaIds)
                : '0';

            $query->selectRaw("
                content_moderations.*,
                CASE
                    WHEN EXISTS (
                        SELECT 1 FROM content_target_areas cta
                        WHERE cta.content_id = content_moderations.id
                        AND cta.location_master_id = {$userAreaId}
                    ) THEN 0

                    WHEN EXISTS (
                        SELECT 1 FROM content_target_areas cta
                        WHERE cta.content_id = content_moderations.id
                        AND cta.location_master_id IN ({$nearbySql})
                    ) THEN 1

                    ELSE 2
                END AS area_priority
            ")
            ->orderBy('area_priority')
            ->orderByDesc('created_at');

        } else {
            $query->orderByDesc('created_at');
        }

        $contents = $query->get();

        $data = $contents->map(function ($content) {
            return [
                'id'            => $content->id,
                // 'title'         => $content->title,
                'hospital_name' => $content->hospital?->name,
                'hospital_logo' => $content->hospital?->logo ? url('storage/hospital/' . $content->hospital?->logo) : null,
                'description'   => $content->description,
                'media_url'     => $content->media_file ? url('storage/' . $content->media_file) : null,
                'like_count'    => (int) $content->like_count,
                'view_count'    => (int) $content->view_count,
                'comment_count' => (int) $content->comment_count,
                'created_at'    => $content->created_at?->toIso8601String(),
                // Uncomment for debugging
                // 'area_priority' => $content->area_priority ?? null,
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Reels fetched successfully',
            'meta' => [
                'user_area_id' => $userAreaId,
            ],
            'data' => $data
        ]);
    }

    public function toggleLike(ContentModeration $content)
    {

        $memberId = Auth::user()->id;

        DB::transaction(function () use ($content, $memberId, &$liked) {

            $existingLike = ContentLike::where('content_id', $content->id)
                ->where('member_id', $memberId)
                ->lockForUpdate()
                ->first();

            if ($existingLike) {
                $existingLike->delete();

                $content->decrement('like_count');

                $liked = false;
            } else {
                ContentLike::create([
                    'content_id' => $content->id,
                    'member_id'  => $memberId,
                ]);

                $content->increment('like_count');

                $liked = true;
            }
        });

        return response()->json([
            'status' => 200,
            'message' => $liked ? 'Liked successfully' : 'Unliked successfully',
            'data' => [
                'liked' => $liked,
                'like_count' => (int) $content->fresh()->like_count,
            ],
        ]);
    }

}