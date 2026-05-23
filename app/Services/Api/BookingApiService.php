<?php

namespace App\Services\Api;

use App\Models\CareGiver;
use App\Models\CaregiverBooking;
use App\Models\DiagnosticPackage;
use App\Models\DiagnosticLabTest;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Models\StemCellBooking;
use App\Models\DiagnosticTestBooking;
use App\Models\Doctor;
use App\Models\WellnessBooking;
use App\Models\DoctorBooking;
use App\Models\ProcedureBooking;
use App\Models\WellnessCenters;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;

class BookingApiService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService){
        $this->notificationService = $notificationService;
    }

    public function wellnessList(){

        $wellnssCenters = WellnessCenters::with('wellnessCategory')->select('id','centre_name','centre_type')->paginate(10);
        return $wellnssCenters;

    }

    public function wellnessDetails($id){

        $wellnessCenter = WellnessCenters::with('wellnessCategory')
            ->select('id', 'centre_name', 'centre_type', 'address_line_1', 'address_line_2', 'city', 'state', 'pincode', 'latitude', 'longitude', 'contact_person_name', 'contact_person_mobile', 'contact_person_email', 'centre_website', 'centre_instagram_links', 'centre_facebook_links', 
            'centre_linkedin_links', 'centre_twitter_links', 'centre_youtube_links')->find($id);

        return $wellnessCenter;    

    }

    public function procedureBooking($request, $memberId = null){

        $procedureBooking = ProcedureBooking::create([
            'name' => $request->name,
            'mobile_number' => $request->mobile_number,
            'message' => $request->message ?? null,
            'procedure_id' => $request->procedure_id,
            'hospital_id' => $request->hospital_id,
            'member_id' => $memberId,
            'booking_date' => $request->booking_date,
            'required_time_slots' => $request->required_time_slots,
            'status' => 'pending',
        ]);
        
        return $procedureBooking;

    }

    public function doctorBooking($request, $memberId = null){

        $hospitalId = Doctor::find($request->doctor_id);

        $doctorBooking = DoctorBooking::create([
            'name' => $request->name,
            'mobile_number' => $request->mobile_number,
            'member_id' => $memberId,
            'hospital_id' => $hospitalId->hospital_ids ? $hospitalId->hospital_ids[0] : null,
            'doctor_id' => $request->doctor_id,
            'booking_date' => $request->booking_date,
            'required_time_slots' => $request->required_time_slots,
            'purpose' => $request->purpose,
        ]);

        return $doctorBooking;

    }

    public function wellnessBooking($request, $memberId = null){

        $wellnessBooking = WellnessBooking::create([
            'name' => $request->name,
            'mobile_number' => $request->mobile_number,
            'member_id' => $memberId,
            'center_id' => $request->center_id,
            'consultation_type' => "In-Person",
            'purpose' => $request->purpose ?? null,
        ]);

        return $wellnessBooking;

    }

    /**
     * Resolve patient name and mobile from HIP user or dependent (persons) record.
     */
    public function resolvePatientDetails(string $patientUuid): ?array
    {
        $hipUser = HIPUser::query()->find($patientUuid);

        if ($hipUser) {
            $name = trim(($hipUser->first_name ?? '') . ' ' . ($hipUser->last_name ?? ''));

            // patient_id FK references persons — use linked primary person when booking for Self.
            $linkedPerson = Persons::query()
                ->where('hip_user_id', $hipUser->id)
                ->orderByDesc('is_primary')
                ->first();

            return [
                'member_id'      => $hipUser->id,
                'patient_id'     => $linkedPerson?->id,
                'name'           => $name !== '' ? $name : ($hipUser->email ?? 'Member'),
                'mobile_number'  => $hipUser->mobile_num,
            ];
        }

        $person = Persons::query()->find($patientUuid);

        if ($person) {
            $name = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));

            return [
                'member_id'      => $person->id,
                'patient_id'     => $person->id,
                'name'           => $name !== '' ? $name : 'Dependent',
                'mobile_number'  => $person->mobile ?? $person->hipUser?->mobile_num,
            ];
        }

        return null;
    }

    private function normalizeArrayInput(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Map app labels to DB enum: home | lab.
     * Frontend: "hospital" (visit hospital) → lab, "home" → home.
     */
    public function normalizeSampleCollection(string $value): string
    {
        $key = strtolower(str_replace([' ', '-'], '_', trim($value)));

        return match ($key) {
            'home', 'home_collection' => 'home',
            'hospital', 'lab', 'visit_hospital', 'hospital_visit' => 'lab',
            default => $key,
        };
    }

    /**
     * Service booking: test_items are diagnostic_lab_tests ids from the request.
     */
    public function resolveServiceTestItems(array $testItemIds, int $diagnosticCenterId): array
    {
        $testItemIds = array_values(array_unique(array_map('intval', $testItemIds)));

        if (empty($testItemIds)) {
            throw new \InvalidArgumentException('At least one lab test is required for service booking.');
        }

        $validIds = DiagnosticLabTest::query()
            ->where('diagnostic_id', $diagnosticCenterId)
            ->whereIn('id', $testItemIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($validIds) !== count($testItemIds)) {
            throw new \InvalidArgumentException('One or more lab tests are invalid for this diagnostic center.');
        }

        return $validIds;
    }

    public function diagnosticTestBooking($request, string $patientUuid, ?string $authUserId = null, ?string $deviceId = null)
    {
        $patient = $this->resolvePatientDetails($patientUuid);

        if (! $patient) {
            throw new \InvalidArgumentException('Invalid patient. Patient not found in profile or dependents.');
        }

        if (! $patient['mobile_number']) {
            throw new \InvalidArgumentException('Patient mobile number is required to create a booking.');
        }

        $timeSlots = $this->normalizeArrayInput($request->required_time_slots);
        $sampleCollection = $this->normalizeSampleCollection((string) $request->sample_collection);

        if (! in_array($sampleCollection, ['home', 'lab'], true)) {
            throw new \InvalidArgumentException('sample_collection must be home or hospital (visit hospital).');
        }

        $basePayload = [
            'name'                  => $patient['name'],
            'mobile_number'         => $patient['mobile_number'],
            'member_id'             => $patient['member_id'],
            'patient_id'            => $patient['patient_id'],
            'diagnostic_center_id'  => $request->diagnostic_center_id,
            'sample_collection'     => $sampleCollection,
            'booking_date'          => $request->booking_date,
            'required_time_slots'   => $timeSlots,
            'purpose'               => $request->message ?? $request->purpose ?? null,
            'status'                => 'pending',
        ];

        if ($request->type === 'service') {
            $testItems = $this->resolveServiceTestItems(
                $this->normalizeArrayInput($request->test_items),
                (int) $request->diagnostic_center_id
            );

            $testType = count($testItems) === 1 ? 'single' : 'multi';

            $diagnosticTestBooking = DiagnosticTestBooking::create(array_merge($basePayload, [
                'test_type'  => $testType,
                'test_items' => $testItems,
            ]));

            $this->sendDiagnosticBookingNotification(
                $authUserId,
                $deviceId,
                'New Diagnostic Test Booking',
                'You have a new diagnostic test booking request',
                [
                    'type'       => 'diagnostic_test_booking',
                    'booking_id' => (string) $diagnosticTestBooking->id,
                ]
            );

            return $diagnosticTestBooking;
        }

        if ($request->type === 'package') {
            $package = DiagnosticPackage::query()
                ->where('id', $request->package_id)
                ->where('diagnostic_id', $request->diagnostic_center_id)
                ->first();

            if (! $package) {
                throw new \InvalidArgumentException('Package not found for this diagnostic center.');
            }

            $testItemIds = $this->normalizeArrayInput($package->lab_tests);

            if (empty($testItemIds)) {
                throw new \InvalidArgumentException('Selected package has no lab tests configured.');
            }

            $diagnosticTestBooking = DiagnosticTestBooking::create(array_merge($basePayload, [
                'package_id' => $package->id,
                'test_type'  => 'package',
                'test_items' => $testItemIds,
            ]));

            $this->sendDiagnosticBookingNotification(
                $authUserId,
                $deviceId,
                'New Diagnostic Package Booking',
                'You have a new diagnostic package booking request',
                [
                    'type'       => 'diagnostic_package_booking',
                    'booking_id' => (string) $diagnosticTestBooking->id,
                ]
            );

            return $diagnosticTestBooking;
        }

        return null;
    }

    private function sendDiagnosticBookingNotification(
        ?string $authUserId,
        ?string $deviceId,
        string $title,
        string $body,
        array $data = []
    ): void {
        if (! $authUserId || ! $deviceId) {
            return;
        }

        try {
            $this->notificationService->sendToDevice($authUserId, $deviceId, $title, $body, $data);
        } catch (\Throwable $e) {
            Log::warning('Failed to send diagnostic booking notification', [
                'error'   => $e->getMessage(),
                'user_id' => $authUserId,
            ]);
        }
    }

    public function stemCellBooking($request, $memberId = null){

        $stemCellBooking = StemCellBooking::create([
            'name' => $request->name,
            'mobile_number' => $request->mobile_number,
            'member_id' => $memberId,
            'booking_date' => $request->booking_date,
            'required_time_slots' => $request->required_time_slots,
            'purpose' => $request->purpose,
            'status' => 'enquiry',
        ]);

        return $stemCellBooking;

    }
    
}