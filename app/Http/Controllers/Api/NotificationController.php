<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationController extends Controller
{
    /**
     * Get notifications for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $type = strtolower((string) $request->query('type', 'all'));
        $filter = strtolower((string) $request->query('filter', 'all'));

        $request->validate([
            'type' => 'nullable|string|in:all,appointment,appointments,report,reports,reminder,reminders,package,packages',
            'filter' => 'nullable|string|in:all,unread,today,this_week,important',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 20);
        $page = (int) $request->query('page', 1);
        $baseQuery = Notification::where('user_id', $userId);

        $unreadCount = (clone $baseQuery)
            ->where('is_read', false)
            ->count();

        $todayCount = (clone $baseQuery)
            ->whereDate('created_at', today())
            ->count();

        $filteredQuery = (clone $baseQuery);

        $this->applyTypeFilter($filteredQuery, $type);
        $this->applyStatusFilter($filteredQuery, $filter);

        $todayNotifications = (clone $filteredQuery)
            ->whereDate('created_at', today())
            ->latest()
            ->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            )
            ->withQueryString();

        $yesterdayNotifications = (clone $filteredQuery)
            ->whereDate('created_at', today()->subDay())
            ->latest()
            ->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            )
            ->withQueryString();

        $earlierNotifications = (clone $filteredQuery)
            ->whereDate('created_at', '<', today()->subDay())
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
                'today' => $todayCount,
                'reminders' => 5,
            ],
            'data' => [
                'today' => $this->formatNotifications($todayNotifications),
                'yesterday' => $this->formatNotifications($yesterdayNotifications),
                'earlier' => $this->formatNotifications($earlierNotifications),
            ],
            'pagination' => [
                'today' => $this->paginationData($todayNotifications),
                'yesterday' => $this->paginationData($yesterdayNotifications),
                'earlier' => $this->paginationData($earlierNotifications),
            ],
        ]);
    }

    private function formatNotifications(LengthAwarePaginator $notifications)
    {
        return $notifications->getCollection()->map(function ($notification) {
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
            return url('assets/notification-icon/coins.png');
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
            'next_page_url' => $notifications->nextPageUrl(),
            'previous_page_url' => $notifications->previousPageUrl(),
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
            'today' => $query->whereDate('created_at', today()),
            'this_week' => $query->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]),
            'important' => $query->where(function (Builder $importantQuery) {
                $importantQuery
                    ->where('title', 'like', '%important%')
                    ->orWhere('data->important', true)
                    ->orWhere('data->is_important', true)
                    ->orWhere('data->priority', 'important');
            }),
            default => null,
        };
    }

    /**
     * Mark notification as read
     */
    public function markRead($id, Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $notification = Notification::where('id', $id)
            ->where('user_id', $userId )
            ->firstOrFail();

        $shouldMark = true;
        $data = $notification->data ?? [];

        if (isset($data['invoice_id'])) {
            $invoice = Invoice::find((int) $data['invoice_id']);
            if (!$invoice || $invoice->status !== 'completed') {
                $shouldMark = false;
            }
        }

        if ($shouldMark) {
            $notification->update(['is_read' => true]);
            return response()->json([
                'status' => true,
                'message' => 'Notification marked as read'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Payment not yet completed; notification remains unread'
        ]);
    }

    /**
     * Delete a notification
     */
    public function delete($id, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

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
        $userId = $request->user()->id;
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
        $userId = $request->user()->id;
        $count = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'status' => true,
            'unread_count' => $count
        ]);
    }
}
