<?php

namespace Tests\Feature;

use App\Models\DiagnosticPackage;
use App\Models\HmsIntegration;
use App\Models\HmsWebhookInbox;
use App\Models\UserFamilySubscription;
use App\Services\BookingPricingPreviewService;
use App\Services\Hms\HmsPriceQuoteService;
use App\Services\Hms\HmsSignature;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class HmsClientApiTest extends TestCase
{
    private const API_KEY = 'hms-test-key';

    private const SECRET = 'hms-test-secret';

    private HmsIntegration $integration;

    private string $userId;

    private string $primaryPersonId;

    private string $dependentPersonId;

    private string $doctorId;

    private int $clockStep = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();

        $this->userId = (string) Str::uuid();
        $this->primaryPersonId = (string) Str::uuid();
        $this->dependentPersonId = (string) Str::uuid();
        $this->doctorId = (string) Str::uuid();

        DB::table('healthinpocket_users')->insert([
            'id' => $this->userId,
            'first_name' => 'Asha',
            'last_name' => 'Rao',
            'mobile_num' => '9990001111',
            'password' => 'secret',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('persons')->insert([
            [
                'id' => $this->primaryPersonId,
                'hip_user_id' => $this->userId,
                'parent_id' => null,
                'first_name' => 'Asha',
                'last_name' => 'Rao',
                'mobile' => '9990001111',
                'is_primary' => true,
                'relationship' => 'Self',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $this->dependentPersonId,
                'hip_user_id' => null,
                'parent_id' => $this->primaryPersonId,
                'first_name' => 'Ravi',
                'last_name' => 'Rao',
                'mobile' => '9990002222',
                'is_primary' => false,
                'relationship' => 'spouse',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('hospitals')->insert([
            ['id' => 1, 'name' => 'Allowed Hospital', 'diagnostic_center_id' => 10, 'status' => 'active'],
            ['id' => 2, 'name' => 'Other Hospital', 'diagnostic_center_id' => 99, 'status' => 'active'],
        ]);
        DB::table('doctors')->insert([
            'id' => $this->doctorId,
            'name' => 'Dr. Mehta',
            'consultation_fee' => '800',
            'hospital_ids' => json_encode([1]),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('diagnostic_packages')->insert([
            'id' => 5,
            'name' => 'CBC',
            'code' => 'LAB-1',
            'price' => 1000,
            'discount' => null,
            'diagnostic_id' => 10,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->integration = HmsIntegration::query()->create([
            'name' => 'Test HMS',
            'hospital_ids' => [1],
            'organization_id' => 1,
            'api_key_hash' => HmsIntegration::hashKey(self::API_KEY),
            'signing_secret' => self::SECRET,
            'allowed_ips' => null,
            'is_enabled' => true,
            'rate_limit_per_minute' => 120,
        ]);
        DB::table('hms_external_id_maps')->insert([
            [
                'integration_id' => $this->integration->id,
                'entity_type' => 'hospital',
                'external_id' => 'H1',
                'local_id' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'integration_id' => $this->integration->id,
                'entity_type' => 'patient',
                'external_id' => 'DEP',
                'local_id' => $this->dependentPersonId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'integration_id' => $this->integration->id,
                'entity_type' => 'doctor',
                'external_id' => 'DOC-1',
                'local_id' => $this->doctorId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'integration_id' => $this->integration->id,
                'entity_type' => 'package',
                'external_id' => 'EXT-PKG',
                'local_id' => '5',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_valid_signature_returns_membership_and_request_id(): void
    {
        $this->createPlan(['code' => 'FAM-GOLD']);
        $this->createSubscription();

        $response = $this->signed('GET', $this->membershipUri());

        $response->assertOk();
        $response->assertHeader('X-Request-Id');
        $this->assertNotSame('', (string) $response->headers->get('X-Request-Id'));
        $response->assertJsonPath('has_membership', true);
        $response->assertJsonPath('plan.code', 'FAM-GOLD');

        $log = HmsWebhookInbox::query()->first();
        $this->assertNotNull($log);
        $this->assertSame('GET', $log->method);
        $this->assertSame('processed', $log->state);
        $this->assertSame('patient.membership.read', $log->event_type);
        $this->assertSame(['[redacted]'], $log->headers['x-hms-key']);
        $this->assertSame(['[redacted]'], $log->headers['x-hms-signature']);
        $this->assertStringNotContainsString(self::API_KEY, json_encode($log->headers));
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $response = $this->signed('GET', $this->membershipUri(), null, [
            'signature' => str_repeat('ab', 32),
        ]);

        $this->assertHmsError($response, 401, 'invalid_signature');
    }

    public function test_hashed_api_key_is_rejected(): void
    {
        $response = $this->signed('GET', $this->membershipUri(), null, [
            'key' => HmsIntegration::hashKey(self::API_KEY),
        ]);

        $this->assertHmsError($response, 401, 'invalid_signature');
    }

    public function test_stale_timestamp_is_rejected(): void
    {
        $response = $this->signed('GET', $this->membershipUri(), null, [
            'timestamp' => (string) (now()->getTimestamp() - 301),
        ]);

        $this->assertHmsError($response, 401, 'stale_timestamp');
    }

    public function test_replayed_signature_is_rejected(): void
    {
        $this->createPlan();
        $this->createSubscription();
        $uri = $this->membershipUri();
        $timestamp = (string) now()->getTimestamp();

        $this->signed('GET', $uri, null, ['timestamp' => $timestamp])->assertOk();
        $replay = $this->signed('GET', $uri, null, ['timestamp' => $timestamp]);

        $this->assertHmsError($replay, 401, 'invalid_signature');
        $replay->assertJsonPath('message', 'Signature has already been used.');
    }

    public function test_disabled_integration_is_rejected(): void
    {
        DB::table('hms_integrations')->where('id', $this->integration->id)->update(['is_enabled' => false]);

        $response = $this->signed('GET', $this->membershipUri());

        $this->assertHmsError($response, 403, 'invalid_signature');
        $response->assertJsonPath('message', 'Integration is disabled.');
    }

    public function test_hospital_outside_the_integration_is_forbidden(): void
    {
        $response = $this->signed('GET', $this->membershipUri('9990001111', '2'));

        $this->assertHmsError($response, 403, 'forbidden_hospital');

        $missing = $this->signed('GET', $this->membershipUri('9990001111', 'NO-SUCH'));
        $this->assertHmsError($missing, 404, 'not_found');
    }

    public function test_rate_limit_is_enforced(): void
    {
        DB::table('hms_integrations')->where('id', $this->integration->id)->update([
            'rate_limit_per_minute' => 2,
        ]);
        $this->createPlan();
        $this->createSubscription();
        $uri = $this->membershipUri();

        $this->signed('GET', $uri)->assertOk();
        $this->signed('GET', $uri)->assertOk();
        $limited = $this->signed('GET', $uri);

        $this->assertHmsError($limited, 429, 'rate_limited');
    }

    public function test_ip_allow_list_rejects_other_addresses(): void
    {
        DB::table('hms_integrations')->where('id', $this->integration->id)->update([
            'allowed_ips' => json_encode(['203.0.113.9']),
        ]);

        $response = $this->signed('GET', $this->membershipUri());

        $this->assertHmsError($response, 403, 'invalid_signature');
        $response->assertJsonPath('message', 'Request IP is not allowed.');
    }

    public function test_unknown_patient_is_not_found(): void
    {
        $membership = $this->signed('GET', $this->membershipUri('7000000099'));
        $reward = $this->signed('GET', $this->rewardUri('7000000099'));

        $this->assertHmsError($membership, 404, 'patient_not_found');
        $membership->assertJsonPath('message', 'Patient was not found.');
        $this->assertHmsError($reward, 404, 'patient_not_found');

        $knownMobileWrongRef = $this->signedJson('/apiv1/client/price/quote', $this->quoteBody('doctor', [
            'doctor_id' => $this->doctorId,
            'patient_ref' => 'MISSING-PERSON',
        ]));
        $this->assertHmsError($knownMobileWrongRef, 404, 'patient_not_found');
    }

    /**
     * @dataProvider acceptedMobileFormats
     */
    public function test_membership_matches_stored_mobile_after_normalization(string $mobile): void
    {
        $this->signed('GET', $this->membershipUri($mobile))->assertOk()->assertJsonPath('has_membership', false);
    }

    public static function acceptedMobileFormats(): array
    {
        return [
            'plain' => ['9990001111'],
            'spaces' => ['99900 01111'],
            'dashes' => ['99900-01111'],
            'leading zero' => ['09990001111'],
            'country code' => ['919990001111'],
            'plus country and spaces' => ['+91 99900 01111'],
            'trunk zero then country code' => ['0919990001111'],
        ];
    }

    public function test_blank_mobile_is_a_validation_error(): void
    {
        $this->assertHmsError(
            $this->signed('GET', $this->membershipUri('000')),
            422,
            'validation_error'
        );
    }

    public function test_unknown_mobile_quote_uses_base_tier_without_creating_rows(): void
    {
        $this->seedPricingTier();
        $before = $this->businessCounts();
        $expected = $this->preview()->doctor(
            HmsPriceQuoteService::ANONYMOUS_HIP_USER_ID,
            800,
            0,
            0,
            1,
            [],
            true
        );

        $response = $this->signedJson('/apiv1/client/price/quote', $this->quoteBody('doctor', [
            'mobile' => '+91 70000 00001',
            'doctor_id' => $this->doctorId,
            'original_fee' => 800,
            'coins_used' => 50,
            'patient_ref' => 'DOES-NOT-EXIST',
        ]));

        $response->assertOk();
        $response->assertJsonPath('patient_exists', false);
        $response->assertJsonPath('pricing.membership.applied', false);
        $response->assertJsonPath('pricing.coin_discount', 0);
        $this->assertEquals($expected['pricing'], $response->json('pricing'));
        $this->assertSame($before, $this->businessCounts());
        $this->assertSame(0, DB::table('healthinpocket_users')->where('mobile_num', '7000000001')->count());
        $this->assertSame(1, DB::table('user_reward_progress')->count());
    }

    public function test_missing_params_are_a_validation_error(): void
    {
        $response = $this->signed('GET', '/apiv1/client/get/patient/membership');

        $this->assertHmsError($response, 422, 'validation_error');
    }

    public function test_membership_when_none_is_active(): void
    {
        $response = $this->signed('GET', $this->membershipUri());

        $response->assertOk();
        $response->assertExactJson([
            'has_membership' => false,
            'valid_at_hospital' => false,
            'patient_covered' => false,
            'plan' => null,
            'free_consultations' => null,
            'free_packages' => [],
            'covered_members' => [],
        ]);
    }

    public function test_membership_with_consultations_and_package_allowance_left(): void
    {
        $this->createPlan([
            'code' => 'FAM-GOLD',
            'max_consultations' => 4,
            'branch_ids' => json_encode([1]),
            'benefits' => json_encode([[
                'diagnostic_package_id' => 5,
                'benefit_type' => 'free',
                'max_lab_tests' => 2,
            ]]),
        ]);
        $subscriptionId = $this->createSubscription();
        $this->logConsultation($subscriptionId);

        $response = $this->signed('GET', $this->membershipUri());

        $response->assertOk();
        $response->assertJsonPath('has_membership', true);
        $response->assertJsonPath('valid_at_hospital', true);
        $response->assertJsonPath('patient_covered', true);
        $response->assertJsonPath('plan.id', 1);
        $response->assertJsonPath('plan.code', 'FAM-GOLD');
        $response->assertJsonPath('plan.name', 'Family Gold');
        $response->assertJsonPath('plan.payment_status', 'paid');
        $response->assertJsonPath('plan.branches', [1]);
        $response->assertJsonPath('plan.days_left', 10);
        $response->assertJsonPath('free_consultations.unlimited', false);
        $response->assertJsonPath('free_consultations.total', 4);
        $response->assertJsonPath('free_consultations.used', 1);
        $response->assertJsonPath('free_consultations.remaining', 3);
        $response->assertJsonPath('free_packages.0.package_id', 5);
        $response->assertJsonPath('free_packages.0.package_code', 'LAB-1');
        $response->assertJsonPath('free_packages.0.name', 'CBC');
        $response->assertJsonPath('free_packages.0.total', 2);
        $response->assertJsonPath('free_packages.0.used', 0);
        $response->assertJsonPath('free_packages.0.remaining', 2);
        $response->assertJsonPath('covered_members.0.person_id', $this->primaryPersonId);
        $response->assertJsonPath('covered_members.1.person_id', $this->dependentPersonId);

        $dependent = $this->signed('GET', $this->membershipUri('9990001111', 'H1', 'DEP'));
        $dependent->assertOk();
        $dependent->assertJsonPath('patient_covered', true);
        $dependent->assertJsonPath('has_membership', true);
    }

    public function test_membership_when_consultations_are_exhausted(): void
    {
        $this->createPlan(['max_consultations' => 1, 'branch_ids' => json_encode([1])]);
        $subscriptionId = $this->createSubscription();
        $this->logConsultation($subscriptionId);

        $response = $this->signed('GET', $this->membershipUri());

        $response->assertOk();
        $response->assertJsonPath('has_membership', true);
        $response->assertJsonPath('valid_at_hospital', true);
        $response->assertJsonPath('free_consultations.used', 1);
        $response->assertJsonPath('free_consultations.remaining', 0);
    }

    public function test_membership_is_not_valid_at_another_hospital(): void
    {
        $this->createPlan(['branch_ids' => json_encode([2])]);
        $this->createSubscription();

        $response = $this->signed('GET', $this->membershipUri());

        $response->assertOk();
        $response->assertJsonPath('has_membership', true);
        $response->assertJsonPath('valid_at_hospital', false);
        $response->assertJsonPath('plan.branches', [2]);
    }

    public function test_dependent_outside_covered_members_is_not_covered(): void
    {
        $this->createPlan(['branch_ids' => json_encode([1])]);
        $this->createSubscription([
            'covered_member_ids' => json_encode([$this->primaryPersonId]),
        ]);

        $response = $this->signed('GET', $this->membershipUri('9990001111', 'H1', 'DEP'));

        $response->assertOk();
        $response->assertJsonPath('has_membership', true);
        $response->assertJsonPath('patient_covered', false);
        $response->assertJsonPath('covered_members.0.person_id', $this->primaryPersonId);
        $response->assertJsonPath('covered_members.0.name', 'Asha Rao');
        $response->assertJsonPath('covered_members.0.relationship', 'Self');
        $this->assertCount(1, $response->json('covered_members'));
    }

    public function test_reward_plan_reports_a_used_up_discount_limit(): void
    {
        $this->seedRewardTiers();
        DB::table('user_reward_progress')->insert([
            'hip_user_id' => $this->userId,
            'reward_tier_id' => 2,
            'tier_generation' => 0,
            'earned_coins_in_tier' => 40,
            'total_lifetime_coins' => 80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ([1, 2] as $bookingId) {
            DB::table('reward_tier_discount_usages')->insert([
                'hip_user_id' => $this->userId,
                'reward_tier_id' => 2,
                'tier_generation' => 0,
                'booking_type' => 'doctor',
                'booking_id' => $bookingId,
                'status' => 'consumed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('reward_tier_package_discounts')->insert([
            'reward_tier_id' => 2,
            'diagnostic_package_id' => 5,
            'promotion_type' => 'discount',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('coins')->insert([
            'person_id' => $this->primaryPersonId,
            'coins' => 25,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = DB::table('user_reward_progress')->count();
        $response = $this->signed('GET', $this->rewardUri());

        $response->assertOk();
        $response->assertJsonPath('tier_name', 'Silver');
        $response->assertJsonPath('percentage', 15);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('discounted_bookings.limit', 2);
        $response->assertJsonPath('discounted_bookings.used', 2);
        $response->assertJsonPath('discounted_bookings.remaining', 0);
        $response->assertJsonPath('package_promotions.0.package_id', 5);
        $response->assertJsonPath('package_promotions.0.package_code', 'LAB-1');
        $response->assertJsonPath('package_promotions.0.type', 'percentage');
        $response->assertJsonPath('package_promotions.0.value', 10);
        $response->assertJsonPath('progress.next_tier_name', 'Gold');
        $response->assertJsonPath('progress.earned_coins_in_tier', 40);
        $response->assertJsonPath('progress.coins_to_next_tier', 60);
        $response->assertJsonPath('progress.progress_percentage', 40);
        $response->assertJsonPath('lifetime_coins', 80);
        $response->assertJsonPath('wallet_coin_balance', 25);
        $this->assertSame($before, DB::table('user_reward_progress')->count());
    }

    public function test_reward_plan_does_not_create_progress_rows(): void
    {
        $this->seedRewardTiers();
        $bareUserId = (string) Str::uuid();
        DB::table('healthinpocket_users')->insert([
            'id' => $bareUserId,
            'first_name' => 'Bare',
            'last_name' => 'User',
            'mobile_num' => '8880001111',
            'password' => 'secret',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('persons')->insert([
            'id' => (string) Str::uuid(),
            'hip_user_id' => $bareUserId,
            'first_name' => 'Bare',
            'last_name' => 'User',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = DB::table('user_reward_progress')->count();
        $response = $this->signed('GET', $this->rewardUri('8880001111'));

        $response->assertOk();
        $response->assertJsonPath('tier_name', 'Bronze');
        $response->assertJsonPath('discounted_bookings.limit', null);
        $response->assertJsonPath('lifetime_coins', 0);
        $this->assertSame($before, DB::table('user_reward_progress')->count());
        $this->assertDatabaseMissing('user_reward_progress', ['hip_user_id' => $bareUserId]);
    }

    public function test_quote_matches_preview_for_tier_only_doctor_second_opinion_and_package(): void
    {
        $this->seedPricingTier();
        $before = $this->businessCounts();

        $this->assertQuoteMatches('doctor', [
            'doctor_id' => $this->doctorId,
            'original_fee' => 640,
            'coins_used' => 50,
        ], fn () => $this->preview()->doctor($this->userId, 640, 0, 0, 1, [], true));

        $this->assertQuoteMatches('doctor', [
            'doctor_id' => 'DOC-1',
        ], fn () => $this->preview()->doctor($this->userId, 800, 0, 0, 1, [], true));

        $this->assertQuoteMatches('second_opinion', [
            'doctor_id' => $this->doctorId,
            'coins_used' => 20,
        ], fn () => $this->preview()->secondOpinion($this->userId, 800, 0, 0));

        $this->assertQuoteMatches('package', [
            'package_code' => 'LAB-1',
            'coins_used' => 15,
        ], fn () => $this->preview()->package($this->userId, DiagnosticPackage::query()->findOrFail(5), 'diagnostic', 0, 0));

        $package = DiagnosticPackage::query()->findOrFail(5);
        $shadow = clone $package;
        $shadow->setAttribute('price', 640);
        $shadow->setAttribute('discount', null);
        $this->assertQuoteMatches('package', [
            'package_code' => 'EXT-PKG',
            'original_fee' => 640,
        ], fn () => $this->preview()->package($this->userId, $shadow, 'diagnostic', 0, 0));

        $this->assertSame('800', (string) DB::table('doctors')->where('id', $this->doctorId)->value('consultation_fee'));
        $this->assertEquals(1000, (float) DB::table('diagnostic_packages')->where('id', 5)->value('price'));
        $this->assertSame($before, $this->businessCounts());
        $this->assertSame(5, HmsWebhookInbox::query()->count());
        $this->assertSame(0, UserFamilySubscription::query()->count());
    }

    public function test_quote_matches_preview_when_membership_is_active(): void
    {
        $this->seedPricingTier();
        $this->createPlan([
            'max_consultations' => 3,
            'branch_ids' => json_encode([1]),
            'benefits' => json_encode([[
                'diagnostic_package_id' => 5,
                'benefit_type' => 'free',
                'max_lab_tests' => 1,
            ]]),
        ]);
        $this->createSubscription();
        $before = $this->businessCounts();

        $this->assertQuoteMatches('doctor', [
            'doctor_id' => $this->doctorId,
        ], fn () => $this->preview()->doctor($this->userId, 800, 0, 0, 1, [], true));

        $this->assertQuoteMatches('second_opinion', [
            'doctor_id' => $this->doctorId,
        ], fn () => $this->preview()->secondOpinion($this->userId, 800, 0, 0));

        $this->assertQuoteMatches('package', [
            'package_code' => 'LAB-1',
        ], fn () => $this->preview()->package($this->userId, DiagnosticPackage::query()->findOrFail(5), 'diagnostic', 0, 0));

        $doctorQuote = $this->quotePayload('doctor', ['doctor_id' => $this->doctorId]);
        $this->assertTrue($doctorQuote['pricing']['membership']['applied']);
        $secondOpinion = $this->quotePayload('second_opinion', ['doctor_id' => $this->doctorId]);
        $this->assertFalse($secondOpinion['pricing']['membership']['applied']);

        $this->assertSame($before, $this->businessCounts());
    }

    public function test_reads_do_not_write_rows_outside_the_inbox(): void
    {
        $this->seedPricingTier();
        $this->createPlan(['max_consultations' => 2, 'branch_ids' => json_encode([1])]);
        $this->createSubscription();
        $before = $this->businessCounts();
        $inboxBefore = HmsWebhookInbox::query()->count();

        $this->signed('GET', $this->membershipUri())->assertOk();
        $this->signed('GET', $this->rewardUri())->assertOk();
        $this->signedJson('/apiv1/client/price/quote', $this->quoteBody('doctor', [
            'doctor_id' => $this->doctorId,
        ]))->assertOk();

        $this->assertSame($before, $this->businessCounts());
        $this->assertSame($inboxBefore + 3, HmsWebhookInbox::query()->count());
        $this->assertSame(1, DB::table('user_reward_progress')->count());
        $this->assertSame(1, DB::table('user_family_subscriptions')->count());
        $this->assertSame(0, DB::table('subscription_usage_logs')->count());
        $this->assertSame(0, DB::table('reward_tier_discount_usages')->count());
    }

    private function assertQuoteMatches(string $bookingType, array $overrides, callable $expected): void
    {
        $response = $this->signedJson('/apiv1/client/price/quote', $this->quoteBody($bookingType, $overrides));
        $response->assertOk();
        $preview = $expected();
        $response->assertJsonPath('currency', 'INR');
        $response->assertJsonPath('patient_exists', true);
        $this->assertNotEmpty($response->json('quote_id'));
        $this->assertEquals($preview['pricing']['total_payable'], $response->json('payable_amount'));
        $this->assertEquals($preview['pricing'], $response->json('pricing'));
        $this->assertTrue(now()->addMinutes(14)->lt(\Carbon\Carbon::parse($response->json('expires_at'))));
        $this->assertTrue(now()->addMinutes(16)->gt(\Carbon\Carbon::parse($response->json('expires_at'))));
        $this->assertEquals(0, $response->json('pricing.coin_discount'));
    }

    private function quotePayload(string $bookingType, array $overrides): array
    {
        return $this->signedJson('/apiv1/client/price/quote', $this->quoteBody($bookingType, $overrides))
            ->assertOk()
            ->json();
    }

    private function quoteBody(string $bookingType, array $overrides): array
    {
        return array_merge([
            'booking_type' => $bookingType,
            'mobile' => '9990001111',
            'hospital_code' => 'H1',
        ], $overrides);
    }

    private function preview(): BookingPricingPreviewService
    {
        return app(BookingPricingPreviewService::class);
    }

    /**
     * @return array<string, int>
     */
    private function businessCounts(): array
    {
        $tables = [
            'healthinpocket_users',
            'persons',
            'family_packages',
            'user_family_subscriptions',
            'subscription_usage_logs',
            'user_reward_progress',
            'reward_tier_discount_usages',
            'coins',
            'diagnostic_packages',
            'doctors',
            'hms_integrations',
            'hms_external_id_maps',
        ];
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }

    private function membershipUri(string $mobile = '9990001111', string $hospitalCode = 'H1', ?string $patientRef = null): string
    {
        return '/apiv1/client/get/patient/membership?'.$this->query($mobile, $hospitalCode, $patientRef);
    }

    private function rewardUri(string $mobile = '9990001111', string $hospitalCode = 'H1'): string
    {
        return '/apiv1/client/get/patient/reward/plan?'.$this->query($mobile, $hospitalCode);
    }

    private function query(string $mobile, string $hospitalCode, ?string $patientRef = null): string
    {
        $params = ['mobile' => $mobile, 'hospital_code' => $hospitalCode];
        if ($patientRef !== null) {
            $params['patient_ref'] = $patientRef;
        }

        return http_build_query($params);
    }

    private function signed(string $method, string $uri, ?string $body = null, array $overrides = []): TestResponse
    {
        $timestamp = (string) ($overrides['timestamp'] ?? (now()->getTimestamp() + $this->clockStep++));
        $raw = $body ?? '';
        $signature = $overrides['signature'] ?? HmsSignature::sign(
            $overrides['secret'] ?? self::SECRET,
            $timestamp,
            $method,
            $uri,
            $raw
        );

        return $this->call($method, $uri, [], [], [], [
            'HTTP_X_HMS_KEY' => $overrides['key'] ?? self::API_KEY,
            'HTTP_X_HMS_TIMESTAMP' => $timestamp,
            'HTTP_X_HMS_SIGNATURE' => $signature,
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], $raw);
    }

    private function signedJson(string $uri, array $payload, array $overrides = []): TestResponse
    {
        $raw = json_encode($payload, JSON_UNESCAPED_SLASHES);

        return $this->signed('POST', $uri, $raw === false ? '' : $raw, $overrides);
    }

    private function assertHmsError(TestResponse $response, int $status, string $code): void
    {
        $response->assertStatus($status);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('code', $code);
        $response->assertHeader('X-Request-Id');
        $this->assertNotSame('', (string) $response->json('request_id'));
        $this->assertSame($response->headers->get('X-Request-Id'), $response->json('request_id'));
    }

    private function createPlan(array $overrides = []): void
    {
        DB::table('family_packages')->insert(array_merge([
            'id' => 1,
            'name' => 'Family Gold',
            'code' => 'FAM-GOLD',
            'price' => 1000,
            'duration_days' => 30,
            'max_members' => 4,
            'max_consultations' => 4,
            'max_lab_tests' => 0,
            'max_hip_coins' => 0,
            'branch_ids' => json_encode([1]),
            'benefits' => json_encode([]),
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function createSubscription(array $overrides = []): int
    {
        return (int) DB::table('user_family_subscriptions')->insertGetId(array_merge([
            'hip_user_id' => $this->userId,
            'family_package_id' => 1,
            'covered_member_ids' => null,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
            'payment_status' => 'paid',
            'amount_paid' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function logConsultation(int $subscriptionId): void
    {
        DB::table('subscription_usage_logs')->insert([
            'user_family_subscription_id' => $subscriptionId,
            'hip_user_id' => $this->userId,
            'usage_type' => 'consultation',
            'used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPricingTier(): void
    {
        DB::table('reward_tiers')->insert([
            'id' => 1,
            'name' => 'Gold',
            'min_coins' => 0,
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('reward_tier_configs')->insert([
            'reward_tier_id' => 1,
            'total_discount_percentage' => 10,
            'free_checkup_count' => 0,
            'earned_coins_per_booking' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_reward_progress')->insert([
            'hip_user_id' => $this->userId,
            'reward_tier_id' => 1,
            'tier_generation' => 0,
            'earned_coins_in_tier' => 0,
            'total_lifetime_coins' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedRewardTiers(): void
    {
        $now = now();
        DB::table('reward_tiers')->insert([
            ['id' => 1, 'name' => 'Bronze', 'min_coins' => 0, 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => ' Silver ', 'min_coins' => 50, 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Gold', 'min_coins' => 100, 'sort_order' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('reward_tier_configs')->insert([
            ['reward_tier_id' => 1, 'total_discount_percentage' => 5, 'free_checkup_count' => 0, 'earned_coins_per_booking' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['reward_tier_id' => 2, 'total_discount_percentage' => 15, 'free_checkup_count' => 2, 'earned_coins_per_booking' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['reward_tier_id' => 3, 'total_discount_percentage' => 20, 'free_checkup_count' => 0, 'earned_coins_per_booking' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function createTables(): void
    {
        foreach ([
            'hms_webhook_inbox',
            'hms_external_id_maps',
            'hms_integrations',
            'reward_tier_discount_usages',
            'reward_tier_package_discounts',
            'user_reward_progress',
            'reward_tier_configs',
            'reward_tiers',
            'subscription_usage_logs',
            'user_family_subscriptions',
            'family_packages',
            'coins',
            'diagnostic_packages',
            'doctors',
            'hospitals',
            'persons',
            'healthinpocket_users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('healthinpocket_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile_num')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('persons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('hip_user_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('relationship')->nullable();
            $table->string('gender')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
        Schema::create('hospitals', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('diagnostic_center_id')->nullable();
            $table->string('status')->nullable();
        });
        Schema::create('doctors', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('consultation_fee')->nullable();
            $table->json('hospital_ids')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('diagnostic_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->nullable();
            $table->unsignedBigInteger('diagnostic_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('family_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('duration_days')->default(30);
            $table->unsignedInteger('max_members')->default(1);
            $table->unsignedInteger('max_consultations')->default(0);
            $table->unsignedInteger('max_lab_tests')->default(0);
            $table->unsignedInteger('max_hip_coins')->default(0);
            $table->json('branch_ids')->nullable();
            $table->json('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('user_family_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('hip_user_id');
            $table->unsignedBigInteger('family_package_id');
            $table->json('covered_member_ids')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active');
            $table->string('payment_status')->default('paid');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('subscription_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_family_subscription_id');
            $table->uuid('hip_user_id')->nullable();
            $table->string('usage_type');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('booking_type')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('reward_tiers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('min_coins')->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('reward_tier_configs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('reward_tier_id');
            $table->decimal('total_discount_percentage', 5, 2)->nullable();
            $table->unsignedInteger('free_checkup_count')->default(0);
            $table->unsignedInteger('earned_coins_per_booking')->default(0);
            $table->timestamps();
        });
        Schema::create('user_reward_progress', function (Blueprint $table): void {
            $table->id();
            $table->uuid('hip_user_id');
            $table->unsignedBigInteger('reward_tier_id');
            $table->unsignedInteger('tier_generation')->default(0);
            $table->unsignedInteger('earned_coins_in_tier')->default(0);
            $table->unsignedInteger('total_lifetime_coins')->default(0);
            $table->timestamps();
        });
        Schema::create('reward_tier_package_discounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('reward_tier_id');
            $table->unsignedBigInteger('diagnostic_package_id');
            $table->string('promotion_type');
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('reward_tier_discount_usages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('hip_user_id');
            $table->unsignedBigInteger('reward_tier_id');
            $table->unsignedInteger('tier_generation')->default(0);
            $table->string('booking_type', 32);
            $table->unsignedBigInteger('booking_id');
            $table->string('status')->default('consumed');
            $table->timestamps();
        });
        Schema::create('coins', function (Blueprint $table): void {
            $table->id();
            $table->uuid('person_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->integer('coins')->default(0);
            $table->timestamps();
        });
        Schema::create('hms_integrations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('hospital_ids');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->char('api_key_hash', 64);
            $table->text('signing_secret');
            $table->json('allowed_ips')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('rate_limit_per_minute')->default(120);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('hms_external_id_maps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('integration_id');
            $table->string('entity_type', 32);
            $table->string('external_id');
            $table->string('local_id');
            $table->timestamps();
        });
        Schema::create('hms_webhook_inbox', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('integration_id')->nullable();
            $table->uuid('request_id');
            $table->string('event_id')->nullable();
            $table->string('event_type');
            $table->string('method', 10);
            $table->string('path');
            $table->json('headers');
            $table->json('payload');
            $table->string('state', 32);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
        });
    }
}
