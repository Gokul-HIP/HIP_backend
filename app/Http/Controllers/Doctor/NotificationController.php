<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\DoctorNotificationService;
use App\Support\CurrentDoctor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user('filament')?->id;

        if (! $userId) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        $limit = min((int) $request->query('limit', 20), 50);

        $notifications = $this->notificationQueryForDoctor($request)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Notification $notification) => $this->formatNotification($notification))
            ->values();

        return response()->json([
            'status' => true,
            'unread_count' => $this->unreadCountForDoctor($request),
            'data' => $notifications,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user('filament')?->id;

        if (! $userId) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'status' => true,
            'unread_count' => $this->unreadCountForDoctor($request),
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $userId = $request->user('filament')?->id;

        if (! $userId) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        $notification = $this->notificationQueryForDoctor($request)
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return response()->json(['status' => false, 'message' => 'Notification not found'], 404);
        }

        if (! $notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        return response()->json([
            'status' => true,
            'unread_count' => $this->unreadCountForDoctor($request),
            'notification' => $this->formatNotification($notification->fresh()),
        ]);
    }

    protected function notificationQueryForDoctor(Request $request): Builder
    {
        $userId = $request->user('filament')?->id;
        $doctorId = CurrentDoctor::resolveDoctorId();
        $linkedUserIds = CurrentDoctor::linkedUserIds($doctorId);

        $query = Notification::query();

        if ($linkedUserIds !== []) {
            $query->whereIn('user_id', $linkedUserIds);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        return DoctorNotificationService::applyDoctorPortalScope($query);
    }

    protected function unreadCountForDoctor(Request $request): int
    {
        return $this->notificationQueryForDoctor($request)
            ->where('is_read', false)
            ->count();
    }

    protected function unreadCountForUser(string $userId): int
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    protected function formatNotification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'body' => $notification->body,
            'data' => $notification->data ?? [],
            'is_read' => (bool) $notification->is_read,
            'created_at' => $notification->created_at?->toIso8601String(),
            'time_label' => $this->timeLabel($notification->created_at),
        ];
    }

    protected function timeLabel(?Carbon $createdAt): string
    {
        if (! $createdAt) {
            return '—';
        }

        if ($createdAt->isToday()) {
            return $createdAt->diffForHumans(null, true).' ago';
        }

        if ($createdAt->isYesterday()) {
            return 'Yesterday, '.$createdAt->format('g:i A');
        }

        return $createdAt->format('d M Y, g:i A');
    }
}
