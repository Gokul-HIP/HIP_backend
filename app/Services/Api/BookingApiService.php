<?php

namespace App\Services\Api;

use App\Services\FamilyPackageService;
use App\Models\CaregiverBooking;
use App\Models\DiagnosticPackage;
use App\Models\DiseasePackage;
use App\Models\Hospital;
use App\Models\DiagnosticLabTest;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Models\StemCellBooking;
use App\Models\DiagnosticTestBooking;
use App\Models\Doctor;
use App\Models\WellnessBooking;
use App\Models\DoctorBooking;
use App\Models\SecondOpinion;
use App\Models\Document;
use App\Models\ProcedureBooking;
use App\Models\WellnessCenters;
use App\Models\Coins;
use App\Models\Invoice;
use App\Models\Transactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\CoinsWalletService;
use App\Services\NotificationService;
use App\Services\RewardTierService;

class BookingApiService
{
    protected NotificationService $notificationService;

    protected PaymentApiService $paymentApiService;

    public function __construct(
        NotificationService $notificationService,
        PaymentApiService $paymentApiService
    ) {
        $this->notificationService = $notificationService;
        $this->paymentApiService = $paymentApiService;
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

    public function doctorBooking($request, ?string $authUserId = null)
    {
        if (! $authUserId) {
            throw new \InvalidArgumentException('Authentication required to book an appointment.');
        }

        $context = $this->resolveDoctorBookingContext(
            (string) $request->patient_id,
            $authUserId
        );

        $doctor = Doctor::query()->find($request->doctor_id);

        if (! $doctor) {
            throw new \InvalidArgumentException('Doctor not found.');
        }

        $branchId = (int) ($request->branch_id ?? $request->hospital_id);
        $timeSlots = $this->normalizeArrayInput($request->required_time_slots);
        $message = $request->message ?? $request->purpose ?? null;
        $consultationFee = (float) ($doctor->consultation_fee ?? 0);
        $amountForOneCoin = (float) app_setting(
            'amount_for_one_coin',
            config('settings.payment.amount_for_one_coin', 1)
        );
        $requestedCoinsUsed = max(0, (int) $request->input('coins_used', 0));
        $isCoinsApplied = $requestedCoinsUsed > 0 || (bool) $request->boolean('is_coins_applied');
        $isOnlinePayment = (bool) $request->boolean('is_online_payment');

        return DB::transaction(function () use (
            $request,
            $authUserId,
            $context,
            $doctor,
            $branchId,
            $timeSlots,
            $message,
            $consultationFee,
            $amountForOneCoin,
            $isCoinsApplied,
            $requestedCoinsUsed,
            $isOnlinePayment
        ) {
            $coinsUsed = 0;
            $coinsValue = 0.0;

            if ($isCoinsApplied && $requestedCoinsUsed > 0) {
                $person = Persons::query()->where('hip_user_id', $authUserId)->first();
                $walletPersonId = $person?->parent_id ?? $person?->id;

                if (! $walletPersonId) {
                    throw new \InvalidArgumentException('Coin wallet not found for this user.');
                }

                $coinsWallet = Coins::query()
                    ->where('person_id', $walletPersonId)
                    ->lockForUpdate()
                    ->first();

                $availableCoins = (int) ($coinsWallet?->coins ?? 0);

                if ($requestedCoinsUsed > $availableCoins) {
                    throw new \InvalidArgumentException('Insufficient coins. Available: '.$availableCoins);
                }

                $coinsUsed = $requestedCoinsUsed;
                $coinsValue = round($coinsUsed * $amountForOneCoin, 2);

                // For online payment, deduct coins only after Razorpay payment succeeds.
                if (! $isOnlinePayment && $coinsWallet instanceof Coins) {
                    app(CoinsWalletService::class)->debit($coinsWallet, $coinsUsed);
                }
            }

            $totalDiscount = round(min($coinsValue, $consultationFee), 2);
            $amountAfterDiscount = round(max(0, $consultationFee - $totalDiscount), 2);
            $serviceCharge = $this->paymentApiService->calculateServiceCharges($amountAfterDiscount);
            $totalAmount = round($amountAfterDiscount + $serviceCharge, 2);

            $doctorBooking = DoctorBooking::create([
                'name'                => $context['name'],
                'mobile_number'       => $context['mobile_number'],
                'member_id'           => $context['member_id'],
                'patient_id'          => $context['patient_id'],
                'relationship'        => $context['relationship'],
                'branch_id'           => $branchId,
                'hospital_id'         => $branchId ?: ($doctor->hospital_ids[0] ?? null),
                'doctor_id'           => $request->doctor_id,
                'department_id'       => $request->department_id,
                'appointment_type'    => $request->appointment_type,
                'consultation_type'   => $request->appointment_type,
                'booking_date'        => $request->booking_date,
                'required_time_slots' => $timeSlots,
                'reason_of_visit'     => $request->reason_of_visit,
                'is_follow_up'        => (bool) $request->boolean('is_follow_up'),
                'message'             => $message,
                'purpose'             => $message,
                'is_coins_applied'    => $coinsUsed > 0,
                'coins_used'          => $coinsUsed,
                'consultation_fee'    => $consultationFee,
                'service_charges'     => $serviceCharge,
                'total_discount'      => $totalDiscount,
                'amount_after_discount' => $amountAfterDiscount,
                'total_amount'        => $totalAmount,
                'is_online_payment'   => $isOnlinePayment,
                'payment_status'      => $isOnlinePayment ? 'pending' : 'unpaid',
                'status'              => 'pending',
            ]);

            $paymentData = null;

            if ($isOnlinePayment) {
                $patientPersonId = (string) ($context['patient_id'] ?? '');

                if ($patientPersonId === '') {
                    throw new \InvalidArgumentException('Patient profile is required for online payment.');
                }

                if ($totalAmount <= 0) {
                    $this->paymentApiService->deductDoctorBookingCoins($doctorBooking);

                    $invoice = $this->paymentApiService->createInvoiceForDoctorBooking(
                        $doctorBooking,
                        $patientPersonId
                    );

                    $doctorBooking->update([
                        'invoice_id' => $invoice->id,
                        'payment_status' => 'paid',
                        'status' => 'confirmed',
                    ]);

                    $invoice->update([
                        'status' => 'completed',
                        'payment_method' => 'free',
                    ]);

                    Transactions::create([
                        'invoice_id' => $invoice->id,
                        'service_types' => $invoice->service_types,
                        'invoice_details' => $invoice->invoice_details,
                        'transaction_amount' => 0,
                        'service_charges' => (float) ($doctorBooking->service_charges ?? 0),
                        'payment_gateway_charges' => 0,
                        'discount_amount' => (float) ($doctorBooking->total_discount ?? 0),
                        'total_gst' => 0,
                        'total_amount' => 0,
                        'status' => 'completed',
                        'payment_method' => 'free',
                    ]);

                    $paymentData = [
                        'requires_payment' => false,
                        'invoice_id' => (int) $invoice->id,
                        'payment_status' => 'paid',
                    ];
                } else {
                    $invoice = $this->paymentApiService->createInvoiceForDoctorBooking(
                        $doctorBooking,
                        $patientPersonId
                    );

                    $doctorBooking->update(['invoice_id' => $invoice->id]);

                    $razorpayOrder = $this->paymentApiService->createDoctorBookingRazorpayOrder(
                        $doctorBooking->fresh()
                    );

                    $paymentData = [
                        'requires_payment' => true,
                        'invoice_id' => (int) $razorpayOrder['invoice_id'],
                        'order_id' => (string) $razorpayOrder['order_id'],
                        'razorpay_key' => (string) $razorpayOrder['razorpay_key'],
                        'amount' => (int) $razorpayOrder['amount'],
                        'transaction_id' => (string) $razorpayOrder['transaction_id'],
                        'currency' => 'INR',
                        'payment_status' => 'pending',
                    ];
                }
            }

            if (! $isOnlinePayment || ($paymentData['payment_status'] ?? '') === 'paid') {
                $this->awardRewardTierBookingCoins($authUserId, $totalAmount);
            }

            try {
                app(FamilyPackageService::class)->tryLogBookingUsage(
                    $authUserId,
                    'consultation',
                    (int) $doctorBooking->id,
                    'doctor_booking'
                );
            } catch (\Throwable $e) {
                Log::warning('Family package consultation usage log failed', [
                    'user_id' => $authUserId,
                    'booking_id' => $doctorBooking->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'booking' => $doctorBooking->fresh(['doctor']),
                'payment' => $paymentData,
            ];
        });
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

            // patient_id FK references persons — ensure a primary person exists for invoices/payments.
            $linkedPerson = $this->ensurePrimaryPersonForHipUser($hipUser);

            return [
                'member_id'      => $hipUser->id,
                'patient_id'     => $linkedPerson->id,
                'name'           => $name !== '' ? $name : ($hipUser->email ?? 'Member'),
                'mobile_number'  => $hipUser->mobile_num,
                'relationship'   => $linkedPerson->relationship ?? 'Self',
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
                'relationship'   => $person->relationship ?? 'Dependent',
            ];
        }

        return null;
    }

    /**
     * Ensure a persons row exists for a HIP user (required for Razorpay invoices / coin wallet).
     */
    private function ensurePrimaryPersonForHipUser(HIPUser $hipUser): Persons
    {
        $person = Persons::query()
            ->where('hip_user_id', $hipUser->id)
            ->orderByDesc('is_primary')
            ->first();

        if ($person) {
            if (! $person->parent_id) {
                $person->update(['parent_id' => $person->id]);
            }

            return $person->fresh();
        }

        if ($hipUser->mobile_num) {
            $person = Persons::query()
                ->where('mobile', $hipUser->mobile_num)
                ->where(function ($query) use ($hipUser) {
                    $query->whereNull('hip_user_id')
                        ->orWhere('hip_user_id', $hipUser->id);
                })
                ->orderByDesc('is_primary')
                ->first();

            if ($person) {
                $person->update([
                    'hip_user_id' => $hipUser->id,
                    'is_primary'  => true,
                ]);

                if (! $person->parent_id) {
                    $person->update(['parent_id' => $person->id]);
                }

                return $person->fresh();
            }
        }

        $person = Persons::create([
            'first_name'    => $hipUser->first_name,
            'last_name'     => $hipUser->last_name,
            'email'         => $hipUser->email,
            'mobile'        => $hipUser->mobile_num,
            'gender'        => $hipUser->gender,
            'dob'           => $hipUser->dob,
            'hip_user_id'   => $hipUser->id,
            'is_primary'    => true,
            'relationship'  => 'Self',
        ]);

        $person->update(['parent_id' => $person->id]);

        return $person->fresh();
    }

    /**
     * Doctor booking: member_id/mobile = authenticated booker; name/relationship/patient_id = selected patient.
     */
    public function resolveDoctorBookingContext(string $patientUuid, string $authUserId): array
    {
        $booker = HIPUser::query()->find($authUserId);

        if (! $booker) {
            throw new \InvalidArgumentException('Authenticated member not found.');
        }

        if (! $booker->mobile_num) {
            throw new \InvalidArgumentException('Your mobile number is required to create a booking.');
        }

        $patient = $this->resolvePatientDetails($patientUuid);

        if (! $patient) {
            throw new \InvalidArgumentException('Invalid patient. Patient not found in profile or dependents.');
        }

        return [
            'member_id'     => $booker->id,
            'mobile_number' => $booker->mobile_num,
            'patient_id'    => $patient['patient_id'],
            'name'          => $patient['name'],
            'relationship'  => $patient['relationship'],
        ];
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

    /**
     * Diagnostic / disease package booking (package only).
     *
     * @return array{booking: DiagnosticTestBooking, payment: array<string, mixed>|null}
     */
    public function diagnosticTestBooking($request, ?string $authUserId = null, ?string $deviceId = null): array
    {
        if (! $authUserId) {
            throw new \InvalidArgumentException('Authentication required to create a package booking.');
        }

        $context = $this->resolveDoctorBookingContext(
            (string) $request->patient_id,
            $authUserId
        );

        $branchId = (int) ($request->branch_id ?? $request->hospital_id);
        $hospital = Hospital::query()
            ->select('id', 'diagnostic_center_id')
            ->find($branchId);

        if (! $hospital || ! $hospital->diagnostic_center_id) {
            throw new \InvalidArgumentException('Hospital branch is not linked to a diagnostic center.');
        }

        $packageType = (string) $request->package_type;
        $package = $this->resolvePackageForBooking(
            $packageType,
            (int) $request->package_id,
            (int) $hospital->diagnostic_center_id
        );

        if (! $package) {
            throw new \InvalidArgumentException('Package not found or inactive for this branch.');
        }

        $testItemIds = $this->normalizeArrayInput($package->lab_tests);

        if ($testItemIds === []) {
            throw new \InvalidArgumentException('Selected package has no lab tests configured.');
        }

        $timeSlots = $this->normalizeArrayInput($request->required_time_slots);
        $sampleCollection = $this->normalizeSampleCollection((string) $request->sample_collection);

        if (! in_array($sampleCollection, ['home', 'lab'], true)) {
            throw new \InvalidArgumentException('sample_collection must be home or hospital (visit hospital).');
        }

        $message = $request->message ?? $request->purpose ?? null;
        $packageFee = $this->packageBookingFee($package);
        $amountForOneCoin = (float) app_setting(
            'amount_for_one_coin',
            config('settings.payment.amount_for_one_coin', 1)
        );
        $requestedCoinsUsed = max(0, (int) $request->input('coins_used', 0));
        $isCoinsApplied = $requestedCoinsUsed > 0 || (bool) $request->boolean('is_coins_applied');
        $isOnlinePayment = (bool) $request->boolean('is_online_payment');

        return DB::transaction(function () use (
            $request,
            $authUserId,
            $context,
            $hospital,
            $branchId,
            $package,
            $packageType,
            $testItemIds,
            $timeSlots,
            $sampleCollection,
            $message,
            $packageFee,
            $amountForOneCoin,
            $isCoinsApplied,
            $requestedCoinsUsed,
            $isOnlinePayment,
            $deviceId
        ) {
            $coinsUsed = 0;
            $coinsValue = 0.0;

            if ($isCoinsApplied && $requestedCoinsUsed > 0) {
                $person = Persons::query()->where('hip_user_id', $authUserId)->first();
                $walletPersonId = $person?->parent_id ?? $person?->id;

                if (! $walletPersonId) {
                    throw new \InvalidArgumentException('Coin wallet not found for this user.');
                }

                $coinsWallet = Coins::query()
                    ->where('person_id', $walletPersonId)
                    ->lockForUpdate()
                    ->first();

                $availableCoins = (int) ($coinsWallet?->coins ?? 0);

                if ($requestedCoinsUsed > $availableCoins) {
                    throw new \InvalidArgumentException('Insufficient coins. Available: '.$availableCoins);
                }

                $coinsUsed = $requestedCoinsUsed;
                $coinsValue = round($coinsUsed * $amountForOneCoin, 2);

                if (! $isOnlinePayment && $coinsWallet instanceof Coins) {
                    app(CoinsWalletService::class)->debit($coinsWallet, $coinsUsed);
                }
            }

            $totalDiscount = round(min($coinsValue, $packageFee), 2);
            $amountAfterDiscount = round(max(0, $packageFee - $totalDiscount), 2);
            $serviceCharge = $this->paymentApiService->calculateServiceCharges($amountAfterDiscount);
            $totalAmount = round($amountAfterDiscount + $serviceCharge, 2);

            $booking = DiagnosticTestBooking::create([
                'name'                  => $context['name'],
                'mobile_number'         => $context['mobile_number'],
                'member_id'             => $context['member_id'],
                'patient_id'            => $context['patient_id'],
                'relationship'          => $context['relationship'],
                'branch_id'             => $branchId,
                'diagnostic_center_id'  => $hospital->diagnostic_center_id,
                'package_id'            => $package->id,
                'package_type'          => $packageType,
                'test_type'             => 'package',
                'test_items'            => $testItemIds,
                'sample_collection'     => $sampleCollection,
                'booking_date'          => $request->booking_date,
                'required_time_slots'   => $timeSlots,
                'purpose'               => $message,
                'status'                => 'pending',
                'is_coins_applied'      => $coinsUsed > 0,
                'coins_used'            => $coinsUsed,
                'package_fee'           => $packageFee,
                'service_charges'       => $serviceCharge,
                'total_discount'        => $totalDiscount,
                'amount_after_discount' => $amountAfterDiscount,
                'total_amount'          => $totalAmount,
                'is_online_payment'     => $isOnlinePayment,
                'payment_status'        => $isOnlinePayment ? 'pending' : 'unpaid',
            ]);

            $paymentData = null;
            $patientPersonId = (string) ($context['patient_id'] ?? '');

            if ($isOnlinePayment) {
                if ($patientPersonId === '') {
                    throw new \InvalidArgumentException('Patient profile is required for online payment.');
                }

                if ($totalAmount <= 0) {
                    $this->paymentApiService->deductDiagnosticTestBookingCoins($booking);

                    $invoice = $this->paymentApiService->createInvoiceForDiagnosticTestBooking(
                        $booking,
                        $patientPersonId
                    );

                    $booking->update([
                        'invoice_id'     => $invoice->id,
                        'payment_status' => 'paid',
                        'status'         => 'confirmed',
                    ]);

                    $invoice->update([
                        'status'         => 'completed',
                        'payment_method' => 'free',
                    ]);

                    Transactions::create([
                        'invoice_id'              => $invoice->id,
                        'service_types'           => $invoice->service_types,
                        'invoice_details'         => $invoice->invoice_details,
                        'transaction_amount'      => 0,
                        'service_charges'         => (float) ($booking->service_charges ?? 0),
                        'payment_gateway_charges' => 0,
                        'discount_amount'         => (float) ($booking->total_discount ?? 0),
                        'total_gst'               => 0,
                        'total_amount'            => 0,
                        'status'                  => 'completed',
                        'payment_method'          => 'free',
                    ]);

                    $paymentData = [
                        'requires_payment' => false,
                        'invoice_id'       => (int) $invoice->id,
                        'payment_status'   => 'paid',
                    ];
                } else {
                    $invoice = $this->paymentApiService->createInvoiceForDiagnosticTestBooking(
                        $booking,
                        $patientPersonId
                    );

                    $booking->update(['invoice_id' => $invoice->id]);

                    $razorpayOrder = $this->paymentApiService->createDiagnosticTestBookingRazorpayOrder(
                        $booking->fresh()
                    );

                    $paymentData = [
                        'requires_payment' => true,
                        'invoice_id'       => (int) $razorpayOrder['invoice_id'],
                        'order_id'         => (string) $razorpayOrder['order_id'],
                        'razorpay_key'     => (string) $razorpayOrder['razorpay_key'],
                        'amount'           => (int) $razorpayOrder['amount'],
                        'transaction_id'   => (string) $razorpayOrder['transaction_id'],
                        'currency'         => 'INR',
                        'payment_status'   => 'pending',
                    ];
                }
            }

            $this->sendDiagnosticBookingNotification(
                $authUserId,
                $deviceId,
                'New Package Booking',
                'Your diagnostic package booking has been submitted',
                [
                    'type'         => 'diagnostic_package_booking',
                    'booking_id'   => (string) $booking->id,
                    'package_type' => $packageType,
                ]
            );

            if (! $isOnlinePayment || ($paymentData['payment_status'] ?? '') === 'paid') {
                $this->awardRewardTierBookingCoins($authUserId, $totalAmount);
            }

            try {
                app(FamilyPackageService::class)->tryLogBookingUsage(
                    $authUserId,
                    'lab_test',
                    (int) $booking->id,
                    'diagnostic_test_booking'
                );
            } catch (\Throwable $e) {
                Log::warning('Family package lab test usage log failed', [
                    'user_id' => $authUserId,
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'booking' => $booking->fresh(),
                'payment' => $paymentData,
            ];
        });
    }

    private function resolvePackageForBooking(
        string $packageType,
        int $packageId,
        int $diagnosticCenterId
    ): DiagnosticPackage|DiseasePackage|null {
        $query = $packageType === 'disease'
            ? DiseasePackage::query()
            : DiagnosticPackage::query();

        $package = $query
            ->where('id', $packageId)
            ->where('diagnostic_id', $diagnosticCenterId)
            ->first();

        if (! $package || ($package->status ?? null) !== 'active') {
            return null;
        }

        return $package;
    }

    private function packageBookingFee(DiagnosticPackage|DiseasePackage $package): float
    {
        $price = (float) ($package->price ?? 0);

        return \App\Support\DiscountPrice::payable($price, $package->discount ?? null);
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

    /**
     * Second opinion request with optional report uploads and optional Razorpay payment.
     *
     * @return array{booking: SecondOpinion, payment: array<string, mixed>|null}
     */
    public function secondOpinion($request, ?string $authUserId = null): array
    {
        if (! $authUserId) {
            throw new \InvalidArgumentException('Authentication required to submit a second opinion request.');
        }

        $context = $this->resolveDoctorBookingContext(
            (string) $request->patient_id,
            $authUserId
        );

        $doctor = Doctor::query()->find($request->doctor_id);

        if (! $doctor) {
            throw new \InvalidArgumentException('Doctor not found.');
        }

        $branchId = (int) ($request->branch_id ?? $request->hospital_id);
        $timeSlots = $this->normalizeArrayInput($request->preferred_time_slots);
        $consultationFee = (float) ($request->input('consultation_fee', $doctor->consultation_fee ?? 0));
        $amountForOneCoin = (float) app_setting(
            'amount_for_one_coin',
            config('settings.payment.amount_for_one_coin', 1)
        );
        $requestedCoinsUsed = max(0, (int) $request->input('coins_used', 0));
        $isCoinsApplied = $requestedCoinsUsed > 0 || (bool) $request->boolean('is_coins_applied');
        $isOnlinePayment = (bool) $request->boolean('is_online_payment');

        $result = DB::transaction(function () use (
            $request,
            $authUserId,
            $context,
            $doctor,
            $branchId,
            $timeSlots,
            $consultationFee,
            $amountForOneCoin,
            $isCoinsApplied,
            $requestedCoinsUsed,
            $isOnlinePayment
        ) {
            $documentIds = $this->storeSecondOpinionReports(
                $request,
                (string) $context['member_id']
            );

            $coinsUsed = 0;
            $coinsValue = 0.0;

            if ($isCoinsApplied && $requestedCoinsUsed > 0) {
                $person = Persons::query()->where('hip_user_id', $authUserId)->first();
                $walletPersonId = $person?->parent_id ?? $person?->id;

                if (! $walletPersonId) {
                    throw new \InvalidArgumentException('Coin wallet not found for this user.');
                }

                $coinsWallet = Coins::query()
                    ->where('person_id', $walletPersonId)
                    ->lockForUpdate()
                    ->first();

                $availableCoins = (int) ($coinsWallet?->coins ?? 0);

                if ($requestedCoinsUsed > $availableCoins) {
                    throw new \InvalidArgumentException('Insufficient coins. Available: '.$availableCoins);
                }

                $coinsUsed = $requestedCoinsUsed;
                $coinsValue = round($coinsUsed * $amountForOneCoin, 2);

                if (! $isOnlinePayment && $coinsWallet instanceof Coins) {
                    app(CoinsWalletService::class)->debit($coinsWallet, $coinsUsed);
                }
            }

            $totalDiscount = round(min($coinsValue, $consultationFee), 2);
            $amountAfterDiscount = round(max(0, $consultationFee - $totalDiscount), 2);
            $serviceCharge = $this->paymentApiService->calculateServiceCharges($amountAfterDiscount);
            $totalAmount = round($amountAfterDiscount + $serviceCharge, 2);

            $secondOpinion = SecondOpinion::create([
                'member_id'               => $context['member_id'],
                'patient_id'              => $context['patient_id'],
                'patient_name'            => $context['name'],
                'diagnosis'               => $request->diagnosis,
                'treatment'               => $request->treatment,
                'question_for_doctor'     => $request->question_for_doctor,
                'document_ids'            => $documentIds !== [] ? $documentIds : null,
                'branch_id'               => $branchId,
                'speciality_id'           => $request->speciality_id ?? $request->department_id,
                'doctor_id'               => $request->doctor_id,
                'mode_of_consultation'    => $request->mode_of_consultation,
                'preferred_date'          => $request->preferred_date,
                'preferred_time_slots'    => $timeSlots,
                'relationship'            => $context['relationship'],
                'is_coins_applied'        => $coinsUsed > 0,
                'coins_used'              => $coinsUsed,
                'consultation_fee'        => $consultationFee,
                'service_charges'         => $serviceCharge,
                'total_discount'          => $totalDiscount,
                'amount_after_discount'   => $amountAfterDiscount,
                'total_amount'            => $totalAmount,
                'is_online_payment'       => $isOnlinePayment,
                'payment_status'          => $isOnlinePayment ? 'pending' : 'unpaid',
                'status'                  => 'pending',
            ]);

            $paymentData = null;
            $needsRazorpay = false;

            if ($isOnlinePayment) {
                $patientPersonId = (string) ($context['patient_id'] ?? '');

                if ($patientPersonId === '') {
                    throw new \InvalidArgumentException('Patient profile is required for online payment.');
                }

                if ($totalAmount <= 0) {
                    $this->paymentApiService->deductSecondOpinionCoins($secondOpinion);

                    $invoice = $this->paymentApiService->createInvoiceForSecondOpinion(
                        $secondOpinion,
                        $patientPersonId
                    );

                    $secondOpinion->update([
                        'invoice_id'     => $invoice->id,
                        'payment_status' => 'paid',
                        'status'         => 'confirmed',
                    ]);

                    $invoice->update([
                        'status'         => 'completed',
                        'payment_method' => 'free',
                    ]);

                    Transactions::create([
                        'invoice_id'              => $invoice->id,
                        'service_types'           => $invoice->service_types,
                        'invoice_details'         => $invoice->invoice_details,
                        'transaction_amount'      => 0,
                        'service_charges'         => (float) ($secondOpinion->service_charges ?? 0),
                        'payment_gateway_charges' => 0,
                        'discount_amount'         => (float) ($secondOpinion->total_discount ?? 0),
                        'total_gst'               => 0,
                        'total_amount'            => 0,
                        'status'                  => 'completed',
                        'payment_method'          => 'free',
                    ]);

                    $paymentData = [
                        'requires_payment' => false,
                        'invoice_id'       => (int) $invoice->id,
                        'payment_status'   => 'paid',
                    ];
                } else {
                    $invoice = $this->paymentApiService->createInvoiceForSecondOpinion(
                        $secondOpinion,
                        $patientPersonId
                    );

                    $secondOpinion->update(['invoice_id' => $invoice->id]);
                    $needsRazorpay = true;
                }
            }

            if (! $isOnlinePayment || ($paymentData['payment_status'] ?? '') === 'paid') {
                $this->awardRewardTierBookingCoins($authUserId, $totalAmount);
            }

            return [
                'booking' => $secondOpinion->fresh(['doctor']),
                'payment' => $paymentData,
                'needs_razorpay' => $needsRazorpay,
            ];
        });

        if ($result['needs_razorpay'] ?? false) {
            $razorpayOrder = $this->paymentApiService->createSecondOpinionRazorpayOrder(
                $result['booking']->fresh()
            );

            $result['payment'] = [
                'requires_payment' => true,
                'invoice_id'       => (int) $razorpayOrder['invoice_id'],
                'order_id'         => (string) $razorpayOrder['order_id'],
                'razorpay_key'     => (string) $razorpayOrder['razorpay_key'],
                'amount'           => (int) $razorpayOrder['amount'],
                'transaction_id'   => (string) $razorpayOrder['transaction_id'],
                'currency'         => 'INR',
                'payment_status'   => 'pending',
            ];
        }

        unset($result['needs_razorpay']);

        return $result;
    }

    /**
     * Store uploaded report_1 / report_2 files.
     * document_name = original file name; report_N_name = document_type.
     *
     * @return list<int>
     */
    private function storeSecondOpinionReports($request, string $memberId): array
    {
        $documentIds = [];

        foreach ([1, 2] as $index) {
            $fileKey = "report_{$index}";
            $nameKey = "report_{$index}_name";

            if (! $request->hasFile($fileKey)) {
                continue;
            }

            $file = $request->file($fileKey);

            if (! $file instanceof UploadedFile) {
                throw new \InvalidArgumentException("Report {$index} upload is invalid.");
            }

            if (! $file->isValid()) {
                $uploadError = match ($file->getError()) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'file is too large',
                    UPLOAD_ERR_PARTIAL => 'upload was incomplete, please try again',
                    UPLOAD_ERR_NO_FILE => 'no file was received',
                    default => 'upload failed',
                };

                throw new \InvalidArgumentException("Report {$index} {$uploadError}.");
            }

            $originalFileName = trim(basename((string) $file->getClientOriginalName()));
            $documentName = $originalFileName !== '' ? $originalFileName : "Report {$index}";

            $documentType = strtolower(trim((string) $request->input($nameKey, '')));
            if ($documentType === '') {
                $documentType = 'report';
            }

            $storedPath = $file->store("documents/second-opinion/{$memberId}", 'public');

            $document = Document::create([
                'member_id'     => $memberId,
                'document_name' => $documentName,
                'document_path' => $storedPath,
                'document_type' => $documentType,
                'document_size' => (string) $file->getSize(),
            ]);

            $documentIds[] = (int) $document->id;
        }

        return $documentIds;
    }

    private function awardRewardTierBookingCoins(string $hipUserId, float $paidAmount = 0): void
    {
        $rewardTierService = app(RewardTierService::class);
        $tier = $rewardTierService->getTierForUser($hipUserId);
        $tier->loadMissing('config');

        $coinsFromPayment = (int) round($paidAmount * 0.01);
        $tierBonus = (int) ($tier->config?->earned_coins_per_booking ?? 0);

        $rewardTierService->addEarnedCoins($hipUserId, $coinsFromPayment + $tierBonus);
    }
}