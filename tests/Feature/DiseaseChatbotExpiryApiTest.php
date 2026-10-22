<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\Coins;
use App\Models\DiseaseChatbotBooking;
use App\Models\Doctor;
use App\Models\DoctorAssignment;
use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Procedure;
use App\Models\RewardTier;
use App\Models\RewardTierConfig;
use App\Models\RazorpayPayment;
use App\Models\Setting;
use App\Models\SpecialitiesMaster;
use App\Models\Speciality;
use App\Models\Transactions;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\Events\AppointmentCancelled;
use App\Modules\Automation\Services\InvoiceGeneratedDispatcher;
use App\Modules\Automation\Services\PaymentReceivedDispatcher;
use App\Modules\Automation\Services\PendingPaymentDetector;
use App\Services\Api\PaymentApiService;
use App\Services\DoctorNotificationService;
use App\Services\FamilyPackageService;
use App\Services\InvoiceFeeCalculator;
use App\Services\MembershipEntitlementService;
use App\Services\NotificationService;
use App\Services\RewardTierService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Razorpay\Api\Api;
use ReflectionProperty;
use Tests\TestCase;

class DiseaseChatbotExpiryApiTest extends TestCase
{
    private static bool $schemaReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Disease chatbot expiry tests must use sqlite.');
        }

        $this->bootSchema();
        $this->withoutMiddleware(EnsureAccountIsActive::class);

        putenv('RAZORPAY_KEY_ID=rzp_test_key');
        putenv('RAZORPAY_KEY_SECRET=rzp_test_secret');
        $_ENV['RAZORPAY_KEY_ID'] = 'rzp_test_key';
        $_ENV['RAZORPAY_KEY_SECRET'] = 'rzp_test_secret';
        putenv('AMOUNT_FOR_ONE_COIN=10');
        $_ENV['AMOUNT_FOR_ONE_COIN'] = '10';

        config([
            'chatbot.disease_enabled' => true,
            'chatbot.ai_matching_enabled' => false,
            'chatbot.slot_minutes' => 30,
            'chatbot.slot_lead_minutes' => 30,
            'chatbot.payment_hold_minutes' => 15,
            'chatbot.slot_hold_minutes' => 15,
            'chatbot.slot_horizon_days' => 14,
            'chatbot.reconcile_max_minutes' => 60,
            'chatbot.appointment_types' => ['In-Clinic'],
            'app.timezone' => 'Asia/Kolkata',
            'settings.fees.service_charges' => 3,
            'services.service_charges_percent' => 3,
            'settings.payment.amount_for_one_coin' => 10,
        ]);

        Cache::flush();
        Setting::clearCache();
        Setting::set('amount_for_one_coin', 10, 'float', 'payment');
        Carbon::setTestNow(Carbon::parse('2026-10-17 08:00:00', 'Asia/Kolkata'));

        Event::fake([AppointmentBooked::class, AppointmentCancelled::class]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/orders/') && str_contains($request->url(), '/payments')) {
                return Http::response(['entity' => 'collection', 'count' => 0, 'items' => []], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyUser')->andReturn(true)->byDefault();
        });
        $this->mock(DoctorNotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyDoctorOfNewBooking')->andReturnNull()->byDefault();
        });
        $this->mock(FamilyPackageService::class, function ($mock) {
            $mock->shouldReceive('tryLogBookingUsage')->andReturnNull();
            $mock->shouldReceive('expireStaleSubscriptions')->andReturn(0);
        });
        $this->mock(MembershipEntitlementService::class, function ($mock) {
            $feeCalc = app(InvoiceFeeCalculator::class);
            $mock->shouldReceive('priceDoctorConsultation')->andReturnUsing(
                function (string $hipUserId, float $fee, float $coinsValue = 0.0, ?int $branchId = null) use ($feeCalc) {
                    $fee = round(max(0, $fee), 2);
                    $coinsValue = round(max(0, $coinsValue), 2);
                    $coinsDiscount = round(min($coinsValue, $fee), 2);
                    $bd = $feeCalc->bookingBreakdown($fee, $coinsDiscount);

                    return [
                        'membership_benefit_applied' => false,
                        'benefit_type' => null,
                        'rejection_reason' => 'no_active_membership',
                        'subscription_id' => null,
                        'original_base_price' => $bd['original_amount'],
                        'membership_discount' => 0.0,
                        'reward_discount' => 0.0,
                        'reward_discount_source' => null,
                        'coins_discount' => $coinsDiscount,
                        'total_discount' => $bd['discount_amount'],
                        'discounted_base_price' => $bd['discounted_amount'],
                        'service_charges' => $bd['service_charges'],
                        'final_payable_amount' => $bd['invoice_total'],
                        'remaining_consultations' => null,
                        'breakdown' => $bd,
                    ];
                }
            );
            $mock->shouldReceive('formatPricingForApi')->andReturnUsing(fn (array $pricing) => [
                'membership_benefit_applied' => false,
                'benefit_type' => null,
                'rejection_reason' => $pricing['rejection_reason'] ?? null,
                'original_base_price' => (float) ($pricing['original_base_price'] ?? 0),
                'membership_discount' => 0.0,
                'reward_discount' => 0.0,
                'reward_discount_source' => null,
                'discounted_base_price' => (float) ($pricing['discounted_base_price'] ?? 0),
                'service_charges' => (float) ($pricing['service_charges'] ?? 0),
                'final_payable_amount' => (float) ($pricing['final_payable_amount'] ?? 0),
                'remaining_consultations' => null,
                'remaining_lab_tests' => null,
            ]);
            $mock->shouldReceive('reserveUsage')->andReturnNull();
            $mock->shouldReceive('releaseUsage')->andReturn(0);
            $mock->shouldReceive('confirmUsage')->andReturnNull();
        });
        $this->mock(InvoiceGeneratedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->mock(PaymentReceivedDispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->andReturnNull();
        });
        $this->mock(RewardTierService::class, function ($mock) {
            $tier = new RewardTier();
            $tier->setRelation('config', new RewardTierConfig([
                'earned_coins_per_booking' => 0,
            ]));
            $mock->shouldReceive('getTierForUser')->andReturn($tier);
            $mock->shouldReceive('addEarnedCoins')->andReturnNull();
            $mock->shouldReceive('earnedCoinsPerBookingBonus')->andReturn(0);
            $mock->shouldReceive('calculateBasePriceDiscount')->andReturn([
                'amount' => 0.0,
                'source' => null,
                'benefit_type' => null,
                'package_promotion_type' => null,
                'total_discount_percentage' => null,
            ]);
        });

        $this->bindRazorpayStub();

        foreach ([
            'disease_chatbot_bookings', 'disease_chatbot_messages', 'disease_chatbot_sessions',
            'doctor_booking_statuses', 'razorpay_payments', 'transactions', 'invoices',
            'doctor_bookings', 'doctor_reviews', 'doctor_assignments', 'procedures', 'doctors',
            'specialities', 'specialities_masters', 'coins', 'persons', 'hospitals',
            'healthinpocket_users', 'settings',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        Setting::clearCache();
        Setting::set('amount_for_one_coin', 10, 'float', 'payment');
    }

    public function test_expire_command_releases_hold_and_suppresses_cancel_side_effects(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor();
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk()->assertJsonPath('state', 'PAYMENT_PENDING');

        $booking = DoctorBooking::query()->firstOrFail();
        $invoice = Invoice::query()->findOrFail($booking->invoice_id);
        $this->assertSame('pending', $invoice->status);

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:16:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();

        $link = DiseaseChatbotBooking::query()->firstOrFail();
        $this->assertSame(DiseaseChatbotBooking::STATUS_EXPIRED, $link->status);
        $this->assertSame(DoctorBooking::STATUS_CANCELLED, $booking->fresh()->status);
        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertSame(
            'cancelled',
            Transactions::query()->where('invoice_id', $invoice->id)->value('status')
        );
        $this->assertSame(
            'superseded',
            RazorpayPayment::query()->where('invoice_id', $invoice->id)->value('payment_status')
        );

        Event::assertNotDispatched(AppointmentCancelled::class);

        $expired = $this->getJson('/api/chatbot/sessions/'.$sessionId)->assertOk();
        $this->assertSame('EXPIRED', $expired->json('state'));
        $lastText = (string) (collect($expired->json('messages'))->last()['text'] ?? '');
        $this->assertStringContainsString("payment wasn't completed", $lastText);

        $path = storage_path('framework/disease-chatbot-expired-dump.json');
        file_put_contents($path, json_encode([
            'http' => $expired->getStatusCode(),
            'body' => $expired->json(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fwrite(STDOUT, "\n===== EXPIRED DUMP =====\n".file_get_contents($path)."\n===== END =====\n");
    }

    public function test_expire_skips_paid_and_captured_in_flight(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Skip Paid', 'HIPSKIP1');
        Sanctum::actingAs($user);

        $sessionPaid = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id, '09:00 AM');
        $this->postJson('/api/chatbot/sessions/'.$sessionPaid.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $paidBooking = DoctorBooking::query()->latest('id')->firstOrFail();
        $paidBooking->update([
            'payment_status' => 'paid',
            'status' => DoctorBooking::STATUS_CONFIRMED,
        ]);
        Invoice::query()->whereKey($paidBooking->invoice_id)->update(['status' => 'completed']);

        $sessionCap = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id, '09:30 AM');
        $this->postJson('/api/chatbot/sessions/'.$sessionCap.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $capBooking = DoctorBooking::query()->where('id', '!=', $paidBooking->id)->firstOrFail();
        RazorpayPayment::query()
            ->where('invoice_id', $capBooking->invoice_id)
            ->update(['payment_status' => 'captured']);

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();

        $this->assertSame(
            DiseaseChatbotBooking::STATUS_CONFIRMED,
            DiseaseChatbotBooking::query()->where('doctor_booking_id', $paidBooking->id)->value('status')
        );
        $this->assertSame(
            DiseaseChatbotBooking::STATUS_PENDING_PAYMENT,
            DiseaseChatbotBooking::query()->where('doctor_booking_id', $capBooking->id)->value('status')
        );
        $this->assertSame(DoctorBooking::STATUS_PENDING, $capBooking->fresh()->status);
    }

    public function test_pending_payment_detector_skips_cancelled_chatbot_invoice(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Reminder Stop', 'HIPREM01');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();

        $invoice = Invoice::query()->firstOrFail();
        $this->assertSame('cancelled', $invoice->status);

        $detector = app(PendingPaymentDetector::class);
        $this->assertFalse($detector->isEligible($invoice->fresh()));
        $this->assertFalse(
            $detector->candidateQuery(now())->whereKey($invoice->id)->exists()
        );
    }

    public function test_non_chatbot_pending_booking_untouched_by_expire_command(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Plain', 'HIPPLAIN1');
        Sanctum::actingAs($user);

        $plain = DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'patient_id' => $person->id,
            'member_id' => $user->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['10:00 AM'],
            'status' => DoctorBooking::STATUS_PENDING,
            'payment_status' => 'pending',
            'name' => 'Plain',
            'mobile_number' => '1',
            'total_amount' => 100,
        ]));
        $invoice = Invoice::query()->create([
            'person_id' => $person->id,
            'status' => 'pending',
            'doctor_booking_id' => $plain->id,
            'total_amount' => 100,
            'amount' => 100,
        ]);
        $plain->update(['invoice_id' => $invoice->id]);

        Carbon::setTestNow(Carbon::parse('2026-10-17 09:00:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();

        $this->assertSame(DoctorBooking::STATUS_PENDING, $plain->fresh()->status);
        $this->assertSame('pending', $invoice->fresh()->status);
        $this->assertSame(0, DiseaseChatbotBooking::query()->count());
    }

    public function test_late_verify_revives_when_slot_free(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Late OK', 'HIPLATEOK');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $pending = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $orderId = (string) $pending->json('messages.0.payment.order_id');
        $invoiceId = (int) $pending->json('messages.0.payment.invoice_id');

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();
        $this->assertSame(DiseaseChatbotBooking::STATUS_EXPIRED, DiseaseChatbotBooking::query()->value('status'));

        $verify = $this->postJson('/api/invoices/verify-payment', [
            'invoice_id' => $invoiceId,
            'razorpay_payment_id' => 'pay_late_ok',
            'razorpay_order_id' => $orderId,
            'razorpay_signature' => 'sig_ok',
        ])->assertOk();

        $this->assertTrue((bool) $verify->json('success'));
        $this->assertSame(
            DiseaseChatbotBooking::STATUS_CONFIRMED,
            DiseaseChatbotBooking::query()->value('status')
        );
        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, DoctorBooking::query()->value('status'));
        $this->assertSame('completed', Invoice::query()->value('status'));
    }

    public function test_late_verify_slot_taken_returns_409_refund_required_and_critical_log(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Late Bad', 'HIPLATEBD');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id, '09:00 AM');
        $pending = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $orderId = (string) $pending->json('messages.0.payment.order_id');
        $invoiceId = (int) $pending->json('messages.0.payment.invoice_id');

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();

        DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['09:00 AM'],
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'payment_status' => 'paid',
            'name' => 'Taken',
            'mobile_number' => '2',
        ]));

        Log::spy();

        $conflict = $this->postJson('/api/invoices/verify-payment', [
            'invoice_id' => $invoiceId,
            'razorpay_payment_id' => 'pay_late_bad',
            'razorpay_order_id' => $orderId,
            'razorpay_signature' => 'sig_ok',
        ])->assertStatus(409);

        $dump = ['http' => $conflict->getStatusCode(), 'body' => $conflict->json()];
        $path = storage_path('framework/disease-chatbot-refund-required-409-dump.json');
        file_put_contents($path, json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fwrite(STDOUT, "\n===== 409 REFUND REQUIRED DUMP =====\n".file_get_contents($path)."\n===== END =====\n");

        $this->assertFalse((bool) $conflict->json('success'));
        $this->assertSame('slot_no_longer_available_refund_required', $conflict->json('code'));
        $this->assertSame($invoiceId, (int) $conflict->json('data.invoice_id'));
        $this->assertSame('pay_late_bad', (string) $conflict->json('data.razorpay_payment_id'));
        $this->assertSame(
            DiseaseChatbotBooking::STATUS_REFUND_REQUIRED,
            DiseaseChatbotBooking::query()->value('status')
        );
        Log::shouldHaveReceived('critical')->withArgs(function (string $message, array $context) use ($invoiceId) {
            return str_contains($message, 'refund required')
                && ($context['invoice_id'] ?? null) === $invoiceId
                && isset($context['razorpay_payment_id'])
                && ! isset($context['mobile'])
                && ! isset($context['email']);
        })->once();
    }

    public function test_late_verify_conflict_between_signature_and_transaction_returns_409(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Late Race', 'HIPLATERACE');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id, '09:00 AM');
        $pending = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $orderId = (string) $pending->json('messages.0.payment.order_id');
        $invoiceId = (int) $pending->json('messages.0.payment.invoice_id');

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();
        $this->assertSame(DiseaseChatbotBooking::STATUS_EXPIRED, DiseaseChatbotBooking::query()->value('status'));

        // Simulate a rival booking appearing after signature/capture checks, before revive txn.
        $this->bindRazorpayStub(function () use ($doctor, $hospital) {
            DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
                'doctor_id' => $doctor->id,
                'hospital_id' => $hospital->id,
                'booking_date' => '2026-10-17',
                'required_time_slots' => ['09:00 AM'],
                'status' => DoctorBooking::STATUS_CONFIRMED,
                'payment_status' => 'paid',
                'name' => 'RaceTaken',
                'mobile_number' => '3',
            ]));
        });

        Log::spy();
        $conflict = $this->postJson('/api/invoices/verify-payment', [
            'invoice_id' => $invoiceId,
            'razorpay_payment_id' => 'pay_late_race',
            'razorpay_order_id' => $orderId,
            'razorpay_signature' => 'sig_ok',
        ])->assertStatus(409);

        $this->assertSame('slot_no_longer_available_refund_required', $conflict->json('code'));
        $this->assertSame(
            DiseaseChatbotBooking::STATUS_REFUND_REQUIRED,
            DiseaseChatbotBooking::query()->value('status')
        );
        Log::shouldHaveReceived('critical')->once();
    }

    public function test_expire_run_caps_at_max_links_per_run(): void
    {
        config(['chatbot.expire_max_links_per_run' => 5, 'chatbot.expire_max_run_seconds' => 40]);
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Cap Links', 'HIPCAPLNK');

        for ($i = 0; $i < 8; $i++) {
            $booking = DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
                'doctor_id' => $doctor->id,
                'hospital_id' => $hospital->id,
                'patient_id' => $person->id,
                'member_id' => $user->id,
                'booking_date' => '2026-10-17',
                'required_time_slots' => ['09:00 AM'],
                'status' => DoctorBooking::STATUS_PENDING,
                'payment_status' => 'pending',
                'name' => 'Cap'.$i,
                'mobile_number' => (string) $i,
                'total_amount' => 100,
            ]));
            $invoice = Invoice::query()->create([
                'person_id' => $person->id,
                'status' => 'pending',
                'doctor_booking_id' => $booking->id,
                'total_amount' => 100,
                'amount' => 100,
            ]);
            $booking->update(['invoice_id' => $invoice->id]);
            DiseaseChatbotBooking::query()->create([
                'session_id' => (string) Str::uuid(),
                'doctor_booking_id' => $booking->id,
                'invoice_id' => $invoice->id,
                'status' => DiseaseChatbotBooking::STATUS_PENDING_PAYMENT,
                'expires_at' => Carbon::parse('2026-10-17 07:50:00', 'Asia/Kolkata'),
            ]);
        }

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $result = app(\App\Services\ChatbotUnpaidBookingExpiryService::class)->expireDue();

        $this->assertSame(5, $result['processed']);
        $this->assertTrue($result['stopped_early']);
        $this->assertSame(5, DiseaseChatbotBooking::query()->where('status', DiseaseChatbotBooking::STATUS_EXPIRED)->count());
        $this->assertSame(3, DiseaseChatbotBooking::query()->where('status', DiseaseChatbotBooking::STATUS_PENDING_PAYMENT)->count());
    }

    public function test_expire_run_stops_after_time_budget(): void
    {
        config(['chatbot.expire_max_links_per_run' => 50, 'chatbot.expire_max_run_seconds' => 1]);
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Cap Time', 'HIPCAPTIM');

        for ($i = 0; $i < 3; $i++) {
            $booking = DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
                'doctor_id' => $doctor->id,
                'hospital_id' => $hospital->id,
                'patient_id' => $person->id,
                'member_id' => $user->id,
                'booking_date' => '2026-10-17',
                'required_time_slots' => ['10:00 AM'],
                'status' => DoctorBooking::STATUS_PENDING,
                'payment_status' => 'pending',
                'name' => 'Time'.$i,
                'mobile_number' => '9'.$i,
                'total_amount' => 100,
            ]));
            $invoice = Invoice::query()->create([
                'person_id' => $person->id,
                'status' => 'pending',
                'doctor_booking_id' => $booking->id,
                'total_amount' => 100,
                'amount' => 100,
            ]);
            DiseaseChatbotBooking::query()->create([
                'session_id' => (string) Str::uuid(),
                'doctor_booking_id' => $booking->id,
                'invoice_id' => $invoice->id,
                'status' => DiseaseChatbotBooking::STATUS_PENDING_PAYMENT,
                'expires_at' => Carbon::parse('2026-10-17 07:50:00', 'Asia/Kolkata'),
            ]);
        }

        $service = new class(
            app(\App\Services\DoctorBookingStatusService::class),
            app(PaymentApiService::class),
            app(NotificationService::class),
        ) extends \App\Services\ChatbotUnpaidBookingExpiryService {
            public function expireOne(\App\Models\DiseaseChatbotBooking $link): string
            {
                usleep(600000); // 0.6s so a few links exceed 1s budget

                return 'skipped_other';
            }
        };

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $result = $service->expireDue();

        $this->assertTrue($result['stopped_early']);
        $this->assertLessThan(3, $result['processed']);
        $this->assertGreaterThanOrEqual(1, $result['processed']);
    }

    public function test_non_chatbot_verify_payment_unchanged_when_invoice_cancelled(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('No Link', 'HIPNOLINK');
        Sanctum::actingAs($user);

        $booking = DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'patient_id' => $person->id,
            'member_id' => $user->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['11:00 AM'],
            'status' => DoctorBooking::STATUS_CANCELLED,
            'payment_status' => 'pending',
            'name' => 'X',
            'mobile_number' => '1',
            'total_amount' => 100,
        ]));
        $invoice = Invoice::query()->create([
            'person_id' => $person->id,
            'status' => 'cancelled',
            'doctor_booking_id' => $booking->id,
            'total_amount' => 100,
            'amount' => 100,
        ]);
        Transactions::query()->create([
            'invoice_id' => $invoice->id,
            'status' => 'cancelled',
            'total_amount' => 100,
            'transaction_amount' => 100,
        ]);
        RazorpayPayment::query()->create([
            'invoice_id' => $invoice->id,
            'razorpay_order_id' => 'order_plain_1',
            'payment_status' => 'superseded',
            'amount_paid' => 100,
            'currency' => 'INR',
        ]);

        $this->assertSame(0, DiseaseChatbotBooking::query()->count());

        $response = $this->postJson('/api/invoices/verify-payment', [
            'invoice_id' => $invoice->id,
            'razorpay_payment_id' => 'pay_plain',
            'razorpay_order_id' => 'order_plain_1',
            'razorpay_signature' => 'sig',
        ])->assertStatus(400);

        $this->assertStringContainsString('already processed', (string) $response->json('message'));
        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertNull($response->json('code'));
    }

    public function test_verify_payment_normal_doctor_booking_without_link_row_succeeds(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Plain Verify', 'HIPPLNVFY');
        Sanctum::actingAs($user);

        $booking = DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'patient_id' => $person->id,
            'member_id' => $user->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['11:30 AM'],
            'status' => DoctorBooking::STATUS_PENDING,
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
            'payment_status' => 'pending',
            'name' => 'Plain',
            'mobile_number' => '1',
            'total_amount' => 100,
            'consultation_fee' => 100,
        ]));
        $invoice = Invoice::query()->create([
            'person_id' => $person->id,
            'primary_person_id' => $person->id,
            'status' => 'pending',
            'doctor_booking_id' => $booking->id,
            'total_amount' => 100,
            'amount' => 100,
        ]);
        $booking->update(['invoice_id' => $invoice->id]);
        Transactions::query()->create([
            'invoice_id' => $invoice->id,
            'status' => 'pending',
            'total_amount' => 100,
            'transaction_amount' => 100,
        ]);
        RazorpayPayment::query()->create([
            'invoice_id' => $invoice->id,
            'razorpay_order_id' => 'order_plain_ok',
            'payment_status' => 'created',
            'amount_paid' => 100,
            'currency' => 'INR',
        ]);

        $this->assertSame(0, DiseaseChatbotBooking::query()->count());

        $response = $this->postJson('/api/invoices/verify-payment', [
            'invoice_id' => $invoice->id,
            'razorpay_payment_id' => 'pay_plain_ok',
            'razorpay_order_id' => 'order_plain_ok',
            'razorpay_signature' => 'sig_ok',
        ])->assertOk();

        $this->assertTrue((bool) $response->json('success'));
        $this->assertSame('completed', $invoice->fresh()->status);
        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $booking->fresh()->status);
        $this->assertSame(0, DiseaseChatbotBooking::query()->count());
    }

    public function test_verify_payment_pharmacy_invoice_without_link_row_succeeds(): void
    {
        [$user, , , , $person] = $this->seedHospitalWithDoctor('Pharm Verify', 'HIPPHARVF');
        Sanctum::actingAs($user);

        $invoice = Invoice::query()->create([
            'person_id' => $person->id,
            'primary_person_id' => $person->id,
            'status' => 'pending',
            'pharmacy_order_id' => 9001,
            'doctor_booking_id' => null,
            'total_amount' => 250,
            'amount' => 250,
        ]);
        Transactions::query()->create([
            'invoice_id' => $invoice->id,
            'status' => 'pending',
            'total_amount' => 250,
            'transaction_amount' => 250,
        ]);
        RazorpayPayment::query()->create([
            'invoice_id' => $invoice->id,
            'razorpay_order_id' => 'order_pharm_ok',
            'payment_status' => 'created',
            'amount_paid' => 250,
            'currency' => 'INR',
        ]);

        $this->assertSame(0, DiseaseChatbotBooking::query()->count());
        $this->assertNull($invoice->doctor_booking_id);

        $response = $this->postJson('/api/invoices/verify-payment', [
            'invoice_id' => $invoice->id,
            'razorpay_payment_id' => 'pay_pharm_ok',
            'razorpay_order_id' => 'order_pharm_ok',
            'razorpay_signature' => 'sig_ok',
        ])->assertOk();

        $this->assertTrue((bool) $response->json('success'));
        $this->assertSame('completed', $invoice->fresh()->status);
        $this->assertSame(0, DiseaseChatbotBooking::query()->count());
        $this->assertNull($response->json('code'));
    }

    public function test_e2e_session_to_confirmed_via_verify_payment_finalize(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('E2E Flow', 'HIPE2EFLO');
        Sanctum::actingAs($user);

        Coins::query()->create([
            'person_id' => $person->id,
            'coins' => 50,
        ]);

        $states = [];

        $created = $this->postJson('/api/chatbot/sessions', [
            'hospital_id' => $hospital->id,
            'person_id' => $person->id,
        ])->assertOk();
        $sessionId = (string) $created->json('session_id');
        $states[] = 'start:'.$created->json('state');

        $menu = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertOk();
        $states[] = 'menu:'.$menu->json('state');

        $symptoms = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'symptoms', 'value' => ['Wheeze', 'Cough']],
        ])->assertOk();
        $states[] = 'symptoms:'.$symptoms->json('state');

        $yes = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();
        $states[] = 'results_yes:'.$yes->json('state');

        // Person may be auto-selected when session was opened with person_id.
        if ($yes->json('state') === 'CHOOSE_PERSON') {
            $personStep = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
                'input' => ['type' => 'person', 'value' => $person->id],
            ])->assertOk();
            $states[] = 'person:'.$personStep->json('state');
            $afterPerson = $personStep;
        } else {
            $states[] = 'person:skipped';
            $afterPerson = $yes;
        }

        if ($afterPerson->json('state') === 'CHOOSE_SPECIALITY') {
            $specialityId = (string) ($afterPerson->json('options.0.id') ?? '');
            $afterPerson = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
                'input' => ['type' => 'speciality', 'value' => $specialityId],
            ])->assertOk();
            $states[] = 'speciality:'.$afterPerson->json('state');
        }

        $doctorStep = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => $doctor->id],
        ])->assertOk();
        $states[] = 'doctor:'.$doctorStep->json('state');

        $dateStep = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'date', 'value' => '2026-10-17'],
        ])->assertOk();
        $states[] = 'date:'.$dateStep->json('state');

        $slotStep = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => '09:00 AM'],
        ])->assertOk();
        $states[] = 'slot:'.$slotStep->json('state');

        if ($slotStep->json('state') === 'ASK_COINS') {
            $coinsStep = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
                'input' => ['type' => 'option', 'value' => 'use_none'],
            ])->assertOk();
            $states[] = 'coins:'.$coinsStep->json('state');
        } else {
            $states[] = 'coins:skipped';
        }

        $summary = $this->getJson('/api/chatbot/sessions/'.$sessionId)->assertOk();
        $states[] = 'summary:'.$summary->json('state');
        $this->assertSame('CONFIRM_SUMMARY', $summary->json('state'));

        $confirm = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $states[] = 'confirm:'.$confirm->json('state');
        $this->assertSame('PAYMENT_PENDING', $confirm->json('state'));

        $orderId = (string) $confirm->json('messages.0.payment.order_id');
        $invoiceId = (int) $confirm->json('messages.0.payment.invoice_id');

        $notYet = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'payment_check', 'value' => 'payment_check'],
        ])->assertOk();
        $states[] = 'payment_check_unpaid:'.$notYet->json('state');
        $this->assertSame('PAYMENT_PENDING', $notYet->json('state'));

        $this->postJson('/api/invoices/verify-payment', [
            'invoice_id' => $invoiceId,
            'razorpay_payment_id' => 'pay_e2e_ok',
            'razorpay_order_id' => $orderId,
            'razorpay_signature' => 'sig_ok',
        ])->assertOk();

        $paid = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'payment_check', 'value' => 'payment_check'],
        ])->assertOk();
        $states[] = 'payment_check_paid:'.$paid->json('state');
        $this->assertSame('CONFIRMED', $paid->json('state'));

        $booking = DoctorBooking::query()->firstOrFail();
        $invoice = Invoice::query()->findOrFail($invoiceId);
        $link = DiseaseChatbotBooking::query()->firstOrFail();

        $redacted = [
            'state_sequence' => $states,
            'doctor_bookings' => [[
                'id' => $booking->id,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
                'invoice_id' => $booking->invoice_id,
                'booking_date' => optional($booking->booking_date)?->format('Y-m-d'),
                'required_time_slots' => $booking->required_time_slots,
            ]],
            'invoices' => [[
                'id' => $invoice->id,
                'status' => $invoice->status,
                'doctor_booking_id' => $invoice->doctor_booking_id,
                'total_amount' => (float) $invoice->total_amount,
            ]],
            'disease_chatbot_bookings' => [[
                'id' => $link->id,
                'status' => $link->status,
                'doctor_booking_id' => $link->doctor_booking_id,
                'invoice_id' => $link->invoice_id,
            ]],
        ];

        $path = storage_path('framework/disease-chatbot-e2e-confirmed-dump.json');
        file_put_contents($path, json_encode($redacted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fwrite(STDOUT, "\n===== E2E CONFIRMED DUMP =====\n".file_get_contents($path)."\n===== END =====\n");

        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $booking->status);
        $this->assertSame('completed', $invoice->status);
        $this->assertSame(DiseaseChatbotBooking::STATUS_CONFIRMED, $link->status);
        $this->assertContains('CONFIRMED', array_map(
            static fn (string $s) => substr($s, strrpos($s, ':') + 1),
            $states
        ));
    }

    public function test_refund_required_artisan_lists_links_without_personal_data(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Refund Cmd', 'HIPRFCMD1');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id, '09:00 AM');
        $pending = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $orderId = (string) $pending->json('messages.0.payment.order_id');
        $invoiceId = (int) $pending->json('messages.0.payment.invoice_id');

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();

        DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['09:00 AM'],
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'payment_status' => 'paid',
            'name' => 'Taken',
            'mobile_number' => '2',
        ]));

        $this->postJson('/api/invoices/verify-payment', [
            'invoice_id' => $invoiceId,
            'razorpay_payment_id' => 'pay_rf_cmd',
            'razorpay_order_id' => $orderId,
            'razorpay_signature' => 'sig_ok',
        ])->assertStatus(409);

        $link = DiseaseChatbotBooking::query()->firstOrFail();
        $this->assertSame(DiseaseChatbotBooking::STATUS_REFUND_REQUIRED, $link->status);

        Log::spy();
        $this->artisan('chatbot:refund-required', ['--no-notify' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Total refund_required: 1');

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($link, $invoiceId) {
            return $message === 'chatbot:refund-required listed'
                && ($context['count'] ?? null) === 1
                && in_array((int) $link->id, $context['link_ids'] ?? [], true)
                && in_array((int) $invoiceId, $context['invoice_ids'] ?? [], true)
                && ! isset($context['mobile'], $context['email'], $context['name']);
        })->once();
    }

    public function test_refund_required_notifies_once_then_skips(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name')->default('filament');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function ($table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->uuid('model_id');
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }

        $admin = HIPUser::withoutEvents(fn () => HIPUser::query()->create([
            'email' => 'admin-refund@example.com',
            'first_name' => 'Admin',
            'last_name' => 'Hip',
            'password' => bcrypt('secret'),
            'hip_id' => 'HIPADMIN1',
            'mobile_num' => '9800000001',
            'is_active' => true,
        ]));
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'super-admin-hip',
            'guard_name' => 'filament',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => HIPUser::class,
            'model_id' => $admin->id,
        ]);

        $link = DiseaseChatbotBooking::query()->create([
            'session_id' => (string) Str::uuid(),
            'doctor_booking_id' => 999001,
            'invoice_id' => null,
            'status' => DiseaseChatbotBooking::STATUS_REFUND_REQUIRED,
            'expires_at' => now()->subMinute(),
            'refund_notified_at' => null,
        ]);

        $notifyCount = 0;
        $this->mock(NotificationService::class, function ($mock) use (&$notifyCount, $admin) {
            $mock->shouldReceive('notifyUser')
                ->andReturnUsing(function (string $userId) use (&$notifyCount, $admin) {
                    $this->assertSame((string) $admin->id, $userId);
                    $notifyCount++;

                    return true;
                });
        });

        $this->artisan('chatbot:refund-required')->assertSuccessful();
        $this->assertSame(1, $notifyCount);
        $this->assertNotNull($link->fresh()->refund_notified_at);

        $this->artisan('chatbot:refund-required')->assertSuccessful();
        $this->assertSame(1, $notifyCount);
    }

    public function test_reconcile_captured_confirms_without_expiring(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Rec Capture', 'HIPRECCAP');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id, '09:00 AM');
        $pending = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $orderId = (string) $pending->json('messages.0.payment.order_id');
        $invoiceId = (int) $pending->json('messages.0.payment.invoice_id');

        $this->fakeRazorpayOrderPayments([[
            'id' => 'pay_reconcile_cap',
            'status' => 'captured',
            'amount' => 51500,
            'method' => 'upi',
            'fee' => 0,
            'tax' => 0,
        ]]);

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();

        $this->assertSame(
            DiseaseChatbotBooking::STATUS_CONFIRMED,
            DiseaseChatbotBooking::query()->value('status')
        );
        $this->assertSame('completed', Invoice::query()->find($invoiceId)->status);
        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, DoctorBooking::query()->value('status'));
        $this->assertSame(
            PaymentApiService::SERVER_RECONCILE_SIGNATURE,
            RazorpayPayment::query()->where('razorpay_order_id', $orderId)->value('razorpay_signature')
        );
    }

    public function test_reconcile_none_expires(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Rec None', 'HIPRECNO');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();

        $this->fakeRazorpayOrderPayments([]);

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();

        $this->assertSame(DiseaseChatbotBooking::STATUS_EXPIRED, DiseaseChatbotBooking::query()->value('status'));
    }

    public function test_reconcile_http_500_skips_within_max_window(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Rec 500', 'HIPREC500');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();

        $this->fakeRazorpayOrderPayments([], 500);

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();

        $this->assertSame(
            DiseaseChatbotBooking::STATUS_PENDING_PAYMENT,
            DiseaseChatbotBooking::query()->value('status')
        );
        $this->assertSame(DoctorBooking::STATUS_PENDING, DoctorBooking::query()->value('status'));
    }

    public function test_reconcile_http_500_expires_after_max_minutes(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Rec Max', 'HIPRECMAX');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();

        $booking = DoctorBooking::query()->firstOrFail();
        $booking->forceFill(['created_at' => Carbon::parse('2026-10-17 07:00:00', 'Asia/Kolkata')])->save();

        $this->fakeRazorpayOrderPayments([], 500);

        Log::spy();
        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();

        $this->assertSame(DiseaseChatbotBooking::STATUS_EXPIRED, DiseaseChatbotBooking::query()->value('status'));
        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context) {
            return str_contains($message, 'past max minutes')
                && isset($context['disease_chatbot_booking_id'], $context['invoice_id'])
                && ! isset($context['mobile'], $context['email']);
        })->atLeast()->once();
    }

    public function test_reconcile_authorized_skips(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Rec Auth', 'HIPRECAUTH');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();

        $this->fakeRazorpayOrderPayments([[
            'id' => 'pay_auth_only',
            'status' => 'authorized',
            'amount' => 51500,
            'method' => 'card',
        ]]);

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();

        $this->assertSame(
            DiseaseChatbotBooking::STATUS_PENDING_PAYMENT,
            DiseaseChatbotBooking::query()->value('status')
        );
    }

    public function test_client_verify_after_reconcile_is_idempotent_success(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Rec Verify', 'HIPRECVFY');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id, '09:00 AM');
        $pending = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $orderId = (string) $pending->json('messages.0.payment.order_id');
        $invoiceId = (int) $pending->json('messages.0.payment.invoice_id');

        $this->fakeRazorpayOrderPayments([[
            'id' => 'pay_after_rec',
            'status' => 'captured',
            'amount' => 51500,
            'method' => 'upi',
            'fee' => 0,
            'tax' => 0,
        ]]);

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();
        $this->assertSame('completed', Invoice::query()->find($invoiceId)->status);

        $verify = $this->postJson('/api/invoices/verify-payment', [
            'invoice_id' => $invoiceId,
            'razorpay_payment_id' => 'pay_after_rec',
            'razorpay_order_id' => $orderId,
            'razorpay_signature' => 'sig_client_later',
        ])->assertOk();

        $this->assertTrue((bool) $verify->json('success'));
        $this->assertSame('completed', Invoice::query()->find($invoiceId)->status);
        $this->assertSame(
            'sig_client_later',
            RazorpayPayment::query()->where('razorpay_order_id', $orderId)->value('razorpay_signature')
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function fakeRazorpayOrderPayments(array $items, int $status = 200): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($items, $status) {
            if (str_contains($request->url(), '/orders/') && str_contains($request->url(), '/payments')) {
                return Http::response([
                    'entity' => 'collection',
                    'count' => count($items),
                    'items' => $items,
                ], $status);
            }

            return Http::response(['error' => 'unexpected '.$request->url()], 500);
        });
    }

    public function test_schedule_registers_expire_unpaid_command(): void
    {
        $events = collect(app(Schedule::class)->events());
        $match = $events->first(fn ($e) => str_contains((string) $e->command, 'chatbot:expire-unpaid-bookings')
            || str_contains((string) ($e->description ?? ''), 'chatbot:expire-unpaid-bookings')
            || str_contains(json_encode($e), 'chatbot:expire-unpaid-bookings'));

        $this->assertNotNull($match, 'chatbot:expire-unpaid-bookings must be scheduled');
        $refund = $events->first(fn ($e) => str_contains((string) $e->command, 'chatbot:refund-required')
            || str_contains(json_encode($e), 'chatbot:refund-required'));
        $this->assertNotNull($refund, 'chatbot:refund-required must be scheduled hourly');
        $this->assertTrue(
            str_contains(file_get_contents(base_path('routes/console.php')), 'chatbot:expire-unpaid-bookings')
        );
        $this->assertTrue(
            str_contains(file_get_contents(base_path('routes/console.php')), 'chatbot:refund-required')
        );
        $this->assertTrue(
            str_contains(file_get_contents(base_path('routes/console.php')), 'withoutOverlapping()')
        );
    }

    public function test_payment_check_on_expired_link_returns_expired_state(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('PayCheck Exp', 'HIPPCEXP');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $this->artisan('chatbot:expire-unpaid-bookings')->assertSuccessful();

        $check = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'payment_check', 'value' => 'payment_check'],
        ])->assertOk();
        $this->assertSame('EXPIRED', $check->json('state'));
        $this->assertSame('choose_another_time', $check->json('options.0.id'));
    }

    private function reachConfirmSummary(
        int $hospitalId,
        string $personId,
        string $doctorId,
        string $slot = '09:00 AM'
    ): string {
        $sessionId = $this->postJson('/api/chatbot/sessions', [
            'hospital_id' => $hospitalId,
            'person_id' => $personId,
        ])->assertOk()->json('session_id');

        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'symptoms', 'value' => ['Wheeze', 'Cough']],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'doctor', 'value' => $doctorId],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'date', 'value' => '2026-10-17'],
        ])->assertOk();
        $afterSlot = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => $slot],
        ])->assertOk();
        if ($afterSlot->json('state') === 'ASK_COINS') {
            $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
                'input' => ['type' => 'option', 'value' => 'use_none'],
            ])->assertOk();
        }

        return $sessionId;
    }

    /** @return array{0: HIPUser, 1: Hospital, 2: SpecialitiesMaster, 3: Doctor, 4: Persons} */
    private function seedHospitalWithDoctor(string $hospitalName = 'Expiry Hospital', string $hipId = 'HIPEXP01'): array
    {
        $user = $this->member('expiry@example.com', $hipId);
        $person = $this->person($user, true);
        $hospital = Hospital::withoutEvents(fn () => Hospital::query()->create(['name' => $hospitalName]));
        $master = $this->master('Cardiology', 'CARD'.$hipId, [
            [
                'name' => 'Asthma',
                'symptoms' => ['Wheeze', 'Cough'],
                'description' => 'Airways',
                'emergency' => false,
            ],
        ]);
        $dept = $this->department((int) $hospital->id, (int) $master->id, 'active');
        $doctor = Doctor::withoutEvents(function () use ($hospital, $master) {
            $doctor = new Doctor();
            $doctor->forceFill([
                'name' => 'Dr Available',
                'status' => 'active',
                'consultation_fee' => 500,
                'working_since' => '2018',
                'qualifications' => [],
                'speciality' => [(int) $master->id],
                'hospital_ids' => [(int) $hospital->id],
            ]);
            $doctor->id = (string) Str::uuid();
            $doctor->save();

            return $doctor;
        });
        $procedure = Procedure::withoutEvents(fn () => Procedure::query()->create([
            'procedure_name' => 'Consult',
            'speciality_id' => $dept->id,
            'hospital_id' => $hospital->id,
            'status' => 'active',
        ]));
        DoctorAssignment::withoutEvents(fn () => DoctorAssignment::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'status' => 'active',
            'procedure_ids' => [(int) $procedure->id],
            'time_slots' => [
                ['day' => 'Saturday', 'start' => '09:00', 'end' => '12:00'],
            ],
        ]));

        return [$user, $hospital, $master, $doctor, $person];
    }

    private function member(string $email, string $hipId): HIPUser
    {
        return HIPUser::withoutEvents(fn () => HIPUser::query()->create([
            'email' => $email,
            'first_name' => 'Exp',
            'last_name' => 'User',
            'password' => bcrypt('secret'),
            'hip_id' => $hipId,
            'mobile_num' => '9800000099',
            'is_active' => true,
        ]));
    }

    private function person(HIPUser $user, bool $primary): Persons
    {
        return Persons::withoutEvents(function () use ($user, $primary) {
            $person = Persons::query()->create([
                'first_name' => 'Pat',
                'last_name' => 'IENT',
                'hip_user_id' => $user->id,
                'is_primary' => $primary,
                'is_active' => true,
                'mobile' => '9800000098',
            ]);
            if ($primary && ($person->parent_id === null || $person->parent_id === '')) {
                $person->parent_id = $person->id;
                $person->save();
            }

            return $person->fresh();
        });
    }

    private function master(string $name, string $code, array $diseases): SpecialitiesMaster
    {
        return SpecialitiesMaster::withoutEvents(function () use ($name, $code, $diseases) {
            $master = new SpecialitiesMaster();
            $master->forceFill([
                'name' => $name,
                'code' => $code,
                'status' => 'active',
                'diseases' => $diseases,
            ]);
            $master->save();

            return $master->fresh();
        });
    }

    private function department(int $hospitalId, int $masterId, string $status): Speciality
    {
        return Speciality::withoutEvents(function () use ($hospitalId, $masterId, $status) {
            $row = new Speciality();
            $row->forceFill([
                'speciality_name' => 'Dept '.$masterId,
                'speciality_code' => 'D'.$masterId.$hospitalId,
                'department_category' => 'Dept '.$masterId,
                'status' => $status,
                'hospital_id' => $hospitalId,
                'organization_id' => 1,
                'speciality_master_id' => $masterId,
            ]);
            $row->save();

            return $row->fresh();
        });
    }

    private function bindRazorpayStub(?callable $onPaymentFetch = null): void
    {
        $GLOBALS['pharmacy_rzp_seq'] = 0;
        $GLOBALS['pharmacy_rzp_on_payment_fetch'] = $onPaymentFetch;
        unset($GLOBALS['pharmacy_rzp_reject_signature'], $GLOBALS['pharmacy_rzp_payment_status'], $GLOBALS['pharmacy_rzp_payment_amount']);
        $order = new class {
            public function create(array $attributes): object
            {
                $GLOBALS['pharmacy_rzp_seq']++;

                return (object) ['id' => 'order_test_'.$GLOBALS['pharmacy_rzp_seq']];
            }
        };
        $utility = new class {
            public function verifyPaymentSignature(array $attributes): void
            {
                if (! empty($GLOBALS['pharmacy_rzp_reject_signature'])) {
                    throw new \Razorpay\Api\Errors\SignatureVerificationError('Invalid signature passed');
                }
            }
        };
        $payment = new class {
            public ?string $method = 'upi';
            public $card_id = null;
            public $bank = null;
            public $wallet = null;
            public $vpa = null;
            public int $fee = 0;
            public int $tax = 0;
            public ?string $status = 'captured';
            public ?int $amount = null;

            public function fetch(string $paymentId): self
            {
                if (! empty($GLOBALS['pharmacy_rzp_on_payment_fetch']) && is_callable($GLOBALS['pharmacy_rzp_on_payment_fetch'])) {
                    ($GLOBALS['pharmacy_rzp_on_payment_fetch'])($paymentId);
                }
                if (array_key_exists('pharmacy_rzp_payment_status', $GLOBALS)) {
                    $this->status = $GLOBALS['pharmacy_rzp_payment_status'];
                }
                if (array_key_exists('pharmacy_rzp_payment_amount', $GLOBALS)) {
                    $this->amount = $GLOBALS['pharmacy_rzp_payment_amount'] === null
                        ? null
                        : (int) $GLOBALS['pharmacy_rzp_payment_amount'];
                }

                return $this;
            }
        };
        $api = new class($order, $utility, $payment) extends Api {
            public function __construct(private object $orderEntity, private object $utilityEntity, private object $paymentEntity)
            {
            }

            public function __get($name)
            {
                return match ($name) {
                    'order' => $this->orderEntity,
                    'utility' => $this->utilityEntity,
                    'payment' => $this->paymentEntity,
                    default => null,
                };
            }
        };

        $service = app(PaymentApiService::class);
        $client = new ReflectionProperty(PaymentApiService::class, 'razorpayApi');
        $client->setValue($service, $api);
        $key = new ReflectionProperty(PaymentApiService::class, 'razorpayKeyId');
        $key->setValue($service, 'rzp_test');
        app()->instance(PaymentApiService::class, $service);
    }

    private function bootSchema(): void
    {
        if (self::$schemaReady && Schema::hasTable('disease_chatbot_bookings') && Schema::hasTable('doctor_booking_statuses')) {
            if (! Schema::hasColumn('disease_chatbot_bookings', 'refund_notified_at')) {
                Schema::table('disease_chatbot_bookings', function ($table) {
                    $table->timestamp('refund_notified_at')->nullable();
                });
            }

            return;
        }

        foreach ([
            'disease_chatbot_bookings', 'disease_chatbot_messages', 'disease_chatbot_sessions',
            'doctor_booking_statuses', 'razorpay_payments', 'transactions', 'invoices', 'doctor_bookings',
            'doctor_reviews', 'doctor_assignments', 'procedures', 'doctors', 'specialities',
            'specialities_masters', 'coins', 'persons', 'hospitals', 'healthinpocket_users', 'settings',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('healthinpocket_users', function ($table) {
            $table->uuid('id')->primary();
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('password')->nullable();
            $table->string('hip_id')->nullable();
            $table->string('mobile_num')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('persons', function ($table) {
            $table->uuid('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('relationship')->nullable();
            $table->uuid('hip_user_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('coins', function ($table) {
            $table->id();
            $table->uuid('person_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->integer('coins')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('hospitals', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
        Schema::create('settings', function ($table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->nullable();
            $table->string('group')->nullable();
            $table->timestamps();
        });
        Schema::create('specialities_masters', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->json('diseases')->nullable();
            $table->string('display_image')->nullable();
            $table->string('status')->default('inactive');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('specialities', function ($table) {
            $table->id();
            $table->string('speciality_name');
            $table->string('speciality_code');
            $table->string('department_category');
            $table->string('status')->default('inactive');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('speciality_master_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('procedures', function ($table) {
            $table->id();
            $table->string('procedure_name')->nullable();
            $table->unsignedBigInteger('speciality_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('doctors', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('status')->default('inactive');
            $table->string('doctor_image')->nullable();
            $table->json('qualifications')->nullable();
            $table->json('speciality')->nullable();
            $table->json('hospital_ids')->nullable();
            $table->string('working_since')->nullable();
            $table->decimal('consultation_fee', 10, 2)->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('doctor_assignments', function ($table) {
            $table->id();
            $table->uuid('doctor_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('day')->nullable();
            $table->date('date')->nullable();
            $table->json('time_slots')->nullable();
            $table->json('procedure_ids')->nullable();
            $table->string('status')->default('active');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('doctor_bookings', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->uuid('member_id')->nullable();
            $table->uuid('patient_id')->nullable();
            $table->string('relationship')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->uuid('doctor_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('appointment_type')->nullable();
            $table->string('consultation_type')->nullable();
            $table->date('booking_date')->nullable();
            $table->json('required_time_slots')->nullable();
            $table->string('reason_of_visit')->nullable();
            $table->text('message')->nullable();
            $table->text('purpose')->nullable();
            $table->boolean('is_follow_up')->default(false);
            $table->boolean('is_coins_applied')->default(false);
            $table->unsignedInteger('coins_used')->default(0);
            $table->decimal('consultation_fee', 12, 2)->nullable();
            $table->decimal('service_charges', 12, 2)->nullable();
            $table->decimal('total_discount', 12, 2)->nullable();
            $table->decimal('amount_after_discount', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->boolean('is_online_payment')->default(false);
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('status')->default('pending');
            $table->string('appointment_status')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('doctor_booking_statuses', function ($table) {
            $table->id();
            $table->unsignedBigInteger('doctor_booking_id');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->uuid('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('notes_by')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('doctor_reviews', function ($table) {
            $table->id();
            $table->uuid('doctor_id');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('invoices', function ($table) {
            $table->id();
            $table->uuid('primary_person_id')->nullable();
            $table->uuid('person_id')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('amount', 12, 2)->nullable();
            $table->decimal('total_gst', 12, 2)->nullable();
            $table->decimal('service_charges', 12, 2)->nullable();
            $table->decimal('payment_gateway_charges', 12, 2)->nullable();
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedInteger('coins_applied')->default(0);
            $table->unsignedInteger('coins_earned')->default(0);
            $table->unsignedBigInteger('doctor_booking_id')->nullable();
            $table->unsignedBigInteger('second_opinion_id')->nullable();
            $table->unsignedBigInteger('diagnostic_test_booking_id')->nullable();
            $table->unsignedBigInteger('pharmacy_order_id')->nullable();
            $table->text('service_types')->nullable();
            $table->text('invoice_details')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('transactions', function ($table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('status')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->text('service_types')->nullable();
            $table->text('invoice_details')->nullable();
            $table->decimal('transaction_amount', 12, 2)->default(0);
            $table->decimal('service_charges', 12, 2)->default(0);
            $table->decimal('payment_gateway_charges', 12, 2)->default(0);
            $table->decimal('total_gst', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('razorpay_payments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedInteger('coins_applied')->default(0);
            $table->string('currency')->nullable();
            $table->string('bank')->nullable();
            $table->string('wallet')->nullable();
            $table->string('vpa')->nullable();
            $table->string('card_last4')->nullable();
            $table->string('card_network')->nullable();
            $table->decimal('razorpay_fee', 12, 2)->nullable();
            $table->decimal('razorpay_tax', 12, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('disease_chatbot_sessions', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('person_id')->nullable();
            $table->unsignedBigInteger('hospital_id');
            $table->string('state');
            $table->json('context')->nullable();
            $table->string('status');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('disease_chatbot_messages', function ($table) {
            $table->id();
            $table->uuid('session_id');
            $table->string('sender');
            $table->string('type');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
        Schema::create('disease_chatbot_bookings', function ($table) {
            $table->id();
            $table->uuid('session_id');
            $table->unsignedBigInteger('doctor_booking_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('status');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('refund_notified_at')->nullable();
            $table->timestamps();
            $table->unique('session_id');
            $table->unique('doctor_booking_id');
        });

        self::$schemaReady = true;
    }
}
