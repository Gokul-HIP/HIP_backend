<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Get notifications for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $notifications = Notification::where('user_id', $userId)
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => true,
            'message' => 'Notifications fetched successfully',
            'data' => $notifications->getCollection()->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'user_id' => $notification->user_id,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->toDateTimeString(),
                    'data' => [
                        'type' => $notification->data['type'] ?? null,
                        'route' => $notification->data['route'] ?? null,
                        'invoice_id' => $notification->data['invoice_id'] ?? null,
                        'amount' => $notification->data['amount'] ?? null,
                        'person_name' => $notification->data['person_name'] ?? null,
                        'service_types' => $notification->data['service_types'] ?? null,
                        'payment_screen' => $notification->data['screen'] ?? null,
                        'invoice_token' => $notification->data['invoice_token'] ?? null,
                        'url' => $notification->data['url'] ?? null,
                    ],
                ];
            }),
            'total' => $notifications->total(),
            'per_page' => $notifications->perPage(),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
        ]);
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

        $notification->update([
            'is_read' => true
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read'
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
