<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
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

        $notifications = Notification::where('user_id', $userId)->where('is_read', false)
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
                    'data' => (function ($d) {
                        $d = $d ?? [];
                        if (isset($d['type']) && $d['type'] === 'review_popup') {
                            return [
                                'type' => $d['type'],
                                'entity_type' => $d['entity_type'] ?? null,
                                'entity_id' => $d['entity_id'] ?? null,
                            ];
                        }
                        return $d;
                    })($notification->data),
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
