<?php

namespace App\Http\Controllers\Hms;

use App\Exceptions\HmsClientException;
use App\Models\HmsBooking;
use App\Services\Hms\HmsBookingService;
use App\Services\Hms\HmsCancellationService;
use App\Services\Hms\HmsResponse;
use App\Services\Hms\HmsMobile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HmsBookingController extends HmsController
{
    public function appointment(Request $request, HmsBookingService $bookings): JsonResponse
    {
        $this->requireEventId($request);
        $payload = $request->all();
        $type = (string) ($payload['appointment_type'] ?? '');
        if (! in_array($type, [HmsBooking::TYPE_DOCTOR, HmsBooking::TYPE_SECOND_OPINION], true)) {
            throw new HmsClientException('validation_error', 'appointment_type must be doctor or second_opinion.', 422);
        }

        $this->requireFields($payload, ['hms_booking_id', 'hospital_code', 'doctor_id', 'appointment_date', 'slot_time', 'original_fee', 'payable_amount']);
        $this->requireDate($payload['appointment_date'], 'appointment_date');
        $this->requireTime($payload['slot_time'], 'slot_time');
        $this->requireMoney($payload);
        $this->requirePatient($payload);

        $request->attributes->set('hms_event_type', 'appointment.booked');

        return $this->respond($request, $bookings->book($this->integration($request), $type, $payload));
    }

    public function diagnostic(Request $request, HmsBookingService $bookings): JsonResponse
    {
        $this->requireEventId($request);
        $payload = $request->all();
        $this->requireFields($payload, ['hms_booking_id', 'hospital_code', 'package_code', 'original_fee', 'payable_amount']);
        if (filled($payload['appointment_date'] ?? null)) {
            $this->requireDate($payload['appointment_date'], 'appointment_date');
        }
        if (filled($payload['slot_time'] ?? null)) {
            $this->requireTime($payload['slot_time'], 'slot_time');
        }
        $this->requireMoney($payload);
        $this->requirePatient($payload);
        $request->attributes->set('hms_event_type', 'diagnostic.booked');

        return $this->respond($request, $bookings->book($this->integration($request), HmsBooking::TYPE_PACKAGE, $payload));
    }

    public function labTest(Request $request): JsonResponse
    {
        $this->requireEventId($request);
        $request->attributes->set('hms_event_type', 'labtest.booked');

        throw new HmsClientException('not_supported', 'Lab test bookings are not supported yet.', 501);
    }

    public function cancelled(Request $request, HmsCancellationService $cancellations): JsonResponse
    {
        $this->requireEventId($request);
        $request->attributes->set('hms_event_type', 'booking.cancelled');
        $result = $cancellations->cancelBooking($this->integration($request), $request->all());

        return response()
            ->json($result['body'], $result['http_status'])
            ->header('X-Request-Id', HmsResponse::requestId($request));
    }

    /**
     * @param  array{http_status: int, body: array<string, mixed>}  $result
     */
    private function respond(Request $request, array $result): JsonResponse
    {
        return response()
            ->json($result['body'], $result['http_status'])
            ->header('X-Request-Id', \App\Services\Hms\HmsResponse::requestId($request));
    }

    private function requireEventId(Request $request): void
    {
        if (trim((string) $request->headers->get('X-HMS-Event-Id', '')) === '') {
            throw new HmsClientException('missing_event_id', 'X-HMS-Event-Id is required.', 422);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $fields
     */
    private function requireFields(array $payload, array $fields): void
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                throw new HmsClientException('validation_error', $field.' is required.', 422);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requireMoney(array $payload): void
    {
        foreach (['original_fee', 'payable_amount'] as $field) {
            if (! is_numeric($payload[$field]) || (float) $payload[$field] < 0) {
                throw new HmsClientException('validation_error', $field.' must be a non-negative number of rupees.', 422);
            }
        }
    }

    private function requireDate(mixed $value, string $field): void
    {
        $date = \DateTime::createFromFormat('Y-m-d', (string) $value);
        if (! $date || $date->format('Y-m-d') !== (string) $value) {
            throw new HmsClientException('validation_error', $field.' must be Y-m-d.', 422);
        }
    }

    private function requireTime(mixed $value, string $field): void
    {
        $time = \DateTime::createFromFormat('H:i', (string) $value);
        if (! $time || $time->format('H:i') !== (string) $value) {
            throw new HmsClientException('validation_error', $field.' must be H:i.', 422);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requirePatient(array $payload): void
    {
        $patient = $payload['patient'] ?? null;
        if (! is_array($patient)) {
            throw new HmsClientException('validation_error', 'patient is required.', 422);
        }

        $mobile = HmsMobile::normalize((string) ($patient['mobile'] ?? ''));
        if ($mobile === '' || trim((string) ($patient['name'] ?? '')) === '') {
            throw new HmsClientException('validation_error', 'patient mobile and name are required.', 422);
        }

        $relationship = trim((string) ($patient['relationship'] ?? 'Self'));
        $isDependent = strcasecmp($relationship, 'Self') !== 0;
        if ($isDependent && trim((string) ($patient['patient_ref'] ?? '')) === '') {
            throw new HmsClientException('patient_ref_required', 'patient_ref is required for a dependent.', 422);
        }
        if ($isDependent && HmsMobile::normalize((string) ($patient['primary_mobile'] ?? '')) === '') {
            throw new HmsClientException('validation_error', 'primary_mobile is required for a dependent.', 422);
        }

        $consultation = strtolower((string) ($payload['consultation_type'] ?? 'in_person'));
        if ($consultation !== '' && ! in_array($consultation, ['in_person', 'online'], true)) {
            throw new HmsClientException('validation_error', 'consultation_type must be in_person or online.', 422);
        }
    }
}
