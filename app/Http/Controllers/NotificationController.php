<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    protected $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function saveFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_type' => 'required|string',
            'device_id' => 'required|string'
        ]);

        $this->service->saveToken(
          $request->user()->id,
            $request->fcm_token,
            $request->device_type,
            $request->device_id
        );

        return response()->json(['message' => 'Token stored']);
    }

    public function sendNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'data' => 'nullable|array',
            'device_id' => 'required|string'
        ]);

        $this->service->sendToDevice(
            $request->user()->id,
            $request->device_id,
            $request->title,
            $request->body,
            $request->data
        );

        return response()->json(['message' => 'Notification sent']);
    }
}