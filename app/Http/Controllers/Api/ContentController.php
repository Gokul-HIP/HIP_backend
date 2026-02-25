<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentComment;
use App\Models\ContentLike;
use App\Models\ContentModeration;
use App\Models\ContentView;
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
        $memberId      = Auth::id();

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
                ->where('is_published', true)
                ->withExists([
                    'likes as is_liked' => function ($q) use ($memberId) {
                        $q->where('member_id', $memberId);
                    }
                ]);

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
                'hospital_id'   => $content->hospital?->id,
                'hospital_name' => $content->hospital?->name,
                'hospital_logo' => $content->hospital?->logo ? url('storage/hospital/' . $content->hospital?->logo) : null,
                'description'   => $content->description,
                'media_url'     => $content->media_file ? url('storage/' . $content->media_file) : null,
                'like_count'    => (int) $content->like_count,
                'view_count'    => (int) $content->view_count,
                'comment_count' => (int) $content->comment_count,
                'created_at'    => $content->created_at?->toIso8601String(),
                'is_liked'      => $content->is_liked,
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
        $memberId = Auth::id();
        $liked = false;

        DB::transaction(function () use (&$liked, $memberId, $content) {

            $content = ContentModeration::where('id', $content->id)
                ->lockForUpdate()
                ->first();

            $existingLike = ContentLike::where('content_id', $content->id)
                ->where('member_id', $memberId)
                ->lockForUpdate()
                ->first();

            if ($existingLike) {
                $existingLike->delete();

                if ($content->like_count > 0) {
                    $content->decrement('like_count');
                }

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
            'status'  => 200,
            'message' => $liked ? 'Liked successfully' : 'Unliked successfully',
            'data'    => [
                'liked'      => $liked,
                'like_count' => (int) $content->fresh()->like_count,
            ],
        ]);
    }

    public function addComment(Request $request, ContentModeration $content)
    {
        $request->validate([
            'comment' => 'required|string|max:500',
        ]);

        $memberId = Auth::id();

        DB::transaction(function () use ($request, $content, $memberId) {

            ContentComment::create([
                'content_id' => $content->id,
                'member_id'  => $memberId,
                'comment'    => $request->comment,
                'status'     => 'active',
            ]);

            $content->increment('comment_count');
        });

        return response()->json([
            'status'  => 200,
            'message' => 'Comment added successfully',
            'data'    => [
                'comment_count' => (int) $content->fresh()->comment_count,
            ],
        ]);
    }

    public function getComments(ContentModeration $content)
    {
        $comments = ContentComment::where('content_id', $content->id)
            ->where('status', 'active')
            ->with('member')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([   
            'status' => 200,
            'message' => 'Comments fetched successfully',
            'data'    => $comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'commenter_name' => $comment->member?->name,
                    'commenter_image' => $comment->member?->profile_image ? url('storage/profile/' . $comment->member?->profile_image) : null,
                    'comment' => $comment->comment,
                    'created_at' => $comment->created_at?->toIso8601String(),
                ];
            }),
            'total' => $comments->total(),
            'per_page' => $comments->perPage(),
            'current_page' => $comments->currentPage(),
            'last_page' => $comments->lastPage(),
        ]);
    }

    public function addView(Request $request, ContentModeration $content)
    {
        $memberId = Auth::id();
        $deviceId = $request->header('X-Device-Id');

        if (!$deviceId) {
            return response()->json([
                'status' => 422,
                'message' => 'Device ID is required',
            ], 422);
        }

        DB::transaction(function () use ($content, $memberId, $deviceId) {

            $viewExists = ContentView::where('content_id', $content->id)
                ->where(function ($q) use ($memberId, $deviceId) {
                    if ($memberId) {
                        $q->where('member_id', $memberId)
                        ->where('device_id', $deviceId);
                    } else {
                        $q->where('device_id', $deviceId);
                    }
                })
                ->lockForUpdate()
                ->exists();

            if (!$viewExists) {
                ContentView::create([
                    'content_id' => $content->id,
                    'member_id'  => $memberId,
                    'device_id'  => $deviceId,
                ]);

                $content->increment('view_count');
            }
        });

        return response()->json([
            'status' => 200,
            'message' => 'View recorded',
            'data' => [
                'view_count' => (int) $content->fresh()->view_count,
            ],
        ]);
    }

    public function deleteComment(ContentModeration $content, ContentComment $comment)
    {
        $userId = Auth::id();
    
        if ($comment->content_id !== $content->id) {
            return response()->json([
                'status'  => 404,
                'message' => 'Comment not found for this content',
            ], 404);
        }
    
        if ($comment->member_id !== $userId && !Auth::user()->hasRole('admin')) {
            return response()->json([
                'status'  => 403,
                'message' => 'You are not allowed to delete this comment',
            ], 403);
        }
    
        DB::transaction(function () use ($comment, $content) {
    
            $comment->delete();
    
            if ($content->comment_count > 0) {
                $content->decrement('comment_count');
            }
        });
    
        return response()->json([
            'status'  => 200,
            'message' => 'Comment deleted successfully',
        ]);
    }

}