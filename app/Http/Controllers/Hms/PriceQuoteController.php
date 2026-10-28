<?php

namespace App\Http\Controllers\Hms;

use App\Exceptions\HmsClientException;
use App\Services\Hms\HmsHospitalResolver;
use App\Services\Hms\HmsMobile;
use App\Services\Hms\HmsPatientResolver;
use App\Services\Hms\HmsPriceQuoteService;
use App\Services\Hms\HmsResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceQuoteController extends HmsController
{
    public function __invoke(
        Request $request,
        HmsHospitalResolver $hospitals,
        HmsPatientResolver $patients,
        HmsPriceQuoteService $quotes,
    ): JsonResponse {
        $request->attributes->set('hms_event_type', 'price.quote');

        $bookingType = (string) $request->input('booking_type', '');
        $mobile = HmsMobile::normalize((string) $request->input('mobile', ''));
        $hospitalCode = trim((string) $request->input('hospital_code', ''));

        if (! in_array($bookingType, ['doctor', 'second_opinion', 'package'], true)) {
            throw new HmsClientException('validation_error', 'booking_type must be doctor, second_opinion, or package.', 422);
        }
        if ($mobile === '' || $hospitalCode === '') {
            throw new HmsClientException('validation_error', 'mobile and hospital_code are required.', 422);
        }

        $originalFee = $this->originalFee($request);
        $integration = $this->integration($request);
        $hospital = $hospitals->resolve($integration, $hospitalCode);
        $patientRef = $request->input('patient_ref');
        $patientRef = is_string($patientRef) ? trim($patientRef) : '';
        $resolved = $patients->resolve($integration, $mobile, $patientRef !== '' ? $patientRef : null);
        $patientExists = $resolved !== null;

        if (! $patientExists && $patients->findAccount($mobile) !== null) {
            throw new HmsClientException('patient_not_found', 'Patient was not found.', 404);
        }

        $doctor = null;
        $package = null;
        if ($bookingType === 'package') {
            $packageCode = trim((string) $request->input('package_code', ''));
            if ($packageCode === '') {
                throw new HmsClientException('validation_error', 'package_code is required.', 422);
            }
            $package = $quotes->resolvePackage($integration, $packageCode);
        } else {
            $doctorId = trim((string) $request->input('doctor_id', ''));
            if ($doctorId === '') {
                throw new HmsClientException('validation_error', 'doctor_id is required.', 422);
            }
            $doctor = $quotes->resolveDoctor($integration, $doctorId);
        }

        // coins_used is ignored. A quote always prices zero coins.
        // An unknown mobile is priced as the base tier and creates no account.
        return HmsResponse::ok($request, $quotes->quote(
            $patientExists ? (string) $resolved['account']->getKey() : HmsPriceQuoteService::ANONYMOUS_HIP_USER_ID,
            $bookingType,
            $hospital,
            $doctor,
            $package,
            $originalFee,
            $patientExists,
        ));
    }

    private function originalFee(Request $request): ?float
    {
        if (! $request->exists('original_fee') || $request->input('original_fee') === null || $request->input('original_fee') === '') {
            return null;
        }

        if (! is_numeric($request->input('original_fee')) || (float) $request->input('original_fee') < 0) {
            throw new HmsClientException('validation_error', 'original_fee must be a non-negative number of rupees.', 422);
        }

        return round((float) $request->input('original_fee'), 2);
    }
}
