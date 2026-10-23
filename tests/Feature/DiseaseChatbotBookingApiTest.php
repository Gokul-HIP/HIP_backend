<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\Coins;
use App\Models\DiseaseChatbotBooking;
use App\Models\DiseaseChatbotSession;
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
use App\Models\Setting;
use App\Models\SpecialitiesMaster;
use App\Models\Speciality;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\Services\InvoiceGeneratedDispatcher;
use App\Services\Api\PaymentApiService;
use App\Services\DoctorNotificationService;
use App\Services\FamilyPackageService;
use App\Services\InvoiceFeeCalculator;
use App\Services\MembershipEntitlementService;
use App\Services\NotificationService;
use App\Services\RewardTierService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Razorpay\Api\Api;
use ReflectionProperty;
use Tests\TestCase;

class DiseaseChatbotBookingApiTest extends TestCase
{
    private static bool $schemaReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Disease chatbot booking tests must use sqlite.');
        }

        $this->bootSchema();
        $this->withoutMiddleware(EnsureAccountIsActive::class);

        putenv('RAZORPAY_KEY_ID=rzp_test_key');
        putenv('RAZORPAY_KEY_SECRET=rzp_test_secret');
        $_ENV['RAZORPAY_KEY_ID'] = 'rzp_test_key';
        $_ENV['RAZORPAY_KEY_SECRET'] = 'rzp_test_secret';
        putenv('AMOUNT_FOR_ONE_COIN=10');
        putenv('AMOUNT_For_ONE_COIN=10');
        $_ENV['AMOUNT_FOR_ONE_COIN'] = '10';
        $_ENV['AMOUNT_For_ONE_COIN'] = '10';

        config([
            'chatbot.disease_enabled' => true,
            'chatbot.ai_matching_enabled' => false,
            'chatbot.slot_minutes' => 30,
            'chatbot.slot_lead_minutes' => 30,
            'chatbot.slot_horizon_days' => 14,
            'chatbot.payment_hold_minutes' => 15,
            'chatbot.slot_hold_minutes' => 15,
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

        Event::fake([AppointmentBooked::class]);

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyUser')->andReturn(true);
        });
        $this->mock(DoctorNotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyDoctorOfNewBooking')->andReturnNull();
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
            'disease_chatbot_bookings',
            'disease_chatbot_messages',
            'disease_chatbot_sessions',
            'razorpay_payments',
            'transactions',
            'invoices',
            'doctor_bookings',
            'doctor_reviews',
            'doctor_assignments',
            'procedures',
            'doctors',
            'specialities',
            'specialities_masters',
            'coins',
            'persons',
            'hospitals',
            'healthinpocket_users',
            'settings',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        Setting::clearCache();
        Setting::set('amount_for_one_coin', 10, 'float', 'payment');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_full_path_to_payment_pending_creates_booking_and_payment_message(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor();
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $confirm = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();

        $this->assertSame('PAYMENT_PENDING', $confirm->json('state'));
        $this->assertSame('payment', $confirm->json('messages.0.type'));
        $this->assertTrue((bool) $confirm->json('messages.0.payment.requires_payment'));
        $this->assertNotEmpty($confirm->json('messages.0.payment.order_id'));

        $this->assertSame(1, DoctorBooking::query()->count());
        $this->assertSame(1, DiseaseChatbotBooking::query()->count());

        $booking = DoctorBooking::query()->first();
        $link = DiseaseChatbotBooking::query()->first();
        $this->assertSame((int) $booking->id, (int) $link->doctor_booking_id);
        $this->assertSame(DiseaseChatbotBooking::STATUS_PENDING_PAYMENT, $link->status);
        $this->assertSame($doctor->id, $booking->doctor_id);
        $this->assertSame('pending', $booking->payment_status);
        $this->assertNotNull($booking->invoice_id);
    }

    public function test_double_confirm_and_same_client_message_id_create_one_booking(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor();
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);

        $first = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
            'client_message_id' => 'confirm-once',
        ])->assertOk();
        $this->assertSame('PAYMENT_PENDING', $first->json('state'));

        $idempotent = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
            'client_message_id' => 'confirm-once',
        ])->assertOk();
        $this->assertSame('PAYMENT_PENDING', $idempotent->json('state'));
        $this->assertSame($first->json('messages.0.payment.order_id'), $idempotent->json('messages.0.payment.order_id'));

        // Re-enter summary so a second Confirm hits ChatbotBookingService::book idempotency.
        DiseaseChatbotSession::query()->whereKey($sessionId)->update([
            'state' => DiseaseChatbotSession::STATE_CONFIRM_SUMMARY,
        ]);
        $again = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $this->assertSame('PAYMENT_PENDING', $again->json('state'));

        $this->assertSame(1, DoctorBooking::query()->count());
        $this->assertSame(1, DiseaseChatbotBooking::query()->count());
    }

    public function test_two_sessions_race_same_slot_second_gets_422(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor();
        Sanctum::actingAs($user);

        $sessionA = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id, '09:00 AM');
        $sessionB = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id, '09:00 AM');

        $this->postJson('/api/chatbot/sessions/'.$sessionA.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk()->assertJsonPath('state', 'PAYMENT_PENDING');

        $race = $this->postJson('/api/chatbot/sessions/'.$sessionB.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertStatus(422);

        $this->assertSame(422, $race->json('status'));
        $this->assertSame('CHOOSE_SLOT', $race->json('state'));
        $this->assertSame('That time was just taken. Please pick another.', $race->json('messages.0.text'));
        $this->assertSame(1, DoctorBooking::query()->count());
        $this->assertSame(1, DiseaseChatbotBooking::query()->count());
    }

    public function test_coins_used_and_not_used_paths_match_invoice_total(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Coin Hospital', 'HIPCOIN01');
        Coins::query()->create(['person_id' => $person->id, 'coins' => 100]);
        Sanctum::actingAs($user);

        $sessionMax = $this->reachAskCoins($hospital->id, $person->id, $doctor->id);
        $ask = $this->getJsonSessionState($sessionMax);
        $this->assertSame('ASK_COINS', $ask);

        $summaryMax = $this->postJson('/api/chatbot/sessions/'.$sessionMax.'/reply', [
            'input' => ['type' => 'option', 'value' => 'use_max'],
        ])->assertOk();
        $this->assertSame('CONFIRM_SUMMARY', $summaryMax->json('state'));
        $payableMax = (float) $summaryMax->json('messages.0.breakdown.total_payable');
        $this->assertSame(50, (int) $summaryMax->json('messages.0.coins_applied'));
        $this->assertSame(15.0, $payableMax);

        $this->postJson('/api/chatbot/sessions/'.$sessionMax.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $invoiceMax = Invoice::query()->latest('id')->first();
        $this->assertNotNull($invoiceMax);
        $this->assertSame($payableMax, (float) $invoiceMax->total_amount);

        DoctorBooking::query()->delete();
        DiseaseChatbotBooking::query()->delete();
        Invoice::query()->delete();
        DB::table('transactions')->delete();
        DB::table('razorpay_payments')->delete();

        $sessionNone = $this->reachAskCoins($hospital->id, $person->id, $doctor->id, '09:30 AM');
        $summaryNone = $this->postJson('/api/chatbot/sessions/'.$sessionNone.'/reply', [
            'input' => ['type' => 'option', 'value' => 'use_none'],
        ])->assertOk();
        $this->assertSame('CONFIRM_SUMMARY', $summaryNone->json('state'));
        $payableNone = (float) $summaryNone->json('messages.0.breakdown.total_payable');
        $this->assertSame(0, (int) $summaryNone->json('messages.0.coins_applied'));
        $this->assertSame(515.0, $payableNone);

        $this->postJson('/api/chatbot/sessions/'.$sessionNone.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $invoiceNone = Invoice::query()->latest('id')->first();
        $this->assertNotNull($invoiceNone);
        $this->assertSame($payableNone, (float) $invoiceNone->total_amount);
    }

    public function test_patient_not_in_family_returns_403_when_confirming(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor();
        $stranger = $this->member('stranger@example.com', 'HIPSTR01');
        $foreign = $this->person($stranger, true);
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        DiseaseChatbotSession::query()->whereKey($sessionId)->update(['person_id' => $foreign->id]);

        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertStatus(403)
            ->assertJsonPath('message', 'You can only book for yourself or a family member.');

        $this->assertSame(0, DoctorBooking::query()->count());
    }

    public function test_ask_coins_skipped_at_zero_balance(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor();
        Coins::query()->create(['person_id' => $person->id, 'coins' => 0]);
        Sanctum::actingAs($user);

        $sessionId = $this->beginChatSession($hospital->id, $person->id);
        $this->advanceToSlotList($sessionId, $doctor->id);

        $summary = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => '09:00 AM'],
        ])->assertOk();

        $this->assertSame('CONFIRM_SUMMARY', $summary->json('state'));
        $this->assertSame('summary', $summary->json('messages.0.type'));
        $this->assertSame(0, (int) $summary->json('messages.0.coins_applied'));
    }

    public function test_payment_check_not_paid_then_confirmed_after_marking_paid(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor();
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk()->assertJsonPath('state', 'PAYMENT_PENDING');

        $notPaid = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'payment_check', 'value' => 'payment_check'],
        ])->assertOk();
        $this->assertSame('PAYMENT_PENDING', $notPaid->json('state'));
        $this->assertSame('payment', $notPaid->json('input.type'));

        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'payment', 'value' => 'check'],
        ])->assertOk()->assertJsonPath('state', 'PAYMENT_PENDING');
        $this->assertSame("We haven't received your payment yet.", $notPaid->json('messages.0.text'));

        $booking = DoctorBooking::query()->firstOrFail();
        $invoice = Invoice::query()->findOrFail($booking->invoice_id);
        $booking->update([
            'payment_status' => 'paid',
            'status' => DoctorBooking::STATUS_CONFIRMED,
        ]);
        $invoice->update(['status' => 'completed']);

        $paid = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'payment_check', 'value' => 'payment_check'],
        ])->assertOk();

        $this->assertSame('CONFIRMED', $paid->json('state'));
        $this->assertSame('confirmation', $paid->json('messages.0.type'));
        $this->assertSame(
            DiseaseChatbotBooking::STATUS_CONFIRMED,
            DiseaseChatbotBooking::query()->first()->status
        );
    }

    public function test_free_consult_with_zero_fee_goes_to_confirmed(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Free Hospital', 'HIPFREE1');
        Doctor::withoutEvents(fn () => $doctor->forceFill(['consultation_fee' => 0])->save());
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $confirm = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();

        $this->assertSame('CONFIRMED', $confirm->json('state'));
        $this->assertSame('confirmation', $confirm->json('messages.0.type'));
        $this->assertSame(1, DoctorBooking::query()->count());
        $booking = DoctorBooking::query()->first();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame(DoctorBooking::STATUS_CONFIRMED, $booking->status);
        $this->assertSame(0.0, (float) $booking->total_amount);
        $this->assertSame(
            DiseaseChatbotBooking::STATUS_CONFIRMED,
            DiseaseChatbotBooking::query()->first()->status
        );
    }

    public function test_dumps_booking_flow_real_json(): void
    {
        $dump = [];

        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Dump Hospital', 'HIPDUMP1');
        $dependent = Persons::withoutEvents(fn () => Persons::query()->create([
            'first_name' => 'Kid',
            'last_name' => 'Member',
            'hip_user_id' => null,
            'parent_id' => $person->id,
            'is_primary' => false,
            'is_active' => true,
            'relationship' => 'Child',
            'mobile' => '9800000002',
        ]));
        Sanctum::actingAs($user);

        $choosePersonSession = $this->beginChatSession($hospital->id, null);
        $this->postJson('/api/chatbot/sessions/'.$choosePersonSession.'/reply', [
            'input' => ['type' => 'option', 'value' => 'check_disease'],
        ])->assertOk();
        $this->postJson('/api/chatbot/sessions/'.$choosePersonSession.'/reply', [
            'input' => ['type' => 'symptoms', 'value' => ['Wheeze', 'Cough']],
        ])->assertOk();
        $choosePerson = $this->postJson('/api/chatbot/sessions/'.$choosePersonSession.'/reply', [
            'input' => ['type' => 'option', 'value' => 'yes'],
        ]);
        $dump['choose_person'] = ['http' => $choosePerson->getStatusCode(), 'body' => $choosePerson->json()];

        Coins::query()->create(['person_id' => $person->id, 'coins' => 100]);
        $askSession = $this->beginChatSession($hospital->id, $person->id);
        $this->advanceToSlotList($askSession, $doctor->id);
        $askCoins = $this->postJson('/api/chatbot/sessions/'.$askSession.'/reply', [
            'input' => ['type' => 'slot', 'value' => '09:00 AM'],
        ]);
        $dump['ask_coins'] = ['http' => $askCoins->getStatusCode(), 'body' => $askCoins->json()];

        $confirmSummary = $this->postJson('/api/chatbot/sessions/'.$askSession.'/reply', [
            'input' => ['type' => 'option', 'value' => 'use_none'],
        ]);
        $dump['confirm_summary'] = ['http' => $confirmSummary->getStatusCode(), 'body' => $confirmSummary->json()];

        $paymentPending = $this->postJson('/api/chatbot/sessions/'.$askSession.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ]);
        $dump['payment_pending'] = ['http' => $paymentPending->getStatusCode(), 'body' => $paymentPending->json()];

        $notPaid = $this->postJson('/api/chatbot/sessions/'.$askSession.'/reply', [
            'input' => ['type' => 'payment_check', 'value' => 'payment_check'],
        ]);
        $dump['not_paid'] = ['http' => $notPaid->getStatusCode(), 'body' => $notPaid->json()];

        $booking = DoctorBooking::query()->firstOrFail();
        $invoice = Invoice::query()->findOrFail($booking->invoice_id);
        $booking->update(['payment_status' => 'paid', 'status' => DoctorBooking::STATUS_CONFIRMED]);
        $invoice->update(['status' => 'completed']);
        $confirmed = $this->postJson('/api/chatbot/sessions/'.$askSession.'/reply', [
            'input' => ['type' => 'payment_check', 'value' => 'payment_check'],
        ]);
        $dump['confirmed'] = ['http' => $confirmed->getStatusCode(), 'body' => $confirmed->json()];

        $raceSession = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id, '09:30 AM');
        DoctorBooking::withoutEvents(fn () => DoctorBooking::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'booking_date' => '2026-10-17',
            'required_time_slots' => ['09:30 AM'],
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'name' => 'Race',
            'mobile_number' => '1',
            'payment_status' => 'paid',
        ]));
        $slotTaken = $this->postJson('/api/chatbot/sessions/'.$raceSession.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ]);
        $dump['slot_taken_422'] = ['http' => $slotTaken->getStatusCode(), 'body' => $slotTaken->json()];

        $path = storage_path('framework/disease-chatbot-booking-dump.json');
        file_put_contents($path, json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fwrite(STDOUT, "\n===== DISEASE CHATBOT BOOKING DUMP =====\n");
        fwrite(STDOUT, file_get_contents($path)."\n");
        fwrite(STDOUT, "===== END BOOKING DUMP ({$path}) =====\n");

        $this->assertSame(200, $dump['choose_person']['http']);
        $this->assertSame('CHOOSE_PERSON', $dump['choose_person']['body']['state']);
        $this->assertSame('ASK_COINS', $dump['ask_coins']['body']['state']);
        $this->assertSame('option', $dump['ask_coins']['body']['input']['type']);
        $this->assertSame('option', $dump['ask_coins']['body']['input']['mode']);
        $this->assertStringContainsString('coins = ₹', (string) ($dump['ask_coins']['body']['messages'][0]['text'] ?? ''));
        $this->assertSame('CONFIRM_SUMMARY', $dump['confirm_summary']['body']['state']);
        $this->assertSame('PAYMENT_PENDING', $dump['payment_pending']['body']['state']);
        $this->assertSame(900, (int) ($dump['payment_pending']['body']['messages'][0]['expires_in_seconds'] ?? -1));
        $this->assertStringContainsString("haven't received", (string) ($dump['not_paid']['body']['messages'][0]['text'] ?? ''));
        $this->assertSame('CONFIRMED', $dump['confirmed']['body']['state']);
        $this->assertSame(422, $dump['slot_taken_422']['http']);
        $this->assertNotNull($dependent->id);
    }

    public function test_ask_coins_is_options_only_and_use_fewer_enters_amount_state(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Coins UI', 'HIPCOINUI');
        Coins::query()->create(['person_id' => $person->id, 'coins' => 100]);
        Sanctum::actingAs($user);

        $sessionId = $this->beginChatSession($hospital->id, $person->id);
        $this->advanceToSlotList($sessionId, $doctor->id);
        $ask = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => '09:00 AM'],
        ])->assertOk();
        $this->assertSame('ASK_COINS', $ask->json('state'));
        $this->assertSame('option', $ask->json('input.type'));
        $this->assertSame('option', $ask->json('input.mode'));
        $this->assertStringContainsString('50 coins = ₹500 off', (string) $ask->json('messages.0.text'));

        $amount = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'use_fewer'],
        ])->assertOk();
        $this->assertSame('ASK_COINS_AMOUNT', $amount->json('state'));
        $this->assertSame('number', $amount->json('input.mode'));
        $this->assertSame(1, (int) $amount->json('input.min'));
        $this->assertSame(50, (int) $amount->json('input.max'));
        $this->assertStringContainsString('50 coins = ₹500 off', (string) $amount->json('messages.0.text'));

        $summary = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'number', 'value' => 20],
        ])->assertOk();
        $this->assertSame('CONFIRM_SUMMARY', $summary->json('state'));
        $this->assertSame(20, (int) $summary->json('messages.0.coins_applied'));
    }

    public function test_expires_in_seconds_tracks_fixed_clock_not_constant(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Hold Clock', 'HIPHOLD01');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $pending = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $this->assertSame(900, (int) $pending->json('messages.0.expires_in_seconds'));

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:10:00', 'Asia/Kolkata'));
        $payNow = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'pay_now'],
        ])->assertOk();
        $this->assertSame(300, (int) $payNow->json('messages.0.expires_in_seconds'));
        $this->assertSame(
            (string) $pending->json('messages.0.payment.order_id'),
            (string) $payNow->json('messages.0.payment.order_id')
        );

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:20:00', 'Asia/Kolkata'));
        $late = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'pay_now'],
        ])->assertOk();
        $this->assertSame(0, (int) $late->json('messages.0.expires_in_seconds'));
    }

    public function test_dumps_pay_now_reused_order_json(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Pay Now Dump', 'HIPPAYNOW');
        Sanctum::actingAs($user);

        $sessionId = $this->reachConfirmSummary($hospital->id, $person->id, $doctor->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();

        Carbon::setTestNow(Carbon::parse('2026-10-17 08:05:00', 'Asia/Kolkata'));
        $payNow = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'pay_now'],
        ])->assertOk();

        $dump = ['http' => $payNow->getStatusCode(), 'body' => $payNow->json()];
        $path = storage_path('framework/disease-chatbot-pay-now-dump.json');
        file_put_contents($path, json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fwrite(STDOUT, "\n===== PAY NOW DUMP =====\n");
        fwrite(STDOUT, file_get_contents($path)."\n");
        fwrite(STDOUT, "===== END PAY NOW DUMP ({$path}) =====\n");

        $this->assertSame(200, $dump['http']);
        $this->assertSame('PAYMENT_PENDING', $dump['body']['state']);
        $this->assertSame('payment', $dump['body']['messages'][0]['type']);
        $this->assertSame(600, (int) $dump['body']['messages'][0]['expires_in_seconds']);
    }

    private function getJsonSessionState(string $sessionId): string
    {
        return (string) DiseaseChatbotSession::query()->whereKey($sessionId)->value('state');
    }

    private function beginChatSession(int $hospitalId, ?string $personId): string
    {
        $payload = ['hospital_id' => $hospitalId];
        if ($personId !== null) {
            $payload['person_id'] = $personId;
        }

        return $this->postJson('/api/chatbot/sessions', $payload)
            ->assertOk()
            ->json('session_id');
    }

    private function advanceToSlotList(string $sessionId, string $doctorId): void
    {
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
    }

    private function reachAskCoins(
        int $hospitalId,
        string $personId,
        string $doctorId,
        string $slot = '09:00 AM'
    ): string {
        $sessionId = $this->beginChatSession($hospitalId, $personId);
        $this->advanceToSlotList($sessionId, $doctorId);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => $slot],
        ])->assertOk();

        return $sessionId;
    }

    private function reachConfirmSummary(
        int $hospitalId,
        string $personId,
        string $doctorId,
        string $slot = '09:00 AM'
    ): string {
        $sessionId = $this->beginChatSession($hospitalId, $personId);
        $this->advanceToSlotList($sessionId, $doctorId);
        $afterSlot = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => $slot],
        ])->assertOk();

        if ($afterSlot->json('state') === 'ASK_COINS') {
            $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
                'input' => ['type' => 'option', 'value' => 'use_none'],
            ])->assertOk()->assertJsonPath('state', 'CONFIRM_SUMMARY');
        } else {
            $this->assertSame('CONFIRM_SUMMARY', $afterSlot->json('state'));
        }

        return $sessionId;
    }

    /**
     * @return array{0: HIPUser, 1: Hospital, 2: SpecialitiesMaster, 3: Doctor, 4: Persons}
     */
    private function seedHospitalWithDoctor(string $hospitalName = 'Chat Hospital', string $hipId = 'HIPBOOK01'): array
    {
        $user = $this->member('book@example.com', $hipId);
        $person = $this->person($user, true);
        $hospital = Hospital::withoutEvents(fn () => Hospital::query()->create(['name' => $hospitalName]));
        $master = $this->master('Cardiology', 'CARD'.$hipId, [
            [
                'name' => 'Asthma',
                'symptoms' => ['Wheeze', 'Cough'],
                'about' => 'Airway inflammation',
                'recommended_tests' => ['Spirometry'],
            ],
        ]);
        $this->department($hospital->id, $master->id, 'active');
        $doctor = $this->createDoctor('Dr Available');
        $this->assignDoctor($doctor, $hospital, $master, 'Saturday', '09:00 AM', '12:00 PM');

        return [$user, $hospital, $master, $doctor, $person];
    }

    private function ensureProcedure(Hospital $hospital, SpecialitiesMaster $master): Procedure
    {
        $dept = Speciality::query()
            ->where('hospital_id', $hospital->id)
            ->where('speciality_master_id', $master->id)
            ->firstOrFail();

        $existing = Procedure::query()
            ->where('speciality_id', $dept->id)
            ->where('hospital_id', $hospital->id)
            ->first();
        if ($existing) {
            return $existing;
        }

        return Procedure::query()->create([
            'procedure_name' => 'Consult',
            'speciality_id' => $dept->id,
            'hospital_id' => $hospital->id,
            'status' => 'active',
        ]);
    }

    private function createDoctor(string $name, string $status = 'active', float $fee = 500): Doctor
    {
        return Doctor::withoutEvents(function () use ($name, $status, $fee) {
            $doctor = new Doctor();
            $doctor->forceFill([
                'name' => $name,
                'status' => $status,
                'consultation_fee' => $fee,
                'working_since' => '2018',
                'qualifications' => [],
                'speciality' => [],
                'hospital_ids' => [],
            ]);
            $doctor->id = (string) Str::uuid();
            $doctor->save();

            return $doctor;
        });
    }

    private function assignDoctor(
        Doctor $doctor,
        Hospital $hospital,
        SpecialitiesMaster $master,
        string $day,
        string $start,
        string $end
    ): void {
        $procedure = $this->ensureProcedure($hospital, $master);
        DoctorAssignment::withoutEvents(fn () => DoctorAssignment::query()->create([
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id,
            'status' => 'active',
            'procedure_ids' => [$procedure->id],
            'time_slots' => [
                ['day' => $day, 'start' => $start, 'end' => $end],
            ],
        ]));
        $speciality = array_values(array_unique(array_merge(
            array_map('intval', (array) ($doctor->speciality ?? [])),
            [(int) $master->id]
        )));
        $hospitalIds = array_values(array_unique(array_merge(
            array_map('intval', (array) ($doctor->hospital_ids ?? [])),
            [(int) $hospital->id]
        )));
        Doctor::withoutEvents(fn () => $doctor->forceFill([
            'speciality' => $speciality,
            'hospital_ids' => $hospitalIds,
        ])->save());
        $doctor->refresh();
    }

    private function member(string $email, string $hipId): HIPUser
    {
        return HIPUser::withoutEvents(fn () => HIPUser::query()->create([
            'email' => $email,
            'first_name' => 'Chat',
            'last_name' => 'User',
            'hip_id' => $hipId,
            'is_active' => true,
            'password' => null,
            'mobile_num' => '9800000001',
        ]));
    }

    private function person(HIPUser $user, bool $primary, ?string $parentId = null): Persons
    {
        return Persons::withoutEvents(function () use ($user, $primary, $parentId) {
            $person = Persons::query()->create([
                'first_name' => $primary ? 'Self' : 'Child',
                'last_name' => 'Member',
                'hip_user_id' => $primary ? $user->id : null,
                'parent_id' => $parentId,
                'is_primary' => $primary,
                'is_active' => true,
                'relationship' => $primary ? 'Self' : 'Child',
                'mobile' => '9800000001',
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

    private function bindRazorpayStub(): void
    {
        $GLOBALS['pharmacy_rzp_seq'] = 0;
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
        if (self::$schemaReady && Schema::hasTable('disease_chatbot_bookings') && Schema::hasTable('invoices')) {
            return;
        }

        foreach ([
            'disease_chatbot_bookings', 'disease_chatbot_messages', 'disease_chatbot_sessions',
            'razorpay_payments', 'transactions', 'invoices', 'doctor_bookings', 'doctor_reviews',
            'doctor_assignments', 'procedures', 'doctors', 'specialities', 'specialities_masters',
            'coins', 'persons', 'hospitals', 'healthinpocket_users', 'settings',
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
            $table->timestamps();
            $table->unique('session_id');
            $table->unique('doctor_booking_id');
        });

        self::$schemaReady = true;
    }
}
