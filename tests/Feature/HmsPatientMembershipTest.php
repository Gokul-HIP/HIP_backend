<?php

namespace Tests\Feature;

use App\Enums\MembershipPaymentType;
use App\Livewire\Admin\FamilyPackage\ManageFamilyPackages;
use App\Livewire\HospitalAdmin\Settings\ManageMembershipPackages;
use App\Models\Coins;
use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Models\HmsIntegration;
use App\Models\MembershipPackageDiscount;
use App\Models\Persons;
use App\Models\UserFamilySubscription;
use App\Services\Hms\HmsSignature;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

class HmsPatientMembershipTest extends TestCase
{
    private const API_KEY = 'hms_test_key_membership';

    private const SECRET = 'test-signing-secret';

    private int $clockStep = 0;

    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('HMS membership tests must use the sqlite testing database.');
        }

        $this->bootSchema();
        Cache::flush();
        $this->clockStep = 0;

        $user = HIPUser::query()->create([
            'hip_id' => 'HIP00011',
            'first_name' => 'Asha',
            'last_name' => 'Rao',
            'mobile_num' => '9876543210',
            'organization_id' => '5',
            'password' => null,
        ]);
        $this->userId = (string) $user->id;

        HmsIntegration::query()->create([
            'name' => 'City HMS',
            'hospital_ids' => [1],
            'api_key_hash' => HmsIntegration::hashKey(self::API_KEY),
            'signing_secret' => self::SECRET,
            'is_enabled' => true,
            'rate_limit_per_minute' => 120,
        ]);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->signed('GET', '/apiv1/client/get/patient/membership?mrn_id=MRN-1', null, [
            'signature' => str_repeat('ab', 32),
        ])->assertStatus(401)->assertJsonPath('code', 'invalid_signature');
    }

    public function test_replayed_signature_is_rejected(): void
    {
        $timestamp = (string) now()->getTimestamp();
        $uri = '/apiv1/client/get/patient/membership?mrn_id=MISSING';
        $first = $this->signed('GET', $uri, null, ['timestamp' => $timestamp]);
        $first->assertStatus(404)->assertExactJson([
            'status' => false,
            'error' => 'patient_not_found',
        ]);

        $this->signed('GET', $uri, null, ['timestamp' => $timestamp])
            ->assertStatus(401)
            ->assertJsonPath('code', 'invalid_signature');
    }

    public function test_rate_limit_is_enforced(): void
    {
        HmsIntegration::query()->update(['rate_limit_per_minute' => 2]);
        $integration = HmsIntegration::query()->first();
        RateLimiter::clear('hms-integration:'.$integration->getKey());

        $this->signed('GET', '/apiv1/client/get/patient/membership?mrn_id=MISSING-1')->assertStatus(404);
        $this->signed('GET', '/apiv1/client/get/patient/membership?mrn_id=MISSING-2')->assertStatus(404);
        $this->signed('GET', '/apiv1/client/get/patient/membership?mrn_id=MISSING-3')
            ->assertStatus(429)
            ->assertJsonPath('code', 'rate_limited');
    }

    public function test_unknown_patient_is_not_found(): void
    {
        $this->membership('NO-SUCH')->assertExactJson([
            'status' => false,
            'error' => 'patient_not_found',
        ])->assertStatus(404);
    }

    public function test_person_without_membership_has_no_discount_keys(): void
    {
        $this->person('MRN-NONE', $this->userId);

        $response = $this->membership('MRN-NONE');
        $response->assertOk();
        $response->assertJsonPath('has_membership', false);
        $response->assertJsonPath('covered_member', false);
        $response->assertJsonPath('coins.balance', 40);
        $this->assertEquals(
            app(\App\Services\Api\PaymentApiService::class)->amountForOneCoin(),
            $response->json('coins.value_per_coin')
        );
        $this->assertArrayNotHasKey('discounts', $response->json());
    }

    public function test_covered_and_uncovered_members_and_discounts_per_type(): void
    {
        $primary = $this->person('MRN-PRIMARY', $this->userId);
        $covered = Persons::query()->create([
            'mrn' => 'MRN-CHILD',
            'first_name' => 'Child',
            'mobile' => '9000000002',
            'hip_user_id' => null,
            'parent_id' => $primary->id,
            'is_primary' => false,
        ]);
        $uncovered = Persons::query()->create([
            'mrn' => 'MRN-OTHER',
            'first_name' => 'Other',
            'mobile' => '9000000003',
            'hip_user_id' => null,
            'parent_id' => $primary->id,
            'is_primary' => false,
        ]);

        $package = FamilyPackage::query()->create([
            'name' => 'Family Gold',
            'price' => 1000,
            'duration_days' => 365,
            'max_members' => 4,
            'max_consultations' => 0,
            'is_active' => true,
        ]);
        MembershipPackageDiscount::query()->create([
            'family_package_id' => $package->id,
            'payment_type' => MembershipPaymentType::OpConsultation->value,
            'percentage' => 20,
        ]);
        MembershipPackageDiscount::query()->create([
            'family_package_id' => $package->id,
            'payment_type' => MembershipPaymentType::OpPharmacy->value,
            'percentage' => 5.5,
        ]);

        UserFamilySubscription::query()->create([
            'hip_user_id' => $this->userId,
            'family_package_id' => $package->id,
            'covered_member_ids' => [(string) $primary->id, (string) $covered->id],
            'start_date' => '2026-01-01',
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'active',
        ]);

        $coveredResponse = $this->membership('MRN-CHILD');
        $coveredResponse->assertOk();
        $coveredResponse->assertJsonPath('has_membership', true);
        $coveredResponse->assertJsonPath('plan_name', 'Family Gold');
        $coveredResponse->assertJsonPath('covered_member', true);
        $coveredResponse->assertJsonPath('valid_from', '2026-01-01');
        $coveredResponse->assertJsonPath('discounts.op_consultation.percentage', 20);
        $coveredResponse->assertJsonPath('discounts.op_pharmacy.percentage', 5.5);
        $this->assertSame(
            ['op_consultation', 'op_pharmacy'],
            array_keys($coveredResponse->json('discounts'))
        );

        $this->membership('MRN-OTHER')
            ->assertOk()
            ->assertJsonPath('has_membership', true)
            ->assertJsonPath('covered_member', false)
            ->assertJsonPath('discounts.op_consultation.percentage', 20);
    }

    public function test_expired_membership_is_not_active(): void
    {
        $this->person('MRN-EXPIRED', $this->userId);
        $package = FamilyPackage::query()->create([
            'name' => 'Old Plan',
            'price' => 100,
            'duration_days' => 30,
            'max_members' => 1,
            'max_consultations' => 0,
            'is_active' => true,
        ]);
        UserFamilySubscription::query()->create([
            'hip_user_id' => $this->userId,
            'family_package_id' => $package->id,
            'covered_member_ids' => null,
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->membership('MRN-EXPIRED');
        $response->assertOk();
        $response->assertJsonPath('has_membership', false);
        $this->assertArrayNotHasKey('discounts', $response->json());
    }

    public function test_mobile_fallback_finds_the_user_when_mrn_is_unknown(): void
    {
        $this->person(null, $this->userId);

        $response = $this->membership('NOT-THE-MRN', '91 98765 43210');
        $response->assertOk();
        $response->assertJsonPath('mrn_id', null);
        $response->assertJsonPath('has_membership', false);
    }

    public function test_admin_and_hospital_package_screens_save_percentages(): void
    {
        $fields = [
            'name' => 'City Plan',
            'price' => 500,
            'duration_days' => 365,
            'max_members' => 2,
            'payment_discounts' => array_merge(MembershipPaymentType::blankPercentages(), [
                MembershipPaymentType::Appointment->value => 12.5,
                MembershipPaymentType::LabTestBooking->value => 0,
            ]),
        ];

        Livewire::test(ManageFamilyPackages::class)
            ->set($fields)
            ->call('savePackage')
            ->assertHasNoErrors();

        $package = FamilyPackage::query()->where('name', 'City Plan')->first();
        $this->assertNotNull($package);
        $this->assertSame('12.50', (string) MembershipPackageDiscount::query()
            ->where('family_package_id', $package->id)
            ->where('payment_type', 'appointment')
            ->value('percentage'));
        $this->assertSame(count(MembershipPaymentType::values()), MembershipPackageDiscount::query()
            ->where('family_package_id', $package->id)
            ->count());

        Livewire::test(ManageFamilyPackages::class)
            ->set($fields)
            ->set('name', 'Too High')
            ->set('payment_discounts.ip_final_settlement', 101)
            ->call('savePackage')
            ->assertHasErrors(['payment_discounts.ip_final_settlement']);

        $staff = HIPUser::query()->create([
            'hip_id' => 'HIP00012',
            'first_name' => 'Staff',
            'organization_id' => '5',
            'password' => 'secret',
        ]);

        Livewire::actingAs($staff, 'filament')
            ->test(ManageMembershipPackages::class)
            ->set($fields)
            ->set('name', 'Hospital Plan')
            ->set('payment_discounts.diagnostic_booking', 8)
            ->call('savePackage')
            ->assertHasNoErrors();

        $hospitalPlan = FamilyPackage::query()->where('name', 'Hospital Plan')->first();
        $this->assertSame('8.00', (string) MembershipPackageDiscount::query()
            ->where('family_package_id', $hospitalPlan->id)
            ->where('payment_type', 'diagnostic_booking')
            ->value('percentage'));
    }

    private function membership(string $mrn, ?string $mobile = null): TestResponse
    {
        $uri = '/apiv1/client/get/patient/membership?mrn_id='.rawurlencode($mrn);
        if ($mobile !== null) {
            $uri .= '&mobile='.rawurlencode($mobile);
        }

        return $this->signed('GET', $uri);
    }

    private function person(?string $mrn, string $userId): Persons
    {
        $person = Persons::query()->create([
            'mrn' => $mrn,
            'first_name' => 'Asha',
            'last_name' => 'Rao',
            'mobile' => '9876543210',
            'hip_user_id' => $userId,
            'is_primary' => true,
        ]);
        $person->update(['parent_id' => $person->id]);

        Coins::query()->create([
            'person_id' => $person->id,
            'coins' => 40,
        ]);

        return $person->fresh();
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

    private function bootSchema(): void
    {
        Schema::dropIfExists('membership_package_discounts');
        Schema::dropIfExists('subscription_usage_logs');
        Schema::dropIfExists('user_family_subscriptions');
        Schema::dropIfExists('coins');
        Schema::dropIfExists('doctor_bookings');
        Schema::dropIfExists('hms_webhook_inbox');
        Schema::dropIfExists('hms_integrations');
        Schema::dropIfExists('persons');
        Schema::dropIfExists('family_packages');
        Schema::dropIfExists('diagnostic_packages');
        Schema::dropIfExists('hospitals');
        Schema::dropIfExists('healthinpocket_users');

        Schema::create('healthinpocket_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('hip_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_num')->nullable();
            $table->string('password')->nullable();
            $table->string('organization_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('profile_update')->nullable();
            $table->timestamps();
        });

        Schema::create('hospitals', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('diagnostic_center_id')->nullable();
            $table->timestamps();
        });

        Schema::create('diagnostic_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('diagnostic_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('family_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('duration_days')->default(365);
            $table->unsignedInteger('max_members')->default(1);
            $table->unsignedInteger('max_consultations')->default(0);
            $table->unsignedInteger('max_lab_tests')->default(0);
            $table->unsignedInteger('max_hip_coins')->default(0);
            $table->json('branch_ids')->nullable();
            $table->json('benefits')->nullable();
            $table->json('terms_conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('membership_package_discounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('family_package_id');
            $table->string('payment_type', 64);
            $table->decimal('percentage', 5, 2);
            $table->timestamps();
            $table->unique(['family_package_id', 'payment_type']);
        });

        Schema::create('user_family_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('hip_user_id');
            $table->unsignedBigInteger('family_package_id');
            $table->json('covered_member_ids')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_family_subscription_id');
            $table->string('usage_type');
            $table->timestamps();
        });

        Schema::create('persons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('mrn', 64)->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->uuid('hip_user_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('coins', function (Blueprint $table): void {
            $table->id();
            $table->string('person_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->integer('coins')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_bookings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('patient_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->date('booking_date')->nullable();
        });

        Schema::create('hms_integrations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('hospital_ids');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->char('api_key_hash', 64)->unique();
            $table->text('signing_secret');
            $table->json('allowed_ips')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('rate_limit_per_minute')->default(120);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hms_webhook_inbox', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('integration_id')->nullable();
            $table->uuid('request_id');
            $table->string('event_id')->nullable();
            $table->string('event_type');
            $table->string('method', 10);
            $table->string('path', 1024);
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
