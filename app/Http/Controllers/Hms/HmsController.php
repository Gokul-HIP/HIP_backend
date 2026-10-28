<?php

namespace App\Http\Controllers\Hms;

use App\Exceptions\HmsClientException;
use App\Http\Controllers\Controller;
use App\Models\HmsIntegration;
use Illuminate\Http\Request;

/**
 * Shared base for HMS client reads and the later booking/payment webhooks.
 * Webhook controllers should claim a hms_webhook_inbox row before doing work.
 */
abstract class HmsController extends Controller
{
    protected function integration(Request $request): HmsIntegration
    {
        $integration = $request->attributes->get('hms_integration');

        if (! $integration instanceof HmsIntegration) {
            throw new HmsClientException('invalid_signature', 'Authentication is required.', 401);
        }

        return $integration;
    }
}
