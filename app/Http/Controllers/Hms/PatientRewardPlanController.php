<?php

namespace App\Http\Controllers\Hms;

use App\Exceptions\HmsClientException;
use App\Services\Hms\HmsHospitalResolver;
use App\Services\Hms\HmsMobile;
use App\Services\Hms\HmsPatientResolver;
use App\Services\Hms\HmsResponse;
use App\Services\Hms\HmsRewardPlanReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientRewardPlanController extends HmsController
{
    public function __invoke(
        Request $request,
        HmsHospitalResolver $hospitals,
        HmsPatientResolver $patients,
        HmsRewardPlanReader $reader,
    ): JsonResponse {
        $request->attributes->set('hms_event_type', 'patient.reward_plan.read');

        $mobile = HmsMobile::normalize((string) $request->query('mobile', ''));
        $hospitalCode = trim((string) $request->query('hospital_code', ''));

        if ($mobile === '' || $hospitalCode === '') {
            throw new HmsClientException('validation_error', 'mobile and hospital_code are required.', 422);
        }

        $integration = $this->integration($request);
        $hospitals->resolve($integration, $hospitalCode);
        $patientRef = $request->query('patient_ref');
        $patientRef = is_string($patientRef) ? trim($patientRef) : '';
        $resolved = $patients->resolve($integration, $mobile, $patientRef !== '' ? $patientRef : null);

        if ($resolved === null) {
            throw new HmsClientException('patient_not_found', 'Patient was not found.', 404);
        }

        return HmsResponse::ok($request, $reader->forPatient($resolved['account'], $resolved['person']));
    }
}
