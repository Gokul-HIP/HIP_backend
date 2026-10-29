<?php

namespace Tests\Feature;

use App\Models\Coins;
use App\Models\FamilyPackage;
use App\Models\HmsMembership;
use App\Models\Invoice;
use App\Models\UserFamilySubscription;
use App\Services\Hms\HmsSignature;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;

class HmsMembershipPurchaseTest extends HmsBookingWebhookTest
{
    private int $memberClock = 80;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('hms_memberships');
        Schema::dropIfExists('user_rewards');
        Schema::table('healthinpocket_users', function (Blueprint $table): void {
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
        });
        Schema::table('user_family_subscriptions', function (Blueprint $table): void {
            $table->string('payment_mode')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->boolean('auto_renew')->default(false);
        });
        Schema::create('user_rewards', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_type');
            $table->uuid('hip_user_id');
            $table->boolean('is_applied')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
        Schema::create('hms_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('integration_id');
            $table->string('hms_membership_id');
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('plan_id');
            $table->uuid('hip_user_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('type', 16);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('expected_amount', 12, 2);
            $table->boolean('price_mismatch')->default(false);
            $table->string('payment_reference');
            $table->char('request_hash', 64);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['integration_id', 'hms_membership_id']);
            $table->unique(['integration_id', 'payment_reference']);
        });
    }

    public function test_new_purchase_is_visible_and_prices_a_covered_booking(): void
    {
        $plan = $this->plan('FAM-GOLD', 5000, 4);
        DB::table('hms_external_id_maps')->insert([
            'integration_id' => $this->integration->id,
            'entity_type' => 'plan',
            'external_id' => 'GOLD-EXT',
            'local_id' => $plan->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $created = $this->purchase($this->membershipBody('HMS-M-1', 'new', 'GOLD-EXT', 5000, 'RCPT-1', [
            ['patient_ref' => 'P-78', 'name' => 'Ravi', 'dob' => '2010-01-01', 'relationship' => 'Child'],
        ]));
        $created->assertCreated();
        $created->assertJsonPath('price_mismatch', false);
        $created->assertJsonPath('expected_amount', 5000);
        $created->assertJsonPath('received_amount', 5000);
        $created->assertJsonPath('membership.plan.code', 'FAM-GOLD');
        $created->assertJsonPath('membership.covered_members.0.name', 'Buyer One');
        $created->assertJsonPath('membership.covered_members.0.relationship', 'Self');
        $created->assertJsonPath('membership.covered_members.1.name', 'Ravi');
        $created->assertJsonPath('membership.covered_members.1.relationship', 'Child');
        $this->assertSame('active', UserFamilySubscription::query()->value('status'));
        $this->assertSame('paid', UserFamilySubscription::query()->value('payment_status'));
        $this->assertSame(5000.0, (float) UserFamilySubscription::query()->value('amount_paid'));
        $personId = (string) DB::table('persons')->where('hip_user_id', UserFamilySubscription::query()->value('hip_user_id'))->value('id');
        $this->assertSame(0, (int) Coins::query()->where('person_id', $personId)->value('coins'));
        $this->assertSame(50, (int) Invoice::query()->latest('id')->value('coins_earned'));

        $read = $this->memberGet('7000001111', 'P-BUY');
        $read->assertOk();
        $read->assertJsonPath('has_membership', true);
        $read->assertJsonPath('covered_members.0.relationship', 'Self');
        $read->assertJsonPath('covered_members.1.name', 'Ravi');
        $read->assertJsonPath('free_consultations.remaining', 2);
        $this->memberGet('7000001111', 'P-78')->assertOk()->assertJsonPath('patient_covered', true);

        config(['settings.fees.service_charges' => 10, 'services.service_charges_percent' => 10]);
        $covered = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-RAVI',
            'payable_amount' => 60,
            'patient' => [
                'mobile' => '8881112222',
                'name' => 'Ravi',
                'dob' => '2010-01-01',
                'gender' => 'male',
                'email' => null,
                'patient_ref' => 'P-78',
                'relationship' => 'Child',
                'primary_mobile' => '7000001111',
            ],
        ]));
        $covered->assertCreated();
        $covered->assertJsonPath('pricing.membership.applied', true);
        $covered->assertJsonPath('state', 'pending_payment');
        $this->assertSame($covered->json('pricing.service_charge'), $covered->json('pricing.total_payable'));
        $this->assertGreaterThan(0, $covered->json('pricing.service_charge'));

        $uncovered = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-OTHER',
            'payable_amount' => 600,
            'patient' => [
                'mobile' => '8881113333',
                'name' => 'Not Covered',
                'dob' => '2012-02-02',
                'gender' => 'female',
                'email' => null,
                'patient_ref' => 'P-99',
                'relationship' => 'Child',
                'primary_mobile' => '7000001111',
            ],
        ]));
        $uncovered->assertCreated();
        $uncovered->assertJsonPath('pricing.membership.applied', false);
        $this->assertGreaterThan($uncovered->json('pricing.service_charge'), $uncovered->json('pricing.total_payable'));

        $purchaser = $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-BUYER',
            'payable_amount' => 60,
            'patient' => $this->patient('7000001111', 'Buyer One', 'P-BUY'),
        ]));
        $purchaser->assertCreated();
        $purchaser->assertJsonPath('pricing.membership.applied', true);
        $this->assertSame($purchaser->json('pricing.service_charge'), $purchaser->json('pricing.total_payable'));
        $this->memberGet('7000001111')->assertJsonPath('free_consultations.remaining', 0);
    }

    public function test_renewal_extends_one_period_and_a_second_new_purchase_conflicts(): void
    {
        $this->plan('FAM-GOLD', 5000, 4);
        $first = $this->purchase($this->membershipBody('HMS-M-2', 'new', 'FAM-GOLD', 5000, 'RCPT-2'));
        $first->assertCreated();
        $subscriptionId = $first->json('membership.subscription_id');
        $end = $first->json('membership.end_date');

        $this->book('/apiv1/client/appointment/booked', $this->appointmentPayload([
            'hms_booking_id' => 'HMS-USE',
            'payable_amount' => 0,
            'patient' => $this->patient('7000001111', 'Buyer One', 'P-BUY'),
        ]))->assertCreated();
        $this->assertSame(1, DB::table('subscription_usage_logs')->count());
        $invoicesBeforeRenewal = Invoice::query()->count();

        $renewed = $this->purchase($this->membershipBody('HMS-M-3', 'renewal', 'FAM-GOLD', 5000, 'RCPT-3', [], '2026-12-01'));
        $renewed->assertCreated();
        $renewed->assertJsonPath('membership.subscription_id', $subscriptionId);
        $this->assertSame(
            \Carbon\Carbon::parse($end)->addDays(30)->toDateString(),
            $renewed->json('membership.end_date')
        );
        $this->assertSame(1, UserFamilySubscription::query()->count());
        $this->assertSame(1, DB::table('subscription_usage_logs')->count());
        $this->assertSame($invoicesBeforeRenewal, Invoice::query()->count());

        $again = $this->purchase($this->membershipBody('HMS-M-4', 'new', 'FAM-GOLD', 5000, 'RCPT-4'));
        $this->assertHmsError($again, 409, 'membership_already_active');
    }

    public function test_plan_limits_mismatch_and_idempotency(): void
    {
        $this->plan('FAM-GOLD', 5000, 4);
        $this->plan('FAM-OTHER', 1000, 4, [2]);
        $this->plan('FAM-TINY', 100, 1);

        $this->assertHmsError($this->purchase($this->membershipBody('HMS-M-X', 'new', 'MISSING', 1, 'RCPT-X')), 404, 'not_found');
        $this->assertHmsError($this->purchase($this->membershipBody('HMS-M-H', 'new', 'FAM-OTHER', 1000, 'RCPT-H')), 404, 'not_found');
        $tooMany = $this->purchase($this->membershipBody('HMS-M-MANY', 'new', 'FAM-TINY', 100, 'RCPT-MANY', [
            ['patient_ref' => 'P-1', 'name' => 'One Child', 'dob' => '2012-01-01', 'relationship' => 'Child'],
        ]));
        $this->assertHmsError($tooMany, 422, 'too_many_members');

        $mismatch = $this->purchase($this->membershipBody('HMS-M-MIS', 'new', 'FAM-GOLD', 100, 'RCPT-MIS'));
        $mismatch->assertCreated();
        $mismatch->assertJsonPath('price_mismatch', true);
        $mismatch->assertJsonPath('expected_amount', 5000);
        $mismatch->assertJsonPath('received_amount', 100);
        $this->assertTrue((bool) HmsMembership::query()->where('hms_membership_id', 'HMS-M-MIS')->value('price_mismatch'));
        $this->assertSame('active', UserFamilySubscription::query()->value('status'));

        $body = $this->membershipBody('HMS-M-ID', 'new', 'FAM-TINY', 100, 'RCPT-ID');
        $body['patient']['mobile'] = '7000002222';
        $body['patient']['patient_ref'] = 'P-ID';
        $first = $this->purchase($body, 'evt-mem-1');
        $first->assertCreated();
        $sameEvent = $this->purchase($body, 'evt-mem-1');
        $sameEvent->assertCreated();
        $sameEvent->assertJsonPath('membership.subscription_id', $first->json('membership.subscription_id'));
        $this->assertSame(1, HmsMembership::query()->where('hms_membership_id', 'HMS-M-ID')->count());

        $samePayload = $this->purchase($body, 'evt-mem-2');
        $samePayload->assertOk();
        $samePayload->assertJsonPath('membership.subscription_id', $first->json('membership.subscription_id'));

        $changed = $body;
        $changed['amount_paid'] = 90;
        $conflict = $this->purchase($changed, 'evt-mem-3');
        $this->assertHmsError($conflict, 409, 'membership_conflict');

        $reused = $this->purchase($this->membershipBody('HMS-M-OTHER', 'new', 'FAM-TINY', 100, 'RCPT-ID'));
        $this->assertHmsError($reused, 409, 'payment_reference_used');
    }

    public function test_extend_adds_members_and_a_future_start_is_rejected_on_a_new_period(): void
    {
        $this->plan('FAM-GOLD', 5000, 4);
        $this->plan('FAM-TINY', 100, 1);
        $future = now()->addDay()->toDateString();
        $this->assertHmsError(
            $this->purchase($this->membershipBody('HMS-FUT', 'new', 'FAM-GOLD', 5000, 'RCPT-FUT', [], $future)),
            422,
            'start_date_in_future'
        );
        $this->assertSame(0, UserFamilySubscription::query()->count());

        $past = now()->subDays(3)->toDateString();
        $created = $this->purchase($this->membershipBody('HMS-PAST', 'new', 'FAM-GOLD', 5000, 'RCPT-PAST', [], $past));
        $created->assertCreated();
        $created->assertJsonPath('membership.start_date', $past);
        $end = $created->json('membership.end_date');

        $renewed = $this->purchase($this->membershipBody('HMS-EXT', 'renewal', 'FAM-GOLD', 5000, 'RCPT-EXT', [
            ['patient_ref' => 'P-KID', 'name' => 'Added Kid', 'dob' => '2015-03-03', 'relationship' => 'Child'],
        ], $future));
        $renewed->assertCreated();
        $renewed->assertJsonPath('membership.start_date', $past);
        $renewed->assertJsonPath('membership.end_date', \Carbon\Carbon::parse($end)->addDays(30)->toDateString());
        $names = collect($renewed->json('membership.covered_members'))->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['Buyer One', 'Added Kid'], $names);

        $body = $this->membershipBody('HMS-TINY', 'new', 'FAM-TINY', 100, 'RCPT-TINY');
        $body['patient']['mobile'] = '7000003333';
        $body['patient']['patient_ref'] = 'P-TINY';
        $tiny = $this->purchase($body);
        $tiny->assertCreated();
        $subscriptionId = $tiny->json('membership.subscription_id');
        $tinyEnd = $tiny->json('membership.end_date');
        $blocked = $body;
        $blocked['hms_membership_id'] = 'HMS-TINY-2';
        $blocked['type'] = 'renewal';
        $blocked['payment']['reference'] = 'RCPT-TINY-2';
        $blocked['covered_members'] = [[
            'patient_ref' => 'P-T2',
            'name' => 'Tiny Child',
            'dob' => '2016-01-01',
            'relationship' => 'Child',
        ]];
        $this->assertHmsError($this->purchase($blocked), 422, 'too_many_members');
        $kept = UserFamilySubscription::query()->find($subscriptionId);
        $this->assertSame($tinyEnd, $kept->end_date->toDateString());
        $this->assertCount(1, $kept->covered_member_ids);
    }

    public function test_a_subscription_past_its_end_date_is_not_active(): void
    {
        $this->mock(\App\Services\NotificationService::class, function ($mock): void {
            $mock->shouldReceive('notifyUser')->andReturn(false);
        });
        $this->plan('FAM-GOLD', 5000, 4);
        $created = $this->purchase($this->membershipBody('HMS-OLD', 'new', 'FAM-GOLD', 5000, 'RCPT-OLD'));
        $created->assertCreated();
        $subscriptionId = $created->json('membership.subscription_id');
        DB::table('user_family_subscriptions')->where('id', $subscriptionId)->update([
            'status' => 'active',
            'end_date' => now()->subDay()->toDateString(),
        ]);
        $hipUserId = (string) UserFamilySubscription::query()->whereKey($subscriptionId)->value('hip_user_id');
        $personId = (string) DB::table('persons')->where('hip_user_id', $hipUserId)->value('id');

        $pricing = app(\App\Services\MembershipEntitlementService::class)->priceDoctorConsultation(
            $hipUserId,
            600,
            0,
            1,
            true,
            $personId
        );
        $this->assertFalse($pricing['membership_benefit_applied']);
        $this->assertSame('active', DB::table('user_family_subscriptions')->where('id', $subscriptionId)->value('status'));

        $this->assertNull(app(\App\Services\FamilyPackageService::class)->getUserActiveSubscription($hipUserId));
        $this->assertSame('expired', DB::table('user_family_subscriptions')->where('id', $subscriptionId)->value('status'));

        $again = $this->purchase($this->membershipBody('HMS-NEXT', 'new', 'FAM-GOLD', 5000, 'RCPT-NEXT'));
        $again->assertCreated();
        $this->assertNotSame($subscriptionId, $again->json('membership.subscription_id'));
    }

    private function plan(string $code, float $price, int $maxMembers, ?array $branchIds = null): FamilyPackage
    {
        return FamilyPackage::query()->create([
            'name' => $code,
            'code' => $code,
            'price' => $price,
            'duration_days' => 30,
            'max_members' => $maxMembers,
            'max_consultations' => 2,
            'max_lab_tests' => 0,
            'max_hip_coins' => 0,
            'branch_ids' => $branchIds,
            'benefits' => [],
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $covered
     * @return array<string, mixed>
     */
    private function membershipBody(string $id, string $type, string $plan, float $amount, string $reference, array $covered = [], ?string $start = '2026-10-28'): array
    {
        return [
            'hms_membership_id' => $id,
            'type' => $type,
            'plan_code' => $plan,
            'hospital_code' => 'H1',
            'patient' => [
                'mobile' => '7000001111',
                'name' => 'Buyer One',
                'dob' => '1990-04-04',
                'gender' => 'male',
                'email' => null,
                'patient_ref' => 'P-BUY',
            ],
            'covered_members' => $covered,
            'amount_paid' => $amount,
            'payment' => [
                'reference' => $reference,
                'method' => 'cash',
                'paid_at' => '2026-10-28T16:45:00+05:30',
            ],
            'start_date' => $start,
        ];
    }

    private function purchase(array $payload, string $eventId = 'evt-auto'): TestResponse
    {
        $raw = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = (string) (now()->getTimestamp() + $this->memberClock++);
        $uri = '/apiv1/client/membership/purchased';
        $signature = HmsSignature::sign('hms-test-secret', $timestamp, 'POST', $uri, $raw === false ? '' : $raw);

        return $this->call('POST', $uri, [], [], [], [
            'HTTP_X_HMS_KEY' => 'hms-test-key',
            'HTTP_X_HMS_TIMESTAMP' => $timestamp,
            'HTTP_X_HMS_SIGNATURE' => $signature,
            'HTTP_X_HMS_EVENT_ID' => $eventId === 'evt-auto' ? 'evt-m-'.$this->memberClock : $eventId,
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], $raw === false ? '' : $raw);
    }

    private function memberGet(string $mobile, ?string $patientRef = null): TestResponse
    {
        $uri = '/apiv1/client/get/patient/membership?mobile='.$mobile.'&hospital_code=H1';
        if ($patientRef !== null) {
            $uri .= '&patient_ref='.$patientRef;
        }
        $timestamp = (string) (now()->getTimestamp() + $this->memberClock++);
        $signature = HmsSignature::sign('hms-test-secret', $timestamp, 'GET', $uri, '');

        return $this->call('GET', $uri, [], [], [], [
            'HTTP_X_HMS_KEY' => 'hms-test-key',
            'HTTP_X_HMS_TIMESTAMP' => $timestamp,
            'HTTP_X_HMS_SIGNATURE' => $signature,
            'HTTP_ACCEPT' => 'application/json',
        ]);
    }
}
