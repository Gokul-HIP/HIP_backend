<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationController extends Controller
{
    /**
     * Get notifications for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $type = strtolower((string) $request->query('type', 'all'));
        $filter = strtolower((string) $request->query('filter', 'all'));

        $request->validate([
            'type' => 'nullable|string|in:all,appointment,appointments,report,reports,reminder,reminders,package,packages',
            'filter' => 'nullable|string|in:all,unread,read,today,this_week,this_month,last_month',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 20);
        $page = (int) $request->query('page', 1);
        $baseQuery = Notification::where('user_id', $userId);

        $unreadCount = (clone $baseQuery)
            ->where('is_read', false)
            ->count();

        $readCount = (clone $baseQuery)
            ->where('is_read', true)
            ->count();

        $todayCount = (clone $baseQuery)
            ->whereDate('created_at', today())
            ->count();

        $filteredQuery = (clone $baseQuery);

        $this->applyTypeFilter($filteredQuery, $type);
        $this->applyStatusFilter($filteredQuery, $filter);

        $notifications = (clone $filteredQuery)
            ->latest()
            ->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            )
            ->withQueryString();

        return response()->json([
            'status' => true,
            'message' => 'Notifications fetched successfully',
            'counts' => [
                'unread' => $unreadCount,
                // 'read' => $readCount,
                'today' => $todayCount,
                'reminders' => 5,
            ],
            'data' => $this->groupNotificationsByPeriod($notifications->getCollection()),
            'pagination' => $this->paginationData($notifications),
        ]);
    }

    /**
     * Group a page of notifications into today, yesterday, and date labels.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function groupNotificationsByPeriod(Collection $notifications): array
    {
        $todayItems = collect();
        $yesterdayItems = collect();
        $datedItems = collect();

        foreach ($notifications as $notification) {
            if ($notification->created_at->isToday()) {
                $todayItems->push($notification);
            } elseif ($notification->created_at->isYesterday()) {
                $yesterdayItems->push($notification);
            } else {
                $dateKey = $notification->created_at->toDateString();
                if (! $datedItems->has($dateKey)) {
                    $datedItems->put($dateKey, collect());
                }
                $datedItems->get($dateKey)->push($notification);
            }
        }

        $data = [];

        if ($todayItems->isNotEmpty()) {
            $data['today'] = $this->formatNotificationCollection($todayItems)->values()->all();
        }

        if ($yesterdayItems->isNotEmpty()) {
            $data['yesterday'] = $this->formatNotificationCollection($yesterdayItems)->values()->all();
        }

        $datedItems
            ->sortKeysDesc()
            ->each(function (Collection $items, string $date) use (&$data) {
                $label = Carbon::parse($date)->format('j F');
                $data[$label] = $this->formatNotificationCollection($items)->values()->all();
            });

        return $data;
    }

    private function formatNotificationCollection(Collection $notifications): Collection
    {
        return $notifications->map(function (Notification $notification) {
            $data = $notification->data ?? [];

            if (($data['type'] ?? null) === 'review_popup') {
                $data = [
                    'id' => $notification->id,
                    'user_id' => $notification->user_id,
                    'title' => $notification->title,
                    'type' => $data['type'] ?? null,
                    'url' => $data['url'] ?? null,
                    'route' => $data['route'] ?? null,
                    'body' => $notification->body,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->toDateTimeString(),
                    'icon' => $this->notificationIcon($notification->title, $data),
                    // 'data' => $data,
                    // 'entity_type' => $data['entity_type'] ?? null,
                    // 'entity_id' => $data['entity_id'] ?? null,
                ];
            }

            return [
                'id' => $notification->id,
                'user_id' => $notification->user_id,
                'title' => $notification->title,
                'type' => $data['type'] ?? null,
                'url' => $data['url'] ?? null,
                'route' => $data['route'] ?? null,
                'body' => $notification->body,
                'is_read' => $notification->is_read,
                'created_at' => $notification->created_at->toDateTimeString(),
                'icon' => $this->notificationIcon($notification->title, $data),
                // 'data' => $data,
            ];
        })->values();
    }

    private function notificationIcon(string $title, array $data): ?string
    {
        $title = strtolower($title);
        $type = strtolower((string) ($data['type'] ?? ''));
        $context = strtolower((string) ($data['context'] ?? ''));
        
        if(
            str_contains($type, 'review_popup') && str_contains($title, 'appointment')
        ) {
            return url('assets/notification-icon/rating.png');
        }

        if (str_contains($title, 'coin') || $type === 'coins_earned') {
            return url('assets/notification-icon/coins.webp');
        }

        if (
            str_contains($title, 'package')
            || str_contains($type, 'package')
            || str_contains($context, 'package')
        ) {
            return url('assets/notification-icon/calendar.png');
        }

        if (
            str_contains($title, 'appointment')
            || str_contains($type, 'appointment')
            || str_contains($context, 'appointment')
        ) {
            return url('assets/notification-icon/calendar.png');
        }

        return null;
    }

    private function paginationData(LengthAwarePaginator $notifications): array
    {
        return [
            'total' => $notifications->total(),
            'per_page' => $notifications->perPage(),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            // 'next_page_url' => $notifications->nextPageUrl(),
            // 'previous_page_url' => $notifications->previousPageUrl(),
        ];
    }

    private function applyTypeFilter(Builder $query, string $type): void
    {
        $keyword = match ($type) {
            'appointment', 'appointments' => 'appointment',
            'report', 'reports' => 'report',
            'reminder', 'reminders' => 'reminder',
            'package', 'packages' => 'package',
            default => null,
        };

        if ($keyword !== null) {
            $query->where('title', 'like', "%{$keyword}%");
        }
    }

    private function applyStatusFilter(Builder $query, string $filter): void
    {
        match ($filter) {
            'unread' => $query->where('is_read', false),
            'read' => $query->where('is_read', true),
            'today' => $query->whereDate('created_at', today()),
            'this_week' => $query->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]),
            'this_month' => $query->whereBetween('created_at', [
                now()->copy()->startOfMonth(),
                now()->copy()->endOfMonth(),
            ]),
            'last_month' => $query->whereBetween('created_at', [
                now()->copy()->subMonth()->startOfMonth(),
                now()->copy()->subMonth()->endOfMonth(),
            ]),
            default => null,
        };
    }

    /**
     * Mark one or more notifications as read.
     */
    public function markRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|distinct',
        ]);

        $userId = (string) $request->user()->id;
        $requestedIds = array_map('intval', $validated['ids']);

        $notifications = Notification::query()
            ->where('user_id', $userId)
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy(fn (Notification $notification) => (int) $notification->id);

        $markedIds = [];
        $skippedIds = [];

        foreach ($requestedIds as $id) {
            $notification = $notifications->get($id);

            if (! $notification) {
                $skippedIds[] = $id;
                continue;
            }

            $markedIds[] = $id;
        }

        if ($markedIds !== []) {
            Notification::where('user_id', $userId)
                ->whereIn('id', $markedIds)
                ->update(['is_read' => true]);
        }

        $markedCount = count($markedIds);
        $skippedCount = count($skippedIds);

        if ($markedCount === 0) {
            return response()->json([
                'status' => false,
                'message' => $skippedCount === 1
                    ? 'Notification could not be marked as read'
                    : 'Notifications could not be marked as read',
                'marked_ids' => [],
                'skipped_ids' => $skippedIds,
                'skipped_reason' => 'not_found_for_user',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => $markedCount === 1
                ? 'Notification marked as read'
                : "{$markedCount} notifications marked as read",
            'marked_ids' => $markedIds,
            'skipped_ids' => $skippedIds,
        ]);
    }

    /**
     * Delete a notification
     */
    public function delete($id, Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;

        Notification::where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'status' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Clear all notifications
     */
    public function clearAll(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        Notification::where('user_id', $userId)->delete();

        return response()->json([
            'status' => true,
            'message' => 'All notifications cleared'
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $count = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'status' => true,
            'unread_count' => $count
        ]);
    }
}
