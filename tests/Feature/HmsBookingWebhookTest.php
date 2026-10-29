<?php

namespace Tests\Feature;

use App\Models\Coins;
use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\HmsBooking;
use App\Models\HmsIntegration;
use App\Models\HmsWebhookInbox;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\RewardTierDiscountUsage;
use App\Models\SecondOpinion;
use App\Models\Transactions;
use App\Services\BookingPricingPreviewService;
use App\Services\Hms\HmsSignature;
use App\Services\RewardTierService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class HmsBookingWebhookTest extends TestCase
{
    private const API_KEY = 'hms-test-key';

    private const SECRET = 'hms-test-secret';

    protected HmsIntegration $integration;

    protected string $userId;

    protected string $primaryPersonId;

    protected string $doctorId;

    private int $clockStep = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'settings.fees.service_charges' => 0,
            'services.service_charges_percent' => 0,
            'hms.pending_hold_minutes' => 30,
            'reward_tiers.discount_reservation_hold_minutes' => 15,
        ]);
        $this->createTables();

        $this->userId = (string) Str::uuid();
        $this->primaryPersonId = (string) Str::uuid();
        $this->doctorId = (string) Str::uuid();

        DB::table('healthinpocket_users')->insert([
            'id' => $this->userId,
            'hip_id' => 'HIP00099',
            'first_name' => 'Asha',
            'last_name' => 'Rao',
            'mobile_num' => '9990001111',
            'password' => 'secret',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('persons')->insert([
            'id' => $this->primaryPersonId,
            'hip_user_id' => $this->userId,
            'parent_id' => $this->primaryPersonId,
            'mrn' => 'MRN00099',
            'first_name' => 'Asha',
            'last_name' => 'Rao',
            'mobile' => '9990001111',
            'is_primary' => true,
            'relationship' => 'Self',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('coins')->insert([
            'person_id' => $this->primaryPersonId,
            'coins' => 25,
            'created_at' => now(),
            'updated_at' => now(),
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
            'discount' => 50,
            'diagnostic_id' => 10,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('reward_tiers')->insert([
            'id' => 1,
            'name' => 'Bronze',
            'min_coins' => 0,
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('reward_tier_configs')->insert([
            'reward_tier_id' => 1,
            'total_discount_percentage' => 10,
            'free_checkup_count' => 2,
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

    public function test_doctor_booking_matches_quote_and_stays_pending(): void
    {
        $payload = $this->appointmentPayload();
        $quote = app(BookingPricingPreviewService::class)->doctor(
            $this->userId,
            600,
            0,
            0,
            1,
            [],
            true,
            $this->primaryPersonId
        );
        $response = $this->book('/apiv1/client/appointment/booked', $payload);

        $response->assertCreated();
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('booking_type', 'doctor');
        $response->assertJsonPath('state', 'pending_payment');
        $response->assertJsonPath('price_mismatch', false);
        $response->assertJsonPath('patient.person_id', $this->primaryPersonId);
        $response->assertJsonPath('patient.created', false);
        $response->assertJsonPath('payable_amount', 540);
        $response->assertJsonMissingPath('booking_reference');
        $this->assertNotNull($response->json('hold_expires_at'));

        $this->assertEquals(
            json_decode(json_encode($quote['pricing']), true),
            $response->json('pricing')
        );

        $booking = DoctorBooking::query()->first();
        $this->assertSame('pending', $booking->status);
        $this->assertSame('pending', $booking->payment_status);
        $this->assertTrue((bool) $booking->is_online_payment);
        $this->assertSame(0, (int) $booking->coins_used);
        $this->assertSame('2026-11-02', $booking->booking_date?->format('Y-m-d'));
        $this->assertSame(['10:30'], $booking->required_time_slots);
        $this->assertSame('In-Person', $booking->consultation_type);
        $this->assertSame('In-Clinic', $booking->appointment_type);
        $this->assertFalse($booking->isOnlineConsultation());
        $this->assertSame(0, DoctorBooking::query()->onlineConsultation()->count());
        $this->assertSame('pending', Invoice::query()->first()->status);
        $this->assertSame('pending', Transactions::query()->first()->status);
        $this->assertNull(Transactions::query()->first()->payment_method);
        $this->assertSame(0, DB::table('razorpay_payments')->count());
        $this->assertSame(25, (int) Coins::query()->value('coins'));
        $this->assertSame('reserved', RewardTierDiscountUsage::query()->value('status'));
        $this->assertTrue(RewardTierDiscountUsage::query()->value('expires_at') !== null);
    }

    public function test_second_opinion_ignores_membership_and_uses_reward_only(): void
    {
        $this->createMembership();
        $payload = $this->appointmentPayload(['appointment_type' => 'second_opinion', 'hms_booking_id' => 'HMS-SO']);
        $response = $this->book('/apiv1/client/appointment/booked', $payload);

        $response->assertCreated();
        $response->assertJsonPath('booking_type', 'second_opinion');
        $response->assertJsonPath('pricing.membership.applied', false);
        $response->assertJsonPath('payable_amount', 540);
        $this->assertSame(0, DB::table('subscription_usage_logs')->count());
        $this->assertSame('second_opinion', RewardTierDiscountUsage::query()->value('booking_type'));
        $this->assertSame('pending', SecondOpinion::query()->value('status'));
        $this->assertSame('pending', SecondOpinion::query()->value('payment_status'));
    }

    public function test_package_booking_uses_hms_fee_and_alias_route(): void
    {
        $package = \App\Models\DiagnosticPackage::query()->find(5);
        $package->price = 1000;
        $package->discount = null;
        $quote = app(BookingPricingPreviewService::class)->package(
            $this->userId,
            $package,
            'diagnostic',
            0,
            0,
            $this->primaryPersonId
        );
        $payload = $this->packagePayload();
        $response = $this->book('/apiv1/client/diagnostic/booked', $payload);
        $response->assertCreated();
        $response->assertJsonPath('booking_type', 'package');
        $response->assertJsonPath('payable_amount', 900);

        $this->assertEquals(
            json_decode(json_encode($quote['pricing']), true),
            $response->json('pricing')
        );

        $alias = $this->book('/apiv1/client/diagonistic/booked', $this->packagePayload(['hms_booking_id' => 'HMS-PKG-2']));
        $alias->assertCreated();
        $this->assertSame(2, DiagnosticTestBooking::query()->count());
        $this->assertSame('pending', DiagnosticTestBooking::query()->first()->payment_status);
        $this->assertSame(0, (int) DiagnosticTestBooking::query()->sum('coins_used'));
    }

    public function test_unknown_patient_is_created_with_wallet_progress_and_mrn(): void
    {
        $payload = $this->appointmentPayload([
            'hms_booking_id' => 'HMS-NEW',
            'patient' => $this->patient('7770001111', 'New Patient', 'P-NEW'),
        ]);
        $response = $this->book('/apiv1/client/appointment/booked', $payload);
        $response->assertCreated();
        $response->assertJsonPath('patient.created', true);

        $person = Persons::query()->find($response->json('patient.person_id'));
        $this->assertNotNull($person);
        $this->assertSame($person->id, $person->parent_id);
        $this->assertMatchesRegularExpression('/^MRN\d+$/', (string) $person->mrn);
        $this->assertNotNull($person->hip_user_id);
        $user = DB::table('healthinpocket_users')->where('id', $person->hip_user_id)->first();
        $this->assertNull($user->password);
        $this->assertSame('7770001111', $user->mobile_num);
        $this->assertSame(0, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertSame(1, DB::table('user_reward_progress')->where('hip_user_id', $person->hip_user_id)->count());
        $this->assertSame(25, (int) Coins::query()->where('person_id', $this->primaryPersonId)->value('coins'));
    }

    public function test_dependent_is_created_under_the_primary_and_existing_patient_is_reused(): void
    {
        $created = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-DEP',
            'patient' => [
                'mobile' => '8880002222',
                'name' => 'Ravi Rao',
                'dob' => '2015-01-01',
                'gender' => 'male',
                'email' => null,
                'patient_ref' => 'P-DEP',
                'relationship' => 'Son',
                'primary_mobile' => '9990001111',
            ],
        ]));
        $created->assertCreated();
        $created->assertJsonPath('patient.created', true);
        $child = Persons::query()->find($created->json('patient.person_id'));
        $this->assertNull($child->hip_user_id);
        $this->assertSame($this->primaryPersonId, $child->parent_id);

        $again = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-SELF',
            'patient' => $this->patient('9990001111', 'Asha Rao', 'P-SELF'),
        ]));
        $again->assertCreated();
        $again->assertJsonPath('patient.created', false);
        $again->assertJsonPath('patient.person_id', $this->primaryPersonId);
        $this->assertSame(1, DB::table('healthinpocket_users')->where('mobile_num', '9990001111')->count());
    }

    public function test_membership_applies_only_when_the_person_is_covered(): void
    {
        $this->createMembership(['covered_member_ids' => json_encode([$this->primaryPersonId])]);

        $covered = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-COVERED',
            'payable_amount' => 0,
        ]));
        $covered->assertCreated();
        $covered->assertJsonPath('pricing.membership.applied', true);
        $covered->assertJsonPath('state', 'confirmed');
        $this->assertSame('membership_consumed', DB::table('subscription_usage_logs')->value('notes'));
        $this->assertSame(0, RewardTierDiscountUsage::query()->count());
        $this->assertSame('confirmed', DoctorBooking::query()->value('status'));
        $this->assertSame('paid', DoctorBooking::query()->value('payment_status'));
        $this->assertSame('completed', Invoice::query()->value('status'));
        $this->assertSame('free', Invoice::query()->value('payment_method'));

        $other = (string) Str::uuid();
        DB::table('user_family_subscriptions')->update([
            'covered_member_ids' => json_encode([$other]),
        ]);
        $uncovered = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-OUT',
            'payable_amount' => 540,
        ]));
        $uncovered->assertCreated();
        $uncovered->assertJsonPath('pricing.membership.applied', false);
        $uncovered->assertJsonPath('state', 'pending_payment');
        $this->assertSame(1, DB::table('subscription_usage_logs')->count());
    }

    public function test_package_membership_is_free_before_reward(): void
    {
        $this->createMembership([
            'max_consultations' => 0,
            'benefits' => json_encode([[
                'diagnostic_package_id' => 5,
                'benefit_type' => 'free',
                'max_lab_tests' => 0,
            ]]),
        ]);
        $response = $this->book('/apiv1/client/diagnostic/booked', $this->packagePayload([
            'payable_amount' => 0,
        ]));
        $response->assertCreated();
        $response->assertJsonPath('pricing.membership.applied', true);
        $response->assertJsonPath('state', 'confirmed');
        $this->assertSame('diagnostic_test_booking', DB::table('subscription_usage_logs')->value('booking_type'));
        $this->assertSame(0, RewardTierDiscountUsage::query()->count());
    }

    public function test_reward_reservation_survives_the_app_hold_and_is_released_on_expiry(): void
    {
        DB::table('reward_tier_configs')->update(['free_checkup_count' => 1]);
        $response = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload());
        $response->assertCreated();
        $usage = RewardTierDiscountUsage::query()->first();
        $this->assertSame('reserved', $usage->status);
        $this->assertTrue($usage->expires_at->greaterThan(now()->addMinutes(20)));

        RewardTierDiscountUsage::query()->insert([
            'hip_user_id' => $this->userId,
            'reward_tier_id' => 1,
            'tier_generation' => 0,
            'booking_type' => 'doctor_booking',
            'booking_id' => 999,
            'status' => 'reserved',
            'expires_at' => null,
            'created_at' => now()->subMinutes(20),
            'updated_at' => now()->subMinutes(20),
        ]);
        app(RewardTierService::class)->releaseStaleDiscountReservations();
        $this->assertSame('reserved', $usage->fresh()->status);
        $this->assertSame('released', RewardTierDiscountUsage::query()->where('booking_id', 999)->value('status'));

        HmsBooking::query()->update(['hold_expires_at' => now()->subMinute()]);
        Artisan::call('hms:bookings:expire-unpaid');
        $this->assertSame('released', $usage->fresh()->status);
        $this->assertSame(HmsBooking::STATUS_EXPIRED, HmsBooking::query()->value('status'));
        $this->assertSame('cancelled', DoctorBooking::query()->value('status'));
        $this->assertSame('cancelled', Invoice::query()->value('status'));
        $this->assertSame('cancelled', Transactions::query()->value('status'));
        $this->assertSame(1, HmsWebhookInbox::query()->where('event_type', 'booking.expired')->count());
    }

    public function test_membership_reservation_is_released_on_expiry(): void
    {
        config(['settings.fees.service_charges' => 10, 'services.service_charges_percent' => 10]);
        $this->createMembership();
        $response = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'payable_amount' => 60,
        ]));
        $response->assertCreated();
        $response->assertJsonPath('state', 'pending_payment');
        $this->assertSame('membership_reserved', DB::table('subscription_usage_logs')->value('notes'));

        HmsBooking::query()->update(['hold_expires_at' => now()->subMinute()]);
        Artisan::call('hms:bookings:expire-unpaid');
        $this->assertSame(0, DB::table('subscription_usage_logs')->count());
        $this->assertSame('cancelled', DoctorBooking::query()->value('status'));
        $this->assertSame('pending', DoctorBooking::query()->value('payment_status'));
    }

    public function test_price_mismatch_is_held_and_not_confirmed(): void
    {
        $this->createMembership();
        $response = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'payable_amount' => 1,
        ]));
        $response->assertCreated();
        $response->assertJsonPath('price_mismatch', true);
        $response->assertJsonPath('state', 'pending_payment');
        $response->assertJsonPath('payable_amount', 0);
        $response->assertJsonPath('hms_payable_amount', 1);
        $this->assertSame(HmsBooking::STATUS_HELD, HmsBooking::query()->value('status'));
        $this->assertSame('pending', DoctorBooking::query()->value('status'));
        $this->assertSame('pending', DoctorBooking::query()->value('payment_status'));
        $this->assertSame('membership_reserved', DB::table('subscription_usage_logs')->value('notes'));
        $this->assertArrayHasKey('reward_tier', $response->json('pricing'));
        $this->assertArrayHasKey('membership', $response->json('pricing'));
        $this->assertSame(0, $response->json('pricing.total_payable'));
    }

    public function test_idempotency_replays_the_same_payload_and_conflicts_on_a_different_one(): void
    {
        $payload = $this->appointmentPayload();
        $first = $this->book('/apiv1/client/appointment/booked', $payload, 'evt-1');
        $first->assertCreated();

        $duplicateEvent = $this->book('/apiv1/client/appointment/booked', $payload, 'evt-1');
        $duplicateEvent->assertCreated();
        $duplicateEvent->assertJsonPath('booking_id', $first->json('booking_id'));
        $this->assertSame(1, DoctorBooking::query()->count());
        $this->assertSame(1, HmsWebhookInbox::query()->where('state', 'duplicate')->count());

        $replay = $this->book('/apiv1/client/appointment/booked', $payload, 'evt-2');
        $replay->assertOk();
        $replay->assertJsonPath('booking_id', $first->json('booking_id'));
        $replay->assertJsonPath('patient.created', false);
        $this->assertSame(1, HmsBooking::query()->count());

        $money = $payload;
        $money['original_fee'] = 600.00;
        $money['payable_amount'] = 540.00;
        $this->book('/apiv1/client/appointment/booked', $money, 'evt-3')->assertOk();

        $changed = $payload;
        $changed['slot_time'] = '11:00';
        $conflict = $this->book('/apiv1/client/appointment/booked', $changed, 'evt-4');
        $conflict->assertStatus(409);
        $conflict->assertJsonPath('code', 'booking_conflict');
        $this->assertSame(HmsBooking::query()->value('request_hash'), $conflict->json('request_hash'));
        $this->assertSame(1, DoctorBooking::query()->count());
    }

    public function test_optional_patient_ref_dependent_dedupe_and_online_consultation_type(): void
    {
        $withoutRef = $this->appointmentPayload();
        unset($withoutRef['patient']['patient_ref']);
        $this->book('/apiv1/client/appointment/booked', $withoutRef)->assertCreated();

        $missingPayload = $this->appointmentPayload([
            'hms_booking_id' => 'HMS-NOREF',
            'patient' => [
                'mobile' => '8880003333',
                'name' => 'Mini Rao',
                'dob' => '2016-02-02',
                'relationship' => 'Daughter',
                'primary_mobile' => '9990001111',
            ],
        ]);
        unset($missingPayload['patient']['patient_ref']);
        $missing = $this->book('/apiv1/client/appointment/booked', $missingPayload);
        $this->assertHmsError($missing, 422, 'patient_ref_required');

        $child = [
            'mobile' => '8880003333',
            'name' => 'Mini Rao',
            'dob' => '2016-02-02',
            'gender' => 'female',
            'patient_ref' => 'P-MINI',
            'relationship' => 'Daughter',
            'primary_mobile' => '7000001111',
        ];
        $created = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-MINI',
            'patient' => $child,
            'primary_patient' => ['name' => 'Primary Parent'],
        ]));
        $created->assertCreated();
        $created->assertJsonPath('patient.created', true);
        $primaryUser = DB::table('healthinpocket_users')->where('mobile_num', '7000001111')->first();
        $this->assertSame('Primary', $primaryUser->first_name);
        $personId = $created->json('patient.person_id');

        $again = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-MINI-2',
            'patient' => array_merge($child, ['patient_ref' => 'P-MINI-2', 'mobile' => '8880004444']),
        ]));
        $again->assertCreated();
        $again->assertJsonPath('patient.created', false);
        $again->assertJsonPath('patient.person_id', $personId);

        $online = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-ONLINE',
            'consultation_type' => 'online',
        ]));
        $online->assertCreated();
        $onlineBooking = DoctorBooking::query()->find($online->json('booking_id'));
        $this->assertSame('Online', $onlineBooking->consultation_type);
        $this->assertTrue($onlineBooking->isOnlineConsultation());
        $this->assertSame(1, DoctorBooking::query()->onlineConsultation()->count());
        $this->assertTrue(
            DoctorBooking::query()->where('hospital_id', 1)->whereKey($onlineBooking->id)->exists()
        );
        $this->assertTrue(
            DB::table('doctor_bookings')->where('patient_id', $this->primaryPersonId)->where('id', $online->json('booking_id'))->exists()
        );
    }

    public function test_missing_event_id_labtest_and_forbidden_hospital(): void
    {
        $missing = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload(), '');
        $this->assertHmsError($missing, 422, 'missing_event_id');

        $lab = $this->book('/apiv1/client/labtest/booked', ['hms_booking_id' => 'X']);
        $this->assertHmsError($lab, 501, 'not_supported');
        $this->assertSame(1, HmsWebhookInbox::query()->where('event_type', 'labtest.booked')->count());

        $outside = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hospital_code' => '2',
        ]));
        $this->assertHmsError($outside, 403, 'forbidden_hospital');

        $unknown = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'doctor_id' => 'NO-DOCTOR',
        ]));
        $this->assertHmsError($unknown, 404, 'not_found');
    }

    protected function book(string $uri, array $payload, string $eventId = 'evt-auto'): TestResponse
    {
        $raw = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = (string) (now()->getTimestamp() + $this->clockStep++);
        $signature = HmsSignature::sign(self::SECRET, $timestamp, 'POST', $uri, $raw === false ? '' : $raw);
        $server = [
            'HTTP_X_HMS_KEY' => self::API_KEY,
            'HTTP_X_HMS_TIMESTAMP' => $timestamp,
            'HTTP_X_HMS_SIGNATURE' => $signature,
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ];
        if ($eventId !== '') {
            $server['HTTP_X_HMS_EVENT_ID'] = $eventId === 'evt-auto'
                ? 'evt-'.$this->clockStep
                : $eventId;
        }

        return $this->call('POST', $uri, [], [], [], $server, $raw === false ? '' : $raw);
    }

    protected function appointmentPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'hms_booking_id' => 'HMS-10045',
            'appointment_type' => 'doctor',
            'hospital_code' => 'H1',
            'doctor_id' => 'DOC-1',
            'appointment_date' => '2026-11-02',
            'slot_time' => '10:30',
            'original_fee' => 600,
            'payable_amount' => 540,
            'patient' => $this->patient('9990001111', 'Asha Rao', 'P-77'),
            'notes' => null,
        ], $overrides);
    }

    protected function packagePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'hms_booking_id' => 'HMS-PKG',
            'hospital_code' => 'H1',
            'package_code' => 'EXT-PKG',
            'original_fee' => 1000,
            'payable_amount' => 900,
            'patient' => $this->patient('9990001111', 'Asha Rao', 'P-77'),
            'notes' => null,
        ], $overrides);
    }

    protected function patient(string $mobile, string $name, string $ref): array
    {
        return [
            'mobile' => $mobile,
            'name' => $name,
            'dob' => '1990-05-01',
            'gender' => 'female',
            'email' => null,
            'patient_ref' => $ref,
            'relationship' => 'Self',
            'primary_mobile' => null,
        ];
    }

    protected function createMembership(array $overrides = []): void
    {
        $packageFields = array_intersect_key($overrides, array_flip(['benefits', 'max_consultations', 'branch_ids']));
        DB::table('family_packages')->insert([
            'id' => 1,
            'name' => 'Family Gold',
            'code' => 'FAM-GOLD',
            'price' => 1000,
            'duration_days' => 30,
            'max_members' => 4,
            'max_consultations' => $packageFields['max_consultations'] ?? 4,
            'max_lab_tests' => 0,
            'max_hip_coins' => 0,
            'branch_ids' => $packageFields['branch_ids'] ?? json_encode([1]),
            'benefits' => $packageFields['benefits'] ?? json_encode([]),
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_family_subscriptions')->insert(array_merge([
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
        ], array_diff_key($overrides, $packageFields)));
    }

    protected function assertHmsError(TestResponse $response, int $status, string $code): void
    {
        $response->assertStatus($status);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('code', $code);
    }

    private function createTables(): void
    {
        foreach ([
            'hms_bookings', 'razorpay_payments', 'transactions', 'invoices', 'doctor_booking_statuses',
            'doctor_bookings', 'second_opinions', 'diagnostic_test_bookings', 'hms_webhook_inbox',
            'hms_external_id_maps', 'hms_integrations', 'reward_tier_discount_usages',
            'reward_tier_package_discounts', 'user_reward_progress', 'reward_tier_configs', 'reward_tiers',
            'subscription_usage_logs', 'user_family_subscriptions', 'family_packages', 'coins',
            'diagnostic_packages', 'doctors', 'hospitals', 'persons', 'healthinpocket_users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('healthinpocket_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('hip_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_num')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
        });
        Schema::create('persons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('hip_user_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->string('mrn')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('relationship')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
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
            $table->json('lab_tests')->nullable();
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
            $table->timestamp('cancelled_at')->nullable();
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
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('coins', function (Blueprint $table): void {
            $table->id();
            $table->uuid('person_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->integer('coins')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        $this->bookingTables();
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
        Schema::create('hms_bookings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('integration_id');
            $table->string('hms_booking_id');
            $table->string('booking_type', 32);
            $table->unsignedBigInteger('booking_id');
            $table->uuid('hip_user_id');
            $table->uuid('person_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('status', 32);
            $table->decimal('expected_amount', 12, 2);
            $table->decimal('hms_payable_amount', 12, 2);
            $table->boolean('price_mismatch')->default(false);
            $table->json('pricing_snapshot');
            $table->char('request_hash', 64);
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->string('refund_status', 32)->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->unsignedInteger('coins_reversed')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['integration_id', 'hms_booking_id']);
        });
    }

    private function bookingTables(): void
    {
        Schema::create('doctor_bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->uuid('member_id')->nullable();
            $table->uuid('patient_id')->nullable();
            $table->string('relationship')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->uuid('doctor_id');
            $table->date('booking_date')->nullable();
            $table->json('required_time_slots')->nullable();
            $table->text('message')->nullable();
            $table->text('purpose')->nullable();
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
            $table->string('consultation_type')->nullable();
            $table->string('appointment_type')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('doctor_booking_statuses', function (Blueprint $table): void {
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
        Schema::create('second_opinions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('member_id')->nullable();
            $table->uuid('patient_id')->nullable();
            $table->string('patient_name')->nullable();
            $table->text('question_for_doctor')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
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
            $table->string('status')->default('pending');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('diagnostic_test_bookings', function (Blueprint $table): void {
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
        Schema::create('invoices', function (Blueprint $table): void {
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
            $table->text('service_types')->nullable();
            $table->text('invoice_details')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
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
        Schema::create('razorpay_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('razorpay_order_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });
    }
}
