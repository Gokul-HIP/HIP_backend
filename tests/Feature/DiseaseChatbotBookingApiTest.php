<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\Coins;
use App\Models\DiseaseChatbotBooking;
use App\Models\DiseaseChatbotSession;
use App\Models\Doctor;
use App\Models\DoctorAssignment;
use App\Models\DiagnosticPackage;
use App\Models\FamilyPackage;
use App\Models\DoctorBooking;
use App\Models\DiagnosticTestBooking;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Procedure;
use App\Models\RewardTier;
use App\Models\RewardTierConfig;
use App\Models\RewardTierDiscountUsage;
use App\Models\RewardTierPackageDiscount;
use App\Models\Setting;
use App\Models\SpecialitiesMaster;
use App\Models\SubscriptionUsageLog;
use App\Models\UserFamilySubscription;
use App\Models\UserRewardProgress;
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
use Illuminate\Http\Request;
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
    private ?RewardTier $previewTier = null;
    private bool $previewMembershipEnabled = false;
    private bool $previewPackageFree = false;

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
            $testCase = $this;
            $mock->shouldReceive('priceDoctorConsultation')->andReturnUsing(
                function (string $hipUserId, float $fee, float $coinsValue = 0.0, ?int $branchId = null, bool $readOnly = false) use ($feeCalc, $testCase) {
                    $fee = round(max(0, $fee), 2);
                    $coinsValue = round(max(0, $coinsValue), 2);
                    $membershipDiscount = $readOnly && $testCase->previewMembershipEnabled ? $fee : 0.0;
                    $rewardPercent = $readOnly && $membershipDiscount <= 0
                        ? (float) ($testCase->previewTier?->config?->total_discount_percentage ?? 0)
                        : 0.0;
                    $rewardDiscount = round(min(max(0, $fee - $membershipDiscount), max(0, ($fee - $membershipDiscount) * $rewardPercent / 100)), 2);
                    $coinsDiscount = round(min($coinsValue, max(0, $fee - $membershipDiscount - $rewardDiscount)), 2);
                    $totalDiscount = round($membershipDiscount + $rewardDiscount + $coinsDiscount, 2);
                    $bd = $feeCalc->bookingBreakdown($fee, $totalDiscount);

                    return [
                        'membership_benefit_applied' => $membershipDiscount > 0,
                        'benefit_type' => $membershipDiscount > 0 ? 'free' : null,
                        'rejection_reason' => $membershipDiscount > 0 ? null : 'no_active_membership',
                        'subscription_id' => null,
                        'original_base_price' => $bd['original_amount'],
                        'membership_discount' => $membershipDiscount,
                        'reward_discount' => $rewardDiscount,
                        'reward_discount_source' => $rewardDiscount > 0 ? 'tier_percentage' : null,
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
            $mock->shouldReceive('previewMembershipState')->andReturnUsing(function (string $hipUserId, string $bookingType = 'doctor') use ($testCase) {
                return [
                    'status' => $testCase->previewMembershipEnabled || $testCase->previewPackageFree,
                    'free_consultations_left' => $testCase->previewMembershipEnabled ? 2 : null,
                    'free_package' => $bookingType === 'package' && $testCase->previewPackageFree,
                ];
            });
            $mock->shouldReceive('priceDiagnosticPackage')->andReturnUsing(function (
                string $hipUserId,
                float $fee,
                int $packageId,
                int $branchId,
                string $packageType = 'diagnostic',
                float $coinsValue = 0.0,
                bool $readOnly = false
            ) use ($feeCalc, $testCase) {
                $membershipDiscount = $readOnly && $testCase->previewPackageFree ? $fee : 0.0;
                $rewardPercent = $readOnly && $membershipDiscount <= 0
                    ? (float) ($testCase->previewTier?->config?->total_discount_percentage ?? 0)
                    : 0.0;
                $rewardDiscount = round(min(max(0, $fee - $membershipDiscount), max(0, ($fee - $membershipDiscount) * $rewardPercent / 100)), 2);
                $coinsDiscount = round(min(max(0, $fee - $membershipDiscount - $rewardDiscount), max(0, $coinsValue)), 2);
                $discount = round($membershipDiscount + $rewardDiscount + $coinsDiscount, 2);
                $breakdown = $feeCalc->bookingBreakdown($fee, $discount);
                return [
                    'membership_benefit_applied' => $membershipDiscount > 0,
                    'membership_discount' => $membershipDiscount,
                    'reward_discount' => $rewardDiscount,
                    'reward_discount_source' => $rewardDiscount > 0 ? 'tier_percentage' : null,
                    'coins_discount' => $coinsDiscount,
                    'discounted_base_price' => $breakdown['discounted_amount'],
                    'service_charges' => $breakdown['service_charges'],
                    'final_payable_amount' => $breakdown['invoice_total'],
                ];
            });
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
            $testCase = $this;
            $mock->shouldReceive('getReadOnlyTierForUser')->andReturnUsing(fn () => $testCase->previewTier);
            $mock->shouldReceive('addEarnedCoins')->andReturnNull();
            $mock->shouldReceive('reserveDiscountUsage')->andReturnNull();
            $mock->shouldReceive('confirmDiscountUsage')->andReturnNull();
            $mock->shouldReceive('releaseDiscountUsage')->andReturnNull();
            $mock->shouldReceive('earnedCoinsPerBookingBonus')->andReturn(0);
            $mock->shouldReceive('calculateBasePriceDiscount')->andReturnUsing(function (string $hipUserId, float $base, ?int $packageId = null, bool $readOnly = false) use ($testCase) {
                $percent = $readOnly ? (float) ($testCase->previewTier?->config?->total_discount_percentage ?? 0) : 0.0;
                $amount = round(min($base, max(0, $base * $percent / 100)), 2);
                return [
                    'amount' => $amount,
                    'source' => $amount > 0 ? 'tier_percentage' : null,
                    'benefit_type' => $amount > 0 ? 'discount' : null,
                    'package_promotion_type' => null,
                    'total_discount_percentage' => $percent ?: null,
                ];
            });
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
            'diagnostic_packages',
            'subscription_usage_logs',
            'user_family_subscriptions',
            'family_packages',
            'user_reward_progress',
            'reward_tier_discount_usages',
            'reward_tier_package_discounts',
            'reward_tier_configs',
            'reward_tiers',
            'second_opinions',
            'diagnostic_test_bookings',
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
        $this->assertSame(50, $summaryMax->json('messages.0.coins_applied'));
        $this->assertSame(500, $summaryMax->json('messages.0.breakdown.consultation_fee'));
        $this->assertSame(500, $summaryMax->json('messages.0.breakdown.coin_discount'));
        $this->assertSame(15, $summaryMax->json('messages.0.breakdown.service_charge'));
        $this->assertSame(0, $summaryMax->json('messages.0.breakdown.taxes'));
        $this->assertSame(15.0, $payableMax);

        $preview = $this->getJson('/api/home/user-coins?consultation_fee=500&coins_used=50')->assertOk();
        $this->assertSame(500, $preview->json('data.coin_discount'));
        $this->assertSame(15, $preview->json('data.service_charge'));
        $this->assertSame(15, $preview->json('data.total_amount'));
        $this->assertSame('doctor', $preview->json('data.pricing.booking_type'));
        $this->assertFalse($preview->json('data.pricing.reward_tier.status'));
        $this->assertSame(500.0, (float) $preview->json('data.pricing.original_fee'));
        $this->assertSame(15.0, (float) $preview->json('data.pricing.total_payable'));

        $secondOpinionPreview = $this->getJson('/api/home/user-coins?consultation_fee=500&coins_used=50&booking_type=second_opinion')
            ->assertOk();
        $this->assertSame('second_opinion', $secondOpinionPreview->json('data.pricing.booking_type'));
        $this->assertFalse($secondOpinionPreview->json('data.pricing.membership.status'));
        $this->assertSame(15.0, (float) $secondOpinionPreview->json('data.total_amount'));

        $this->postJson('/api/chatbot/sessions/'.$sessionMax.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $invoiceMax = Invoice::query()->latest('id')->first();
        $booked = DoctorBooking::query()->first();
        $this->assertNotNull($invoiceMax);
        $this->assertNotNull($booked);
        $this->assertSame($payableMax, (float) $invoiceMax->total_amount);
        $this->assertSame(15.0, (float) $booked->service_charges);
        $this->assertSame(500.0, (float) $booked->total_discount);
        $this->assertSame(0.0, (float) $booked->amount_after_discount);
        $this->assertSame(15.0, (float) $booked->total_amount);

        file_put_contents(storage_path('framework/disease-chatbot-use-max-dump.json'), json_encode([
            'confirm_summary' => $summaryMax->json(),
            'user_coins' => $preview->json(),
            'doctor_booking' => [
                'consultation_fee' => (float) $booked->consultation_fee,
                'coins_used' => (int) $booked->coins_used,
                'service_charges' => (float) $booked->service_charges,
                'total_discount' => (float) $booked->total_discount,
                'amount_after_discount' => (float) $booked->amount_after_discount,
                'total_amount' => (float) $booked->total_amount,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

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

    public function test_user_coins_pricing_preview_supports_silver_and_free_membership_without_writes(): void
    {
        [$user, , , , $person] = $this->seedHospitalWithDoctor('Preview Hospital', 'HIPPREV01');
        Sanctum::actingAs($user);

        $silver = new RewardTier(['name' => 'Silver', 'is_active' => true]);
        $silver->setRelation('config', new RewardTierConfig(['total_discount_percentage' => 5]));
        $this->previewTier = $silver;

        $silverResponse = $this->getJson('/api/home/user-coins?consultation_fee=600');
        $silverResponse->assertOk()
            ->assertJsonPath('data.pricing.booking_type', 'doctor')
            ->assertJsonPath('data.pricing.reward_tier.name', 'Silver')
            ->assertJsonPath('data.pricing.reward_tier.percentage', 5)
            ->assertJsonPath('data.pricing.reward_tier.applied', true)
            ->assertJsonPath('data.pricing.reward_tier.discount_amount', 30)
            ->assertJsonPath('data.pricing.membership.status', false)
            ->assertJsonPath('data.pricing.total_payable', 587.1)
            ->assertJsonPath('data.total_amount', 587.1);

        $this->previewMembershipEnabled = true;
        $membershipResponse = $this->getJson('/api/home/user-coins?consultation_fee=600&booking_type=doctor');
        $membershipResponse->assertOk()
            ->assertJsonPath('data.pricing.membership.status', true)
            ->assertJsonPath('data.pricing.membership.applied', true)
            ->assertJsonPath('data.pricing.membership.discount_amount', 600)
            ->assertJsonPath('data.pricing.membership.free_consultations_left', 2)
            ->assertJsonPath('data.pricing.reward_tier.applied', false)
            ->assertJsonPath('data.pricing.reward_tier.discount_amount', 0)
            ->assertJsonPath('data.pricing.total_payable', 18)
            ->assertJsonPath('data.total_amount', 18);

        if (Schema::hasTable('subscription_usage_logs')) {
            $this->assertSame(0, SubscriptionUsageLog::query()->count());
        }
        if (Schema::hasTable('user_reward_progress')) {
            $this->assertSame(0, DB::table('user_reward_progress')->count());
        }
    }

    public function test_doctor_time_slots_keeps_legacy_fee_key_and_adds_booking_pricing(): void
    {
        [$user, , , $doctor] = $this->seedHospitalWithDoctor('Slots Pricing Hospital', 'HIPSLOTS01');
        Sanctum::actingAs($user);
        $silver = new RewardTier(['name' => 'Silver', 'is_active' => true]);
        $silver->setRelation('config', new RewardTierConfig(['total_discount_percentage' => 5]));
        $this->previewTier = $silver;

        $response = $this->getJson('/api/home/doctor-time-slots?doctor_id='.$doctor->id)->assertOk();

        $this->assertSame(500, (int) $response->json('consultation_fee'));
        $this->assertSame(14.25, (float) $response->json('service_charges'));
        $this->assertSame(489.25, (float) $response->json('total_amount'));
        $this->assertSame('doctor', $response->json('pricing.booking_type'));
        $this->assertSame(500.0, (float) $response->json('pricing.original_fee'));
        $this->assertSame('Silver', $response->json('pricing.reward_tier.name'));
        $this->assertSame(25.0, (float) $response->json('pricing.reward_tier.discount_amount'));
        $this->assertSame(489.25, (float) $response->json('pricing.total_payable'));

        $this->previewMembershipEnabled = true;
        $membershipResponse = $this->getJson('/api/home/doctor-time-slots?doctor_id='.$doctor->id)->assertOk();
        $membershipResponse->assertJsonPath('pricing.membership.status', true)
            ->assertJsonPath('pricing.membership.free_consultations_left', 2)
            ->assertJsonPath('pricing.membership.applied', true)
            ->assertJsonPath('pricing.reward_tier.applied', false)
            ->assertJsonPath('pricing.total_payable', 15);
    }

    public function test_package_coins_keeps_legacy_keys_and_adds_shared_pricing_preview(): void
    {
        [$user, $hospital, , , $person] = $this->seedHospitalWithDoctor('Package Pricing Hospital', 'HIPPKG01');
        DB::table('hospitals')->where('id', $hospital->id)->update(['diagnostic_center_id' => 44]);
        $packageId = DB::table('diagnostic_packages')->insertGetId([
            'name' => 'Basic Package',
            'diagnostic_id' => 44,
            'price' => 1000,
            'discount' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 30]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/home/package-coins?type=diagnostic&package_id='.$packageId.'&coins_used=10')
            ->assertOk();

        $this->assertSame(1000.0, (float) $response->json('data.package_fee'));
        $this->assertSame(100.0, (float) $response->json('data.coin_discount'));
        $this->assertSame(27.0, (float) $response->json('data.service_charge'));
        $this->assertSame(927.0, (float) $response->json('data.total_amount'));
        $this->assertSame('package', $response->json('data.pricing.booking_type'));
        $this->assertSame(1000.0, (float) $response->json('data.pricing.original_fee'));
        $this->assertSame(927.0, (float) $response->json('data.pricing.total_payable'));

        $silver = new RewardTier(['name' => 'Silver', 'is_active' => true]);
        $silver->setRelation('config', new RewardTierConfig(['total_discount_percentage' => 5]));
        $this->previewTier = $silver;
        $silverResponse = $this->getJson('/api/home/package-coins?type=diagnostic&package_id='.$packageId)->assertOk();
        $silverResponse->assertJsonPath('data.pricing.reward_tier.name', 'Silver')
            ->assertJsonPath('data.pricing.reward_tier.discount_amount', 50)
            ->assertJsonPath('data.pricing.total_payable', 978.5);

        $this->previewMembershipEnabled = true;
        $this->previewPackageFree = true;
        $membershipResponse = $this->getJson('/api/home/package-coins?type=diagnostic&package_id='.$packageId)->assertOk();
        $membershipResponse->assertJsonPath('data.pricing.membership.status', true)
            ->assertJsonPath('data.pricing.membership.free_package', true)
            ->assertJsonPath('data.pricing.membership.applied', true)
            ->assertJsonPath('data.pricing.reward_tier.applied', false)
            ->assertJsonPath('data.pricing.total_payable', 30);
    }

    public function test_real_silver_doctor_preview_booking_invoice_and_payment_amounts_match(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Real Pricing Hospital', 'HIPREAL01');
        $this->installRealTierSchema();
        $tier = RewardTier::query()->create([
            'name' => 'Silver', 'min_coins' => 100, 'sort_order' => 2, 'is_active' => true,
        ]);
        RewardTierConfig::query()->create([
            'reward_tier_id' => $tier->id,
            'total_discount_percentage' => 5,
            'free_checkup_count' => 0,
            'earned_coins_per_booking' => 0,
        ]);
        UserRewardProgress::query()->create([
            'hip_user_id' => $user->id,
            'reward_tier_id' => $tier->id,
            'earned_coins_in_tier' => 0,
            'total_lifetime_coins' => 100,
        ]);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 200]);
        Sanctum::actingAs($user);

        $realTiers = new RewardTierService();
        $this->app->instance(RewardTierService::class, $realTiers);
        $this->app->instance(FamilyPackageService::class, new FamilyPackageService());
        $this->app->instance(MembershipEntitlementService::class, new MembershipEntitlementService(
            app(FamilyPackageService::class),
            app(InvoiceFeeCalculator::class),
            $realTiers,
        ));

        $withoutCoinsPreview = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor')
            ->assertOk();
        $silverSlotsPreview = $this->getJson('/api/home/doctor-time-slots?doctor_id='.$doctor->id)->assertOk();
        $withoutCoins = $this->createRealDoctorBooking($user->id, $person->id, $hospital->id, $doctor->id, 0);
        $this->assertSame(489.25, (float) $withoutCoinsPreview->json('data.pricing.total_payable'));
        $this->assertSame(489.25, (float) $withoutCoins['booking']->total_amount);
        $this->assertSame(489.25, (float) $withoutCoins['invoice']->total_amount);
        $this->assertSame(48925, (int) $withoutCoins['payment']['amount']);

        $withCoinsPreview = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor&coins_used=100')
            ->assertOk();
        $withCoins = $this->createRealDoctorBooking($user->id, $person->id, $hospital->id, $doctor->id, 100);
        $this->assertSame(47, (int) $withCoins['booking']->coins_used);
        $this->assertSame(5.15, (float) $withCoinsPreview->json('data.pricing.total_payable'));
        $this->assertSame(5.15, (float) $withCoins['booking']->total_amount);
        $this->assertSame(5.15, (float) $withCoins['invoice']->total_amount);
        $this->assertSame(515, (int) $withCoins['payment']['amount']);
        $this->assertSame(200, (int) Coins::query()->where('person_id', $person->id)->value('coins'));

        app(PaymentApiService::class)->deductDoctorBookingCoins($withCoins['booking']);
        $this->assertSame(153, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
    }

    public function test_reward_tier_free_checkup_limit_caps_paid_booking_discounts_and_cancel_returns_use(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Limited Tier Hospital', 'HIPLIMIT01');
        $this->installRealTierSchema();
        $tier = RewardTier::query()->create(['name' => 'Silver', 'min_coins' => 100, 'sort_order' => 2, 'is_active' => true]);
        RewardTierConfig::query()->create([
            'reward_tier_id' => $tier->id, 'total_discount_percentage' => 5,
            'free_checkup_count' => 1, 'earned_coins_per_booking' => 0,
        ]);
        UserRewardProgress::query()->create([
            'hip_user_id' => $user->id, 'reward_tier_id' => $tier->id,
            'earned_coins_in_tier' => 0, 'total_lifetime_coins' => 100,
        ]);
        $this->bindRealPricingServices();
        Sanctum::actingAs($user);

        $firstPreview = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor')->assertOk();
        $this->assertSame(0, RewardTierDiscountUsage::query()->count());
        $first = $this->createRealDoctorBooking($user->id, $person->id, $hospital->id, $doctor->id, 0, '2026-10-18');
        $this->assertSame(1, $firstPreview->json('data.pricing.reward_tier.limit'));
        $this->assertSame(0, $firstPreview->json('data.pricing.reward_tier.used'));
        $this->assertSame(1, $firstPreview->json('data.pricing.reward_tier.remaining'));
        $this->assertSame(25.0, (float) $first['booking']->total_discount);
        $this->assertDatabaseHas('reward_tier_discount_usages', [
            'booking_type' => 'doctor_booking', 'booking_id' => $first['booking']->id, 'status' => 'reserved',
        ]);
        $this->assertSame(0, $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor')
            ->json('data.pricing.reward_tier.used'));
        app(RewardTierService::class)->confirmDiscountUsage('doctor_booking', (int) $first['booking']->id);

        $secondPreview = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor')->assertOk();
        $this->assertSame(1, RewardTierDiscountUsage::query()->count());
        $second = $this->createRealDoctorBooking($user->id, $person->id, $hospital->id, $doctor->id, 0, '2026-10-19');
        $this->assertSame(1, $secondPreview->json('data.pricing.reward_tier.used'));
        $this->assertSame(0, $secondPreview->json('data.pricing.reward_tier.remaining'));
        $this->assertFalse($secondPreview->json('data.pricing.reward_tier.applied'));
        $this->assertSame(0.0, (float) $second['booking']->total_discount);
        $this->assertSame(515.0, (float) $second['booking']->total_amount);

        $first['booking']->update(['status' => 'cancelled']);
        $this->assertDatabaseHas('reward_tier_discount_usages', [
            'booking_type' => 'doctor_booking', 'booking_id' => $first['booking']->id, 'status' => 'released',
        ]);
        $afterCancellation = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor')->assertOk();
        $third = $this->createRealDoctorBooking($user->id, $person->id, $hospital->id, $doctor->id, 0, '2026-10-20');
        $this->assertSame(1, $afterCancellation->json('data.pricing.reward_tier.remaining'));
        $this->assertSame(25.0, (float) $third['booking']->total_discount);
    }

    public function test_reward_tier_limit_zero_is_unlimited_and_tier_change_starts_a_fresh_allowance(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Tier Generation Hospital', 'HIPLIMIT02');
        $this->installRealTierSchema();
        $silver = RewardTier::query()->create(['name' => 'Silver', 'min_coins' => 100, 'sort_order' => 2, 'is_active' => true]);
        $gold = RewardTier::query()->create(['name' => 'Gold', 'min_coins' => 101, 'sort_order' => 3, 'is_active' => true]);
        RewardTierConfig::query()->create(['reward_tier_id' => $silver->id, 'total_discount_percentage' => 5, 'free_checkup_count' => 0]);
        RewardTierConfig::query()->create(['reward_tier_id' => $gold->id, 'total_discount_percentage' => 10, 'free_checkup_count' => 1]);
        $progress = UserRewardProgress::query()->create([
            'hip_user_id' => $user->id, 'reward_tier_id' => $silver->id,
            'earned_coins_in_tier' => 100, 'total_lifetime_coins' => 100,
        ]);
        $this->bindRealPricingServices();
        Sanctum::actingAs($user);

        $unlimited = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor')->assertOk();
        $this->assertSame(0, $unlimited->json('data.pricing.reward_tier.limit'));
        $this->assertNull($unlimited->json('data.pricing.reward_tier.remaining'));
        $this->createRealDoctorBooking($user->id, $person->id, $hospital->id, $doctor->id, 0, '2026-10-21');
        $this->assertTrue((bool) $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor')
            ->json('data.pricing.reward_tier.applied'));

        RewardTierDiscountUsage::query()->create([
            'hip_user_id' => $user->id, 'reward_tier_id' => $silver->id, 'tier_generation' => 0,
            'booking_type' => 'doctor_booking', 'booking_id' => 999, 'status' => 'consumed',
        ]);
        $progress->update(['reward_tier_id' => $gold->id, 'tier_generation' => 1, 'earned_coins_in_tier' => 0]);
        $goldPreview = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor')->assertOk();
        $this->assertSame('Gold', $goldPreview->json('data.pricing.reward_tier.name'));
        $this->assertSame(0, $goldPreview->json('data.pricing.reward_tier.used'));
        $this->assertSame(1, $goldPreview->json('data.pricing.reward_tier.remaining'));
        $this->assertSame(50.0, (float) $goldPreview->json('data.pricing.reward_tier.discount_amount'));
    }

    public function test_reward_tier_last_use_is_claimed_once_while_serialized_by_progress_lock(): void
    {
        [$user] = $this->seedHospitalWithDoctor('Serialized Tier Hospital', 'HIPLIMIT03');
        $this->installRealTierSchema();
        $tier = RewardTier::query()->create(['name' => 'Silver', 'min_coins' => 100, 'sort_order' => 2, 'is_active' => true]);
        RewardTierConfig::query()->create(['reward_tier_id' => $tier->id, 'total_discount_percentage' => 5, 'free_checkup_count' => 1]);
        UserRewardProgress::query()->create(['hip_user_id' => $user->id, 'reward_tier_id' => $tier->id]);
        $tiers = new RewardTierService();

        DB::transaction(function () use ($tiers, $user, $tier): void {
            $first = $tiers->calculateBasePriceDiscount($user->id, 500);
            $this->assertSame(25.0, $first['amount']);
            $tiers->reserveDiscountUsage($user->id, $first, 'doctor_booking', 901, true);

            // A second transaction waiting for this progress-row lock sees the first claim after commit.
            $second = $tiers->calculateBasePriceDiscount($user->id, 500);
            $this->assertSame(0.0, $second['amount']);
            $this->assertSame(1, $second['used']);
        });
    }

    public function test_reward_tier_allowance_is_shared_across_package_second_opinion_and_doctor_bookings(): void
    {
        [$user] = $this->seedHospitalWithDoctor('Shared Tier Allowance Hospital', 'HIPLIMIT04');
        $this->installRealTierSchema();
        $tier = RewardTier::query()->create(['name' => 'Silver', 'min_coins' => 100, 'sort_order' => 2, 'is_active' => true]);
        RewardTierConfig::query()->create(['reward_tier_id' => $tier->id, 'total_discount_percentage' => 5, 'free_checkup_count' => 1]);
        UserRewardProgress::query()->create(['hip_user_id' => $user->id, 'reward_tier_id' => $tier->id]);
        RewardTierDiscountUsage::query()->create([
            'hip_user_id' => $user->id, 'reward_tier_id' => $tier->id, 'tier_generation' => 0,
            'booking_type' => 'diagnostic_test_booking', 'booking_id' => 991, 'status' => 'consumed',
        ]);
        $tiers = new RewardTierService();

        $secondOpinion = $tiers->calculateBasePriceDiscount($user->id, 500);
        $doctor = $tiers->calculateBasePriceDiscount($user->id, 500);

        $this->assertSame(0.0, $secondOpinion['amount']);
        $this->assertSame(0.0, $doctor['amount']);
        $this->assertSame(1, $doctor['used']);
        $this->assertSame(0, $doctor['remaining']);
    }

    public function test_stale_reward_discount_reservations_expire_for_all_booking_types(): void
    {
        [$user] = $this->seedHospitalWithDoctor('Stale Tier Reservations', 'HIPLIMIT05');
        $this->installRealTierSchema();
        $tier = RewardTier::query()->create(['name' => 'Silver', 'min_coins' => 100, 'sort_order' => 2, 'is_active' => true]);
        RewardTierConfig::query()->create(['reward_tier_id' => $tier->id, 'total_discount_percentage' => 5, 'free_checkup_count' => 3]);
        UserRewardProgress::query()->create(['hip_user_id' => $user->id, 'reward_tier_id' => $tier->id]);
        config(['reward_tiers.discount_reservation_hold_minutes' => 15]);
        Carbon::setTestNow(Carbon::parse('2026-10-27 12:00:00', 'Asia/Kolkata'));

        foreach ([['doctor_booking', 1101], ['second_opinion', 1102], ['diagnostic_test_booking', 1103]] as [$type, $id]) {
            DB::table('reward_tier_discount_usages')->insert([
                'hip_user_id' => $user->id, 'reward_tier_id' => $tier->id, 'tier_generation' => 0,
                'booking_type' => $type, 'booking_id' => $id, 'status' => 'reserved',
                'created_at' => now()->subMinutes(16)->toDateTimeString(),
                'updated_at' => now()->subMinutes(16)->toDateTimeString(),
            ]);
        }
        DB::table('reward_tier_discount_usages')->insert([
            'hip_user_id' => $user->id, 'reward_tier_id' => $tier->id, 'tier_generation' => 0,
            'booking_type' => 'doctor_booking', 'booking_id' => 1104, 'status' => 'reserved',
            'created_at' => now()->subMinutes(5)->toDateTimeString(),
            'updated_at' => now()->subMinutes(5)->toDateTimeString(),
        ]);
        DB::table('reward_tier_discount_usages')->insert([
            'hip_user_id' => $user->id, 'reward_tier_id' => $tier->id, 'tier_generation' => 0,
            'booking_type' => 'doctor_booking', 'booking_id' => 1105, 'status' => 'consumed',
            'created_at' => now()->subMinutes(30)->toDateTimeString(),
            'updated_at' => now()->subMinutes(30)->toDateTimeString(),
        ]);

        $preview = (new RewardTierService())->calculateBasePriceDiscount($user->id, 500, null, true);
        $this->assertSame(1, $preview['used']);
        $this->assertSame(1, $preview['remaining']);
        $this->assertSame(25.0, $preview['amount']);

        $this->app->instance(RewardTierService::class, new RewardTierService());
        $this->artisan('reward-tiers:release-stale-discount-reservations')->assertSuccessful();
        $this->assertSame(3, RewardTierDiscountUsage::query()->where('status', 'released')->count());
        $this->assertSame(1, RewardTierDiscountUsage::query()->where('status', 'reserved')->count());
        $this->assertSame(1, RewardTierDiscountUsage::query()->where('status', 'consumed')->count());
        Carbon::setTestNow();
    }

    public function test_first_booking_creates_only_one_reward_progress_row_for_user_without_progress(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('First Tier Progress', 'HIPLIMIT06');
        $this->installRealTierSchema();
        $this->seedRealSilverTier($user->id);
        UserRewardProgress::query()->where('hip_user_id', $user->id)->delete();
        $this->bindRealPricingServices();

        $booking = $this->createRealDoctorBooking($user->id, $person->id, $hospital->id, $doctor->id, 0);

        $this->assertSame(1, UserRewardProgress::query()->where('hip_user_id', $user->id)->count());
        $this->assertSame(25.0, (float) $booking['booking']->total_discount);
        $this->assertSame(1, RewardTierDiscountUsage::query()->where('booking_type', 'doctor_booking')
            ->where('booking_id', $booking['booking']->id)->count());
    }

    public function test_preview_reports_remaining_uses_exhaustion_and_unlimited_tier(): void
    {
        [$user] = $this->seedHospitalWithDoctor('Tier Preview States', 'HIPLIMIT07');
        $this->installRealTierSchema();
        $tier = RewardTier::query()->create(['name' => 'Silver', 'min_coins' => 100, 'sort_order' => 2, 'is_active' => true]);
        $config = RewardTierConfig::query()->create([
            'reward_tier_id' => $tier->id, 'total_discount_percentage' => 5, 'free_checkup_count' => 5,
        ]);
        UserRewardProgress::query()->create(['hip_user_id' => $user->id, 'reward_tier_id' => $tier->id]);
        $this->bindRealPricingServices();
        Sanctum::actingAs($user);
        foreach ([1201, 1202] as $id) {
            RewardTierDiscountUsage::query()->create([
                'hip_user_id' => $user->id, 'reward_tier_id' => $tier->id, 'tier_generation' => 0,
                'booking_type' => 'doctor_booking', 'booking_id' => $id, 'status' => 'consumed',
            ]);
        }

        $threeLeft = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor')->assertOk();
        $this->assertSame(5, $threeLeft->json('data.pricing.reward_tier.limit'));
        $this->assertSame(2, $threeLeft->json('data.pricing.reward_tier.used'));
        $this->assertSame(3, $threeLeft->json('data.pricing.reward_tier.remaining'));
        $this->assertTrue($threeLeft->json('data.pricing.reward_tier.applied'));
        for ($id = 1203; $id <= 1205; $id++) {
            RewardTierDiscountUsage::query()->create([
                'hip_user_id' => $user->id, 'reward_tier_id' => $tier->id, 'tier_generation' => 0,
                'booking_type' => 'second_opinion', 'booking_id' => $id, 'status' => 'consumed',
            ]);
        }

        $zeroLeft = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor')->assertOk();
        $this->assertSame(5, $zeroLeft->json('data.pricing.reward_tier.limit'));
        $this->assertSame(5, $zeroLeft->json('data.pricing.reward_tier.used'));
        $this->assertSame(0, $zeroLeft->json('data.pricing.reward_tier.remaining'));
        $this->assertFalse($zeroLeft->json('data.pricing.reward_tier.applied'));
        $this->assertSame(0.0, (float) $zeroLeft->json('data.pricing.reward_tier.discount_amount'));
        $this->assertSame(515.0, (float) $zeroLeft->json('data.pricing.total_payable'));

        $config->update(['free_checkup_count' => 0]);
        $unlimited = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor')->assertOk();
        $this->assertSame(0, $unlimited->json('data.pricing.reward_tier.limit'));
        $this->assertSame(0, $unlimited->json('data.pricing.reward_tier.used'));
        $this->assertNull($unlimited->json('data.pricing.reward_tier.remaining'));
        $this->assertTrue($unlimited->json('data.pricing.reward_tier.applied'));
    }

    public function test_real_tier_parity_uses_configured_one_and_three_percent_service_charges(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Tier Fee Rates Hospital', 'HIPREAL07');
        $this->installRealTierSchema();
        $this->seedRealSilverTier($user->id);
        Sanctum::actingAs($user);
        $this->bindRealPricingServices();

        foreach ([1 => ['service' => 4.75, 'total' => 479.75], 3 => ['service' => 14.25, 'total' => 489.25]] as $rate => $expected) {
            config([
                'settings.fees.service_charges' => $rate,
                'services.service_charges_percent' => $rate,
            ]);
            $preview = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor')->assertOk();
            $created = $this->createRealDoctorBooking($user->id, $person->id, $hospital->id, $doctor->id, 0);

            $this->assertSame($expected['service'], (float) $preview->json('data.pricing.service_charge'));
            $this->assertSame($expected['total'], (float) $preview->json('data.pricing.total_payable'));
            $this->assertSame($expected['total'], (float) $created['booking']->total_amount);
            $this->assertSame($expected['total'], (float) $created['invoice']->total_amount);
            $this->assertSame((int) round($expected['total'] * 100), (int) $created['payment']['amount']);
        }
    }

    public function test_real_no_tier_no_membership_api_amounts_match_legacy_values(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Legacy Pricing Hospital', 'HIPREAL08');
        $this->installRealTierSchema();
        $this->installRealMembershipSchema();
        $this->installPackageBookingSchema();
        Coins::query()->create(['person_id' => $person->id, 'coins' => 10]);
        DB::table('hospitals')->where('id', $hospital->id)->update(['diagnostic_center_id' => 44]);
        $package = DiagnosticPackage::query()->create([
            'name' => 'Legacy Package',
            'diagnostic_id' => 44,
            'price' => 1000,
            'lab_tests' => [1],
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);
        $this->bindRealPricingServices();

        $coinsResponse = $this->getJson('/api/home/user-coins?consultation_fee=500&coins_used=10')->assertOk();
        $slotsResponse = $this->getJson('/api/home/doctor-time-slots?doctor_id='.$doctor->id)->assertOk();
        $packageResponse = $this->getJson('/api/home/package-coins?type=diagnostic&package_id='.$package->id.'&coins_used=10')
            ->assertOk();

        $coinsResponse->assertJsonPath('data.coins_used', 10)
            ->assertJsonPath('data.remaining_coins', 0)
            ->assertJsonPath('data.coin_discount', 100)
            ->assertJsonPath('data.service_charge', 12)
            ->assertJsonPath('data.total_amount', 412)
            ->assertJsonPath('data.consultation_fee', 500)
            ->assertJsonPath('data.pricing.reward_tier.status', false)
            ->assertJsonPath('data.pricing.membership.status', false);
        $slotsResponse->assertJsonPath('consultation_fee', 500)
            ->assertJsonPath('service_charges', 15)
            ->assertJsonPath('total_amount', 515)
            ->assertJsonPath('pricing.reward_tier.status', false)
            ->assertJsonPath('pricing.membership.status', false);
        $packageResponse->assertJsonPath('data.coins_used', 10)
            ->assertJsonPath('data.remaining_coins', 0)
            ->assertJsonPath('data.package_fee', 1000)
            ->assertJsonPath('data.coin_discount', 100)
            ->assertJsonPath('data.service_charge', 27)
            ->assertJsonPath('data.total_amount', 927)
            ->assertJsonPath('data.pricing.reward_tier.status', false)
            ->assertJsonPath('data.pricing.membership.status', false);

        $this->assertSame(0, UserRewardProgress::query()->count());
        $this->assertSame(0, UserFamilySubscription::query()->count());
        $this->assertSame(0, SubscriptionUsageLog::query()->count());
        $this->assertSame(10, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
    }

    public function test_real_membership_free_consultation_preview_and_booking_invoice_amounts_match(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Real Membership Hospital', 'HIPREAL02');
        $this->installRealTierSchema();
        $this->installRealMembershipSchema();
        $tier = RewardTier::query()->create([
            'name' => 'Silver', 'min_coins' => 100, 'sort_order' => 2, 'is_active' => true,
        ]);
        RewardTierConfig::query()->create([
            'reward_tier_id' => $tier->id,
            'total_discount_percentage' => 5,
            'free_checkup_count' => 0,
            'earned_coins_per_booking' => 0,
        ]);
        UserRewardProgress::query()->create([
            'hip_user_id' => $user->id,
            'reward_tier_id' => $tier->id,
            'earned_coins_in_tier' => 0,
            'total_lifetime_coins' => 100,
        ]);
        $plan = FamilyPackage::query()->create([
            'name' => 'Two Free Consults',
            'price' => 999,
            'duration_days' => 365,
            'max_members' => 1,
            'max_consultations' => 2,
            'max_lab_tests' => 0,
            'max_hip_coins' => 0,
            'branch_ids' => [$hospital->id],
            'benefits' => [],
            'is_active' => true,
            'sort_order' => 1,
        ]);
        UserFamilySubscription::query()->create([
            'hip_user_id' => $user->id,
            'family_package_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
            'status' => 'active',
            'payment_status' => 'paid',
        ]);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 200]);
        Sanctum::actingAs($user);

        $realTiers = new RewardTierService();
        $realFamilyPackageService = new FamilyPackageService();
        $this->app->instance(RewardTierService::class, $realTiers);
        $this->app->instance(FamilyPackageService::class, $realFamilyPackageService);
        $this->app->instance(MembershipEntitlementService::class, new MembershipEntitlementService(
            $realFamilyPackageService,
            app(InvoiceFeeCalculator::class),
            $realTiers,
        ));

        $usageBefore = SubscriptionUsageLog::query()->count();
        $coinsBefore = (int) Coins::query()->where('person_id', $person->id)->value('coins');
        $preview = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=doctor&coins_used=100')->assertOk();
        $membershipSlotsPreview = $this->getJson('/api/home/doctor-time-slots?doctor_id='.$doctor->id)->assertOk();
        $this->assertSame(15.0, (float) $preview->json('data.pricing.total_payable'));
        $this->assertSame(2, (int) $preview->json('data.pricing.membership.free_consultations_left'));
        $this->assertFalse($preview->json('data.pricing.reward_tier.applied'));
        $this->assertSame($usageBefore, SubscriptionUsageLog::query()->count());
        $this->assertSame($coinsBefore, (int) (Coins::query()->where('person_id', $person->id)->value('coins') ?? 0));

        $created = $this->createRealDoctorBooking($user->id, $person->id, $hospital->id, $doctor->id, 100);
        $this->assertSame(0, (int) $created['booking']->coins_used);
        $this->assertSame(15.0, (float) $created['booking']->total_amount);
        $this->assertSame(15.0, (float) $created['invoice']->total_amount);
        $this->assertSame(1500, (int) $created['payment']['amount']);
        $this->assertSame($usageBefore + 1, SubscriptionUsageLog::query()->count());
        app(PaymentApiService::class)->deductDoctorBookingCoins($created['booking']);
        $this->assertSame($coinsBefore, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
    }

    public function test_second_opinion_ignores_client_fee_and_uses_reward_tier_without_membership(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Second Opinion Pricing Hospital', 'HIPREAL03');
        $this->installRealTierSchema();
        $this->installRealMembershipSchema();
        $this->installSecondOpinionSchema();
        $this->seedRealSilverTier($user->id);
        FamilyPackage::query()->create([
            'name' => 'Membership Must Not Apply',
            'price' => 999,
            'duration_days' => 365,
            'max_members' => 1,
            'max_consultations' => 2,
            'max_lab_tests' => 0,
            'max_hip_coins' => 0,
            'branch_ids' => [$hospital->id],
            'benefits' => [],
            'is_active' => true,
            'sort_order' => 1,
        ])->subscriptions()->create([
            'hip_user_id' => $user->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
            'status' => 'active',
            'payment_status' => 'paid',
        ]);
        Sanctum::actingAs($user);
        $this->bindRealPricingServices();
        Coins::query()->create(['person_id' => $person->id, 'coins' => 200]);

        $preview = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=second_opinion')
            ->assertOk();
        $request = Request::create('/internal/second-opinion', 'POST', [
            'patient_id' => $person->id,
            'doctor_id' => $doctor->id,
            'branch_id' => $hospital->id,
            'hospital_id' => $hospital->id,
            'preferred_date' => '2026-10-18',
            'preferred_time_slots' => ['09:00 AM'],
            'consultation_fee' => 1,
            'is_online_payment' => true,
            'is_coins_applied' => false,
        ]);
        $result = app(\App\Services\Api\BookingApiService::class)->secondOpinion($request, $user->id);
        $booking = $result['booking'];
        $invoice = Invoice::query()->findOrFail($booking->invoice_id);

        $this->assertSame(500.0, (float) $booking->consultation_fee);
        $this->assertSame(25.0, (float) $booking->total_discount);
        $this->assertSame(489.25, (float) $preview->json('data.pricing.total_payable'));
        $this->assertSame(489.25, (float) $booking->total_amount);
        $this->assertSame(489.25, (float) $invoice->total_amount);
        $this->assertSame(48925, (int) $result['payment']['amount']);
        $this->assertFalse($preview->json('data.pricing.membership.status'));

        $this->assertSame(200, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $coinsPreview = $this->getJson('/api/home/user-coins?consultation_fee=500&booking_type=second_opinion&coins_used=100')
            ->assertOk();
        $coinsRequest = Request::create('/internal/second-opinion-with-coins', 'POST', [
            'patient_id' => $person->id,
            'doctor_id' => $doctor->id,
            'branch_id' => $hospital->id,
            'hospital_id' => $hospital->id,
            'preferred_date' => '2026-10-18',
            'preferred_time_slots' => ['09:30 AM'],
            'consultation_fee' => 1,
            'is_online_payment' => true,
            'is_coins_applied' => true,
            'coins_used' => 100,
        ]);
        $coinResult = app(\App\Services\Api\BookingApiService::class)->secondOpinion($coinsRequest, $user->id);
        $coinBooking = $coinResult['booking'];
        $coinInvoice = Invoice::query()->findOrFail($coinBooking->invoice_id);
        $this->assertSame(47, (int) $coinBooking->coins_used);
        $this->assertSame(5.15, (float) $coinsPreview->json('data.pricing.total_payable'));
        $this->assertSame(5.15, (float) $coinBooking->total_amount);
        $this->assertSame(5.15, (float) $coinInvoice->total_amount);
        $this->assertSame(515, (int) $coinResult['payment']['amount']);
        $this->assertSame(200, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        app(PaymentApiService::class)->deductSecondOpinionCoins($coinBooking);
        $this->assertSame(153, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
    }

    public function test_real_package_tier_and_coin_preview_booking_invoice_and_payment_match(): void
    {
        [$user, $hospital, , , $person] = $this->seedHospitalWithDoctor('Real Package Hospital', 'HIPREAL04');
        $this->installRealTierSchema();
        $this->installRealMembershipSchema();
        $this->installPackageBookingSchema();
        $this->seedRealSilverTier($user->id);
        DB::table('hospitals')->where('id', $hospital->id)->update(['diagnostic_center_id' => 44]);
        $package = DiagnosticPackage::query()->create([
            'name' => 'Real Package',
            'diagnostic_id' => 44,
            'price' => 1000,
            'discount' => null,
            'lab_tests' => [1],
            'status' => 'active',
        ]);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 200]);
        Sanctum::actingAs($user);
        $this->bindRealPricingServices();

        $tierOnlyPreview = $this->getJson('/api/home/package-coins?type=diagnostic&package_id='.$package->id)
            ->assertOk();
        $tierOnly = $this->createRealPackageBooking($user->id, $person->id, $hospital->id, (int) $package->id, 0);
        $this->assertSame(978.5, (float) $tierOnlyPreview->json('data.pricing.total_payable'));
        $this->assertSame(978.5, (float) $tierOnly['booking']->total_amount);
        $this->assertSame(978.5, (float) $tierOnly['invoice']->total_amount);
        $this->assertSame(97850, (int) $tierOnly['payment']['amount']);

        $preview = $this->getJson('/api/home/package-coins?type=diagnostic&package_id='.$package->id.'&coins_used=100')
            ->assertOk();
        $result = $this->createRealPackageBooking($user->id, $person->id, $hospital->id, (int) $package->id, 100);
        $booking = $result['booking'];
        $invoice = $result['invoice'];

        $this->assertSame(95, (int) $booking->coins_used);
        $this->assertSame(30.0, (float) $preview->json('data.pricing.total_payable'));
        $this->assertSame(30.0, (float) $booking->total_amount);
        $this->assertSame(30.0, (float) $invoice->total_amount);
        $this->assertSame(3000, (int) $result['payment']['amount']);
        $this->assertSame(200, (int) Coins::query()->where('person_id', $person->id)->value('coins'));

        app(PaymentApiService::class)->deductDiagnosticTestBookingCoins($booking);
        $this->assertSame(105, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
    }

    public function test_real_membership_selected_free_package_matches_booking_invoice_and_payment(): void
    {
        [$user, $hospital, , , $person] = $this->seedHospitalWithDoctor('Real Free Package Hospital', 'HIPREAL05');
        $this->installRealTierSchema();
        $this->installRealMembershipSchema();
        $this->installPackageBookingSchema();
        $tier = $this->seedRealSilverTier($user->id);
        RewardTierConfig::query()->where('reward_tier_id', $tier->id)->update(['free_checkup_count' => 1]);
        DB::table('hospitals')->where('id', $hospital->id)->update(['diagnostic_center_id' => 44]);
        $package = DiagnosticPackage::query()->create([
            'name' => 'Included Real Package',
            'diagnostic_id' => 44,
            'price' => 1000,
            'lab_tests' => [1],
            'status' => 'active',
        ]);
        Coins::query()->create(['person_id' => $person->id, 'coins' => 200]);
        $plan = FamilyPackage::query()->create([
            'name' => 'Free Package Membership',
            'price' => 999,
            'duration_days' => 365,
            'max_members' => 1,
            'max_consultations' => 2,
            'max_lab_tests' => 0,
            'max_hip_coins' => 0,
            'branch_ids' => [$hospital->id],
            'benefits' => [[
                'diagnostic_package_id' => $package->id,
                'benefit_type' => 'free',
                'max_lab_tests' => 1,
            ]],
            'is_active' => true,
            'sort_order' => 1,
        ]);
        UserFamilySubscription::query()->create([
            'hip_user_id' => $user->id,
            'family_package_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
            'status' => 'active',
            'payment_status' => 'paid',
        ]);
        Sanctum::actingAs($user);
        $this->bindRealPricingServices();

        $usageBefore = SubscriptionUsageLog::query()->count();
        $preview = $this->getJson('/api/home/package-coins?type=diagnostic&package_id='.$package->id.'&coins_used=100')->assertOk();
        $this->assertSame(30.0, (float) $preview->json('data.pricing.total_payable'));
        $this->assertTrue($preview->json('data.pricing.membership.applied'));
        $this->assertTrue($preview->json('data.pricing.membership.free_package'));
        $this->assertNull($preview->json('data.pricing.membership.free_consultations_left'));
        $this->assertSame(1, $preview->json('data.pricing.membership.remaining_lab_tests'));
        $this->assertFalse($preview->json('data.pricing.reward_tier.applied'));
        $this->assertSame(1, $preview->json('data.pricing.reward_tier.remaining'));
        $this->assertSame($usageBefore, SubscriptionUsageLog::query()->count());
        $this->assertSame(0, (int) $preview->json('data.coins_used'));
        $this->assertSame(0, RewardTierDiscountUsage::query()->count());

        $request = Request::create('/internal/real-free-package-booking', 'POST', [
            'patient_id' => $person->id,
            'branch_id' => $hospital->id,
            'hospital_id' => $hospital->id,
            'package_type' => 'diagnostic',
            'package_id' => $package->id,
            'sample_collection' => 'hospital',
            'booking_date' => '2026-10-18',
            'required_time_slots' => ['09:00 AM'],
            'is_online_payment' => true,
            'is_coins_applied' => true,
            'coins_used' => 100,
        ]);
        $result = app(\App\Services\Api\BookingApiService::class)->diagnosticTestBooking($request, $user->id);
        $booking = $result['booking'];
        $invoice = Invoice::query()->findOrFail($booking->invoice_id);

        $this->assertSame(30.0, (float) $booking->total_amount);
        $this->assertSame(30.0, (float) $invoice->total_amount);
        $this->assertSame(3000, (int) $result['payment']['amount']);
        $this->assertSame($usageBefore + 1, SubscriptionUsageLog::query()->count());
        $this->assertSame(0, RewardTierDiscountUsage::query()->count());
        app(PaymentApiService::class)->deductDiagnosticTestBookingCoins($booking);
        $this->assertSame(200, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
    }

    public function test_real_free_and_percentage_tier_package_promotions_match_booking_invoice_and_payment(): void
    {
        [$user, $hospital, , , $person] = $this->seedHospitalWithDoctor('Real Promotion Hospital', 'HIPREAL06');
        $this->installRealTierSchema();
        $this->installRealMembershipSchema();
        $this->installPackageBookingSchema();
        $tier = $this->seedRealSilverTier($user->id);
        DB::table('hospitals')->where('id', $hospital->id)->update(['diagnostic_center_id' => 44]);
        $package = DiagnosticPackage::query()->create([
            'name' => 'Promoted Package',
            'diagnostic_id' => 44,
            'price' => 1000,
            'lab_tests' => [1],
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);
        $this->bindRealPricingServices();

        foreach ([
            'free' => ['discount_type' => null, 'discount_value' => null, 'total' => 30.0, 'discount' => 1000.0],
            'discount' => ['discount_type' => 'percentage', 'discount_value' => 20, 'total' => 824.0, 'discount' => 200.0],
        ] as $promotionType => $expected) {
            RewardTierPackageDiscount::query()->updateOrCreate(
                ['reward_tier_id' => $tier->id, 'diagnostic_package_id' => $package->id],
                [
                    'promotion_type' => $promotionType,
                    'discount_type' => $expected['discount_type'],
                    'discount_value' => $expected['discount_value'],
                ]
            );

            $preview = $this->getJson('/api/home/package-coins?type=diagnostic&package_id='.$package->id)
                ->assertOk();
            $request = Request::create('/internal/real-promoted-package-booking', 'POST', [
                'patient_id' => $person->id,
                'branch_id' => $hospital->id,
                'hospital_id' => $hospital->id,
                'package_type' => 'diagnostic',
                'package_id' => $package->id,
                'sample_collection' => 'hospital',
                'booking_date' => '2026-10-18',
                'required_time_slots' => ['09:00 AM'],
                'is_online_payment' => true,
            ]);
            $result = app(\App\Services\Api\BookingApiService::class)->diagnosticTestBooking($request, $user->id);
            $booking = $result['booking'];
            $invoice = Invoice::query()->findOrFail($booking->invoice_id);

            $this->assertSame($expected['discount'], (float) $booking->total_discount);
            $this->assertSame($expected['total'], (float) $preview->json('data.pricing.total_payable'));
            $this->assertSame($expected['total'], (float) $booking->total_amount);
            $this->assertSame($expected['total'], (float) $invoice->total_amount);
            $this->assertSame((int) round($expected['total'] * 100), (int) $result['payment']['amount']);
            $this->assertSame($promotionType, $preview->json('data.pricing.reward_tier.package_promotion.type'));
            $this->assertTrue($preview->json('data.pricing.reward_tier.package_promotion.applied'));
        }
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
        $this->assertSame('value', $dump['choose_person']['body']['options'][0]['kind']);
        $this->assertSame('value', collect($dump['confirm_summary']['body']['options'])->firstWhere('id', 'confirm_booking')['kind']);
        $this->assertSame('action', collect($dump['confirm_summary']['body']['options'])->firstWhere('id', 'change_time')['kind']);
        $this->assertSame('value', collect($dump['payment_pending']['body']['options'])->firstWhere('id', 'pay_now')['kind']);
        $this->assertSame('action', collect($dump['payment_pending']['body']['options'])->firstWhere('id', 'restart')['kind']);
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
        $this->assertSame(100, $ask->json('messages.0.coins.wallet_balance'));
        $this->assertSame(50, $ask->json('messages.0.coins.max_usable'));
        $this->assertSame(10, $ask->json('messages.0.coins.rate'));
        $this->assertSame(500, $ask->json('messages.0.coins.consultation_fee'));
        $this->assertStringContainsString('50 coins = ₹500 off', (string) $ask->json('messages.0.text'));

        $amount = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'use_fewer'],
        ])->assertOk();
        $this->assertSame('action', collect($ask->json('options'))->firstWhere('id', 'use_fewer')['kind']);
        $this->assertSame('value', collect($ask->json('options'))->firstWhere('id', 'use_max')['kind']);
        $this->assertSame('value', collect($ask->json('options'))->firstWhere('id', 'use_none')['kind']);
        $this->assertSame('ASK_COINS_AMOUNT', $amount->json('state'));
        $this->assertSame('number', $amount->json('input.type'));
        $this->assertSame('number', $amount->json('input.mode'));
        $this->assertSame(1, $amount->json('input.min'));
        $this->assertSame(50, $amount->json('input.max'));
        $this->assertSame(100, $amount->json('messages.0.coins.wallet_balance'));
        $this->assertSame(50, $amount->json('messages.0.coins.max_usable'));
        $this->assertSame(10, $amount->json('messages.0.coins.rate'));
        $this->assertSame(500, $amount->json('messages.0.coins.consultation_fee'));
        $this->assertStringContainsString('50 coins = ₹500 off', (string) $amount->json('messages.0.text'));

        $summary = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'number', 'value' => 20],
        ])->assertOk();
        $this->assertSame('CONFIRM_SUMMARY', $summary->json('state'));
        $this->assertSame(20, $summary->json('messages.0.coins_applied'));
        $this->assertSame(500, $summary->json('messages.0.breakdown.consultation_fee'));
        $this->assertSame(200, $summary->json('messages.0.breakdown.coin_discount'));
        $this->assertSame(9, $summary->json('messages.0.breakdown.service_charge'));
        $this->assertSame(0, $summary->json('messages.0.breakdown.taxes'));
        $this->assertSame(309, $summary->json('messages.0.breakdown.total_payable'));

        $path = storage_path('framework/disease-chatbot-coins-dump.json');
        file_put_contents($path, json_encode([
            'ask_coins' => ['http' => $ask->getStatusCode(), 'body' => $ask->json()],
            'ask_coins_amount' => ['http' => $amount->getStatusCode(), 'body' => $amount->json()],
            'confirm_summary_20' => ['http' => $summary->getStatusCode(), 'body' => $summary->json()],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function test_typed_coin_amount_rejects_non_integers_and_amounts_above_the_cap(): void
    {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor('Coins Cap', 'HIPCOINCAP');
        Coins::query()->create(['person_id' => $person->id, 'coins' => 100]);
        Sanctum::actingAs($user);

        $sessionId = $this->beginChatSession($hospital->id, $person->id);
        $this->advanceToSlotList($sessionId, $doctor->id);
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => '09:00 AM'],
        ])->assertOk()->assertJsonPath('state', 'ASK_COINS');
        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'use_fewer'],
        ])->assertOk()->assertJsonPath('state', 'ASK_COINS_AMOUNT');

        foreach ([
            [20.5, 'Enter a whole number of coins.'],
            ['20.5', 'Enter a whole number of coins.'],
            ['abc', 'Enter a whole number of coins.'],
            [0, 'Enter at least 1 coin.'],
            [-3, 'Enter at least 1 coin.'],
            [51, 'You can use up to 50 coins.'],
        ] as [$value, $message]) {
            $rejected = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
                'input' => ['type' => 'number', 'value' => $value],
            ])->assertStatus(422);
            $this->assertSame(422, $rejected->json('status'));
            $this->assertSame('ASK_COINS_AMOUNT', $rejected->json('state'));
            $this->assertSame($message, $rejected->json('messages.0.text'));
            $this->assertSame(1, $rejected->json('input.min'));
            $this->assertSame(50, $rejected->json('input.max'));
            $this->assertSame(50, $rejected->json('input.coins.max_usable'));
        }

        $this->assertNull(data_get(
            DiseaseChatbotSession::query()->findOrFail($sessionId)->context,
            'coins_used'
        ));
    }

    public function test_summary_total_matches_payment_amount_for_membership_and_coins(): void
    {
        $this->bootMembershipTables();
        $this->useRealConsultationPricing();

        $noDiscount = $this->summaryThenPayment('Plain Hospital', 'HIPPLAIN1', 'plain@example.com', 0, false);
        $this->assertSame(0, $noDiscount['summary']->json('messages.0.breakdown.coin_discount'));
        $this->assertSame(0, $noDiscount['summary']->json('messages.0.breakdown.membership_discount'));
        $this->assertSame(0, $noDiscount['summary']->json('messages.0.breakdown.reward_discount'));
        $this->assertSame(15, $noDiscount['summary']->json('messages.0.breakdown.service_charge'));
        $this->assertSame(0, $noDiscount['summary']->json('messages.0.breakdown.taxes'));
        $this->assertSame(515, $noDiscount['summary']->json('messages.0.breakdown.total_payable'));
        $this->assertPayableMatchesPayment($noDiscount['summary'], $noDiscount['pending']);

        $coinsOnly = $this->summaryThenPayment('Coin Only Hospital', 'HIPCONLY1', 'coinonly@example.com', 100, false, 20);
        $this->assertSame(200, $coinsOnly['summary']->json('messages.0.breakdown.coin_discount'));
        $this->assertSame(0, $coinsOnly['summary']->json('messages.0.breakdown.membership_discount'));
        $this->assertSame(0, $coinsOnly['summary']->json('messages.0.breakdown.reward_discount'));
        $this->assertSame(9, $coinsOnly['summary']->json('messages.0.breakdown.service_charge'));
        $this->assertSame(309, $coinsOnly['summary']->json('messages.0.breakdown.total_payable'));
        $this->assertPayableMatchesPayment($coinsOnly['summary'], $coinsOnly['pending']);

        $membership = $this->summaryThenPayment('Member Hospital', 'HIPMEMB01', 'member@example.com', 0, true);
        $this->assertSame('CONFIRM_SUMMARY', $membership['after_slot']->json('state'));
        $this->assertSame(500, $membership['summary']->json('messages.0.breakdown.membership_discount'));
        $this->assertSame(0, $membership['summary']->json('messages.0.breakdown.coin_discount'));
        $this->assertSame(0, $membership['summary']->json('messages.0.breakdown.reward_discount'));
        $this->assertSame(15, $membership['summary']->json('messages.0.breakdown.service_charge'));
        $this->assertSame(15, $membership['summary']->json('messages.0.breakdown.total_payable'));
        $this->assertPayableMatchesPayment($membership['summary'], $membership['pending']);

        $both = $this->summaryThenPayment('Member Coin Hospital', 'HIPMEMCO1', 'membercoin@example.com', 100, true);
        $this->assertSame('CONFIRM_SUMMARY', $both['after_slot']->json('state'));
        $this->assertSame(500, $both['summary']->json('messages.0.breakdown.membership_discount'));
        $this->assertSame(0, $both['summary']->json('messages.0.breakdown.coin_discount'));
        $this->assertSame(0, $both['summary']->json('messages.0.breakdown.reward_discount'));
        $this->assertSame(15, $both['summary']->json('messages.0.breakdown.total_payable'));
        $this->assertPayableMatchesPayment($both['summary'], $both['pending']);

        file_put_contents(storage_path('framework/disease-chatbot-membership-dump.json'), json_encode([
            'confirm_summary' => $both['summary']->json(),
            'payment_pending' => $both['pending']->json(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function test_reward_tier_percent_and_coins_match_the_booking_charge(): void
    {
        config([
            'settings.fees.service_charges' => 1,
            'services.service_charges_percent' => 1,
        ]);
        Setting::set('service_charges', 1, 'float', 'fees');

        $this->mock(RewardTierService::class, function ($mock) {
            $tier = new RewardTier();
            $tier->setRelation('config', new RewardTierConfig([
                'earned_coins_per_booking' => 0,
            ]));
            $mock->shouldReceive('getTierForUser')->andReturn($tier);
            $mock->shouldReceive('addEarnedCoins')->andReturnNull();
            $mock->shouldReceive('reserveDiscountUsage')->andReturnNull();
            $mock->shouldReceive('confirmDiscountUsage')->andReturnNull();
            $mock->shouldReceive('releaseDiscountUsage')->andReturnNull();
            $mock->shouldReceive('earnedCoinsPerBookingBonus')->andReturn(0);
            $mock->shouldReceive('calculateBasePriceDiscount')->andReturnUsing(
                function (string $hipUserId, float $remainingBasePrice, ?int $diagnosticPackageId = null) {
                    $remaining = round(max(0, $remainingBasePrice), 2);
                    $amount = round(min($remaining, $remaining * 0.10), 2);

                    return [
                        'amount' => $amount,
                        'source' => 'tier_percentage',
                        'benefit_type' => 'discount',
                        'package_promotion_type' => null,
                        'total_discount_percentage' => 10.0,
                    ];
                }
            );
        });
        $this->bootMembershipTables();
        $this->useRealConsultationPricing();

        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor(
            'Reward Hospital',
            'HIPREW01',
            'reward@example.com'
        );
        Doctor::withoutEvents(fn () => $doctor->forceFill(['consultation_fee' => 600])->save());
        Coins::query()->create(['person_id' => $person->id, 'coins' => 100]);
        Sanctum::actingAs($user);

        $sessionId = $this->beginChatSession($hospital->id, $person->id);
        $this->advanceToSlotList($sessionId, $doctor->id);
        $ask = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => '09:00 AM'],
        ])->assertOk();

        $this->assertSame('ASK_COINS', $ask->json('state'));
        $this->assertSame(54, $ask->json('messages.0.coins.max_usable'));
        $this->assertSame(54, $ask->json('input.coins.max_usable'));
        $this->assertNotSame(60, $ask->json('messages.0.coins.max_usable'));

        $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'use_fewer'],
        ])->assertOk()->assertJsonPath('state', 'ASK_COINS_AMOUNT');
        $summary = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'number', 'value' => 10],
        ])->assertOk();

        $this->assertSame('CONFIRM_SUMMARY', $summary->json('state'));
        $this->assertSame(600, $summary->json('messages.0.breakdown.consultation_fee'));
        $this->assertSame(60, $summary->json('messages.0.breakdown.reward_discount'));
        $this->assertSame(100, $summary->json('messages.0.breakdown.coin_discount'));
        $this->assertEquals(4.40, (float) $summary->json('messages.0.breakdown.service_charge'));
        $this->assertEquals(444.40, (float) $summary->json('messages.0.breakdown.total_payable'));

        $pending = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $this->assertSame('PAYMENT_PENDING', $pending->json('state'));
        $this->assertSame(44440, (int) $pending->json('messages.0.payment.amount'));
        $this->assertPayableMatchesPayment($summary, $pending);
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

    /**
     * @param  \Illuminate\Testing\TestResponse  $summary
     * @param  \Illuminate\Testing\TestResponse  $pending
     */
    private function assertPayableMatchesPayment($summary, $pending): void
    {
        $payable = (float) $summary->json('messages.0.breakdown.total_payable');
        $this->assertSame((int) round($payable * 100), (int) $pending->json('messages.0.payment.amount'));
        $this->assertSame($payable, (float) $pending->json('messages.0.summary.breakdown.total_payable'));
    }

    /**
     * @return array{after_slot: \Illuminate\Testing\TestResponse, summary: \Illuminate\Testing\TestResponse, pending: \Illuminate\Testing\TestResponse}
     */
    private function summaryThenPayment(
        string $hospitalName,
        string $hipId,
        string $email,
        int $coins,
        bool $membership,
        ?int $typedCoins = null
    ): array {
        [$user, $hospital, , $doctor, $person] = $this->seedHospitalWithDoctor($hospitalName, $hipId, $email);
        if ($coins > 0) {
            Coins::query()->create(['person_id' => $person->id, 'coins' => $coins]);
        }
        if ($membership) {
            $this->giveActiveMembership((string) $user->id);
        }
        Sanctum::actingAs($user);

        $sessionId = $this->beginChatSession($hospital->id, $person->id);
        $this->advanceToSlotList($sessionId, $doctor->id);
        $afterSlot = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'slot', 'value' => '09:00 AM'],
        ])->assertOk();

        $summary = $afterSlot;
        if ($afterSlot->json('state') === 'ASK_COINS') {
            $choice = $typedCoins === null ? 'use_none' : 'use_fewer';
            $summary = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
                'input' => ['type' => 'option', 'value' => $choice],
            ])->assertOk();
            if ($typedCoins !== null) {
                $this->assertSame('ASK_COINS_AMOUNT', $summary->json('state'));
                $summary = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
                    'input' => ['type' => 'number', 'value' => $typedCoins],
                ])->assertOk();
            }
        }

        $this->assertSame('CONFIRM_SUMMARY', $summary->json('state'));
        $pending = $this->postJson('/api/chatbot/sessions/'.$sessionId.'/reply', [
            'input' => ['type' => 'option', 'value' => 'confirm_booking'],
        ])->assertOk();
        $this->assertSame('PAYMENT_PENDING', $pending->json('state'));

        return [
            'after_slot' => $afterSlot,
            'summary' => $summary,
            'pending' => $pending,
        ];
    }

    private function giveActiveMembership(string $hipUserId): void
    {
        $packageId = DB::table('family_packages')->insertGetId([
            'name' => 'Consult Plan '.$hipUserId,
            'price' => 100,
            'duration_days' => 30,
            'max_members' => 4,
            'max_consultations' => 0,
            'max_lab_tests' => 0,
            'max_hip_coins' => 0,
            'branch_ids' => json_encode([]),
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_family_subscriptions')->insert([
            'hip_user_id' => $hipUserId,
            'family_package_id' => $packageId,
            'start_date' => '2026-10-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'payment_status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function useRealConsultationPricing(): void
    {
        $pricing = new class(app(FamilyPackageService::class), app(InvoiceFeeCalculator::class), app(RewardTierService::class)) extends MembershipEntitlementService
        {
            public function reserveUsage(
                int $subscriptionId,
                string $usageType,
                int $bookingId,
                string $bookingType,
                ?int $diagnosticPackageId = null
            ): SubscriptionUsageLog {
                return new SubscriptionUsageLog();
            }
        };

        $this->app->instance(MembershipEntitlementService::class, $pricing);
    }

    private function bootMembershipTables(): void
    {
        if (! Schema::hasTable('family_packages')) {
            Schema::create('family_packages', function ($table) {
                $table->id();
                $table->string('name');
                $table->decimal('price', 10, 2)->default(0);
                $table->unsignedInteger('duration_days')->default(30);
                $table->unsignedInteger('max_members')->default(1);
                $table->unsignedInteger('max_consultations')->default(0);
                $table->unsignedInteger('max_lab_tests')->default(0);
                $table->unsignedInteger('max_hip_coins')->default(0);
                $table->json('branch_ids')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('user_family_subscriptions')) {
            Schema::create('user_family_subscriptions', function ($table) {
                $table->id();
                $table->string('hip_user_id');
                $table->unsignedBigInteger('family_package_id');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('status')->default('active');
                $table->string('payment_status')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('subscription_usage_logs')) {
            Schema::create('subscription_usage_logs', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_family_subscription_id');
                $table->string('hip_user_id')->nullable();
                $table->string('usage_type');
                $table->unsignedBigInteger('booking_id')->nullable();
                $table->string('booking_type')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
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
    private function seedHospitalWithDoctor(string $hospitalName = 'Chat Hospital', string $hipId = 'HIPBOOK01', string $email = 'book@example.com'): array
    {
        $user = $this->member($email, $hipId);
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

    private function createRealDoctorBooking(string $userId, string $personId, int $hospitalId, string $doctorId, int $coins, string $date = '2026-10-18'): array
    {
        $request = Request::create('/internal/real-doctor-booking', 'POST', [
            'patient_id' => $personId,
            'doctor_id' => $doctorId,
            'branch_id' => $hospitalId,
            'hospital_id' => $hospitalId,
            'department_id' => 1,
            'appointment_type' => 'In-Clinic',
            'booking_date' => $date,
            'required_time_slots' => ['09:00 AM'],
            'reason_of_visit' => 'Parity test',
            'message' => 'Pricing parity',
            'is_online_payment' => true,
            'is_coins_applied' => $coins > 0,
            'coins_used' => $coins,
        ]);

        $result = app(\App\Services\Api\BookingApiService::class)->doctorBooking($request, $userId);
        $booking = $result['booking'];

        return [
            'booking' => $booking,
            'invoice' => Invoice::query()->findOrFail($booking->invoice_id),
            'payment' => $result['payment'],
        ];
    }

    private function createRealPackageBooking(string $userId, string $personId, int $hospitalId, int $packageId, int $coins): array
    {
        $request = Request::create('/internal/real-package-booking', 'POST', [
            'patient_id' => $personId,
            'branch_id' => $hospitalId,
            'hospital_id' => $hospitalId,
            'package_type' => 'diagnostic',
            'package_id' => $packageId,
            'sample_collection' => 'hospital',
            'booking_date' => '2026-10-18',
            'required_time_slots' => ['09:00 AM'],
            'is_online_payment' => true,
            'is_coins_applied' => $coins > 0,
            'coins_used' => $coins,
        ]);
        $result = app(\App\Services\Api\BookingApiService::class)
            ->diagnosticTestBooking($request, $userId);
        $booking = $result['booking'];

        return [
            'booking' => $booking,
            'invoice' => Invoice::query()->findOrFail($booking->invoice_id),
            'payment' => $result['payment'],
        ];
    }

    private function seedRealSilverTier(string $userId): RewardTier
    {
        $tier = RewardTier::query()->create([
            'name' => 'Silver', 'min_coins' => 100, 'sort_order' => 2, 'is_active' => true,
        ]);
        RewardTierConfig::query()->create([
            'reward_tier_id' => $tier->id,
            'total_discount_percentage' => 5,
            'free_checkup_count' => 0,
            'earned_coins_per_booking' => 0,
        ]);
        UserRewardProgress::query()->create([
            'hip_user_id' => $userId,
            'reward_tier_id' => $tier->id,
            'earned_coins_in_tier' => 0,
            'total_lifetime_coins' => 100,
        ]);

        return $tier;
    }

    private function bindRealPricingServices(): void
    {
        $realTiers = new RewardTierService();
        $familyPackages = new FamilyPackageService();
        $this->app->instance(RewardTierService::class, $realTiers);
        $this->app->instance(FamilyPackageService::class, $familyPackages);
        $this->app->instance(MembershipEntitlementService::class, new MembershipEntitlementService(
            $familyPackages,
            app(InvoiceFeeCalculator::class),
            $realTiers,
        ));
    }

    private function installPackageBookingSchema(): void
    {
        if (! Schema::hasColumn('hospitals', 'diagnostic_center_id')) {
            Schema::table('hospitals', function ($table) {
                $table->unsignedBigInteger('diagnostic_center_id')->nullable();
            });
        }
        if (! Schema::hasColumn('diagnostic_packages', 'lab_tests')) {
            Schema::table('diagnostic_packages', function ($table) {
                $table->json('lab_tests')->nullable();
            });
        }
        if (! Schema::hasTable('diagnostic_test_bookings')) {
            Schema::create('diagnostic_test_bookings', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('mobile_number')->nullable();
                $table->uuid('member_id')->nullable();
                $table->uuid('patient_id')->nullable();
                $table->string('relationship')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('diagnostic_center_id')->nullable();
                $table->unsignedBigInteger('package_id')->nullable();
                $table->string('package_type')->nullable();
                $table->string('test_type')->nullable();
                $table->json('test_items')->nullable();
                $table->string('sample_collection')->nullable();
                $table->date('booking_date')->nullable();
                $table->json('required_time_slots')->nullable();
                $table->string('status')->nullable();
                $table->text('purpose')->nullable();
                $table->text('clinical_notes')->nullable();
                $table->boolean('is_coins_applied')->default(false);
                $table->unsignedInteger('coins_used')->default(0);
                $table->decimal('package_fee', 12, 2)->nullable();
                $table->decimal('service_charges', 12, 2)->nullable();
                $table->decimal('total_discount', 12, 2)->nullable();
                $table->decimal('amount_after_discount', 12, 2)->nullable();
                $table->decimal('total_amount', 12, 2)->nullable();
                $table->boolean('is_online_payment')->default(false);
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->string('payment_status')->nullable();
                $table->uuid('created_by')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    private function installSecondOpinionSchema(): void
    {
        if (Schema::hasTable('second_opinions')) {
            return;
        }

        Schema::create('second_opinions', function ($table) {
            $table->id();
            $table->uuid('member_id')->nullable();
            $table->uuid('patient_id')->nullable();
            $table->string('patient_name')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment')->nullable();
            $table->text('question_for_doctor')->nullable();
            $table->json('document_ids')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('speciality_id')->nullable();
            $table->uuid('doctor_id')->nullable();
            $table->string('mode_of_consultation')->nullable();
            $table->date('preferred_date')->nullable();
            $table->json('preferred_time_slots')->nullable();
            $table->string('relationship')->nullable();
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
            $table->string('status')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function installRealTierSchema(): void
    {
        if (! Schema::hasTable('reward_tiers')) {
            Schema::create('reward_tiers', function ($table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('min_coins')->default(0);
                $table->unsignedInteger('sort_order')->default(1);
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
            Schema::create('reward_tier_configs', function ($table) {
                $table->id();
                $table->unsignedBigInteger('reward_tier_id');
                $table->decimal('total_discount_percentage', 5, 2)->nullable();
                $table->unsignedInteger('free_checkup_count')->default(0);
                $table->unsignedInteger('earned_coins_per_booking')->default(0);
                $table->timestamps();
            });
            Schema::create('reward_tier_package_discounts', function ($table) {
                $table->id();
                $table->unsignedBigInteger('reward_tier_id');
                $table->unsignedBigInteger('diagnostic_package_id');
                $table->string('promotion_type');
                $table->string('discount_type')->nullable();
                $table->decimal('discount_value', 10, 2)->nullable();
                $table->timestamps();
            });
            Schema::create('user_reward_progress', function ($table) {
                $table->id();
                $table->uuid('hip_user_id')->unique();
                $table->unsignedBigInteger('reward_tier_id');
                $table->unsignedInteger('tier_generation')->default(0);
                $table->unsignedInteger('earned_coins_in_tier')->default(0);
                $table->unsignedInteger('total_lifetime_coins')->default(0);
                $table->timestamps();
            });
            Schema::create('reward_tier_discount_usages', function ($table) {
                $table->id();
                $table->uuid('hip_user_id');
                $table->unsignedBigInteger('reward_tier_id');
                $table->unsignedInteger('tier_generation')->default(0);
                $table->string('booking_type', 32);
                $table->unsignedBigInteger('booking_id');
                $table->string('status')->default('reserved');
                $table->timestamps();
                $table->unique(['booking_type', 'booking_id']);
            });
        }
    }

    private function installRealMembershipSchema(): void
    {
        if (! Schema::hasTable('family_packages')) {
            Schema::create('family_packages', function ($table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->unsignedInteger('duration_days');
                $table->unsignedInteger('max_members')->default(1);
                $table->unsignedInteger('max_consultations')->default(0);
                $table->unsignedInteger('max_lab_tests')->default(0);
                $table->unsignedInteger('max_hip_coins')->default(0);
                $table->json('branch_ids')->nullable();
                $table->json('benefits')->nullable();
                $table->json('terms_conditions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->softDeletes();
                $table->timestamps();
            });
            Schema::create('user_family_subscriptions', function ($table) {
                $table->id();
                $table->uuid('hip_user_id');
                $table->unsignedBigInteger('family_package_id');
                $table->json('covered_member_ids')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status')->default('active');
                $table->string('payment_status')->nullable();
                $table->string('payment_mode')->nullable();
                $table->dateTime('activated_at')->nullable();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->decimal('amount_paid', 10, 2)->nullable();
                $table->boolean('auto_renew')->default(false);
                $table->dateTime('cancelled_at')->nullable();
                $table->timestamps();
            });
            Schema::create('subscription_usage_logs', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_family_subscription_id');
                $table->uuid('hip_user_id');
                $table->string('usage_type');
                $table->unsignedBigInteger('booking_id')->nullable();
                $table->string('booking_type')->nullable();
                $table->dateTime('used_at')->nullable();
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
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
            $table->unsignedBigInteger('diagnostic_center_id')->nullable();
            $table->timestamps();
        });
        Schema::create('diagnostic_packages', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('diagnostic_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->json('lab_tests')->nullable();
            $table->string('status')->default('active');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
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
