<?php

namespace App\Http\Controllers\Hms;

use App\Exceptions\HmsClientException;
use App\Http\Controllers\Controller;
use App\Services\Hms\HmsPatientMembershipService;
use App\Services\Hms\HmsResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientMembershipController extends Controller
{
    public function __invoke(Request $request, HmsPatientMembershipService $membership): JsonResponse
    {
        $request->attributes->set('hms_event_type', 'patient.membership.read');

        $mrnId = trim((string) $request->query('mrn_id', ''));
        if ($mrnId === '') {
            throw new HmsClientException('validation_error', 'mrn_id is required.', 422);
        }

        $mobile = $request->query('mobile');
        $payload = $membership->lookup($mrnId, is_string($mobile) ? $mobile : null);

        if ($payload === null) {
            return response()->json([
                'status' => false,
                'error' => 'patient_not_found',
            ], 404)->header('X-Request-Id', HmsResponse::requestId($request));
        }

        return HmsResponse::ok($request, $payload);
    }
}
