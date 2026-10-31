<?php

namespace Tests\Feature;

use App\Enums\MembershipPaymentType;
use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\MembershipPackageDiscount;
use App\Models\Persons;
use App\Models\UserFamilySubscription;
use App\Models\UserReward;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserSubscriptionApiTest extends TestCase
{
    private HIPUser $user;
    private Persons $person;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\EnsureAccountIsActive::class);

        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('This test suite requires SQLite.');
        }

        $this->bootSchema();

        $this->user = HIPUser::query()->create([
            'id'         => '01a19199-0000-7000-9000-000000000001',
            'hip_id'     => 'HIP001',
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'mobile_num' => '9876543210',
            'is_active'  => true,
        ]);

        $this->person = Persons::withoutEvents(function () {
            return Persons::query()->create([
                'id'          => '01a19199-0000-7000-9000-000000000002',
                'hip_user_id' => $this->user->id,
                'first_name'  => 'John',
                'last_name'   => 'Doe',
                'mobile_num'  => '9876543210',
                'is_primary'  => true,
            ]);
        });
    }

    private function bootSchema(): void
    {
        foreach ([
            'membership_package_discounts',
            'subscription_usage_logs',
            'user_rewards',
            'user_family_subscriptions',
            'family_packages',
            'hospitals',
            'persons',
            'healthinpocket_users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('healthinpocket_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('hip_id')->nullable();
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
            $table->string('mobile_num')->nullable();
            $table->string('relationship')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('hospitals', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('family_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('duration_days')->default(365);
            $table->unsignedInteger('max_members')->default(4);
            $table->unsignedInteger('max_consultations')->default(0);
            $table->unsignedInteger('max_lab_tests')->default(0);
            $table->unsignedInteger('max_hip_coins')->default(100);
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
            $table->uuid('hip_user_id');
            $table->unsignedBigInteger('family_package_id');
            $table->json('covered_member_ids')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active');
            $table->string('payment_status')->default('paid');
            $table->string('payment_mode', 20)->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_rewards', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_type')->default('family_package');
            $table->uuid('hip_user_id');
            $table->boolean('is_applied')->default(false);
            $table->timestamp('applied_at')->nullable();
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
    }

    public function test_subscription_with_several_discounts_shows_only_positive_percentages_in_exact_order(): void
    {
        Sanctum::actingAs($this->user);

        $package = FamilyPackage::query()->create([
            'id'            => 10,
            'name'          => 'Family Health Gold',
            'duration_days' => 365,
            'price'         => 1999,
            'max_members'   => 4,
            'terms_conditions' => ['Terms apply', 'Valid 1 year'],
        ]);

        // Insert discounts out of order, including a zero percentage discount
        MembershipPackageDiscount::query()->create([
            'family_package_id' => $package->id,
            'payment_type'      => MembershipPaymentType::DiagnosticBooking->value,
            'percentage'        => 15.00,
        ]);
        MembershipPackageDiscount::query()->create([
            'family_package_id' => $package->id,
            'payment_type'      => MembershipPaymentType::OpPharmacy->value,
            'percentage'        => 5.00,
        ]);
        MembershipPackageDiscount::query()->create([
            'family_package_id' => $package->id,
            'payment_type'      => MembershipPaymentType::OpConsultation->value,
            'percentage'        => 10.00,
        ]);
        MembershipPackageDiscount::query()->create([
            'family_package_id' => $package->id,
            'payment_type'      => MembershipPaymentType::Appointment->value,
            'percentage'        => 0.00,
        ]);
        MembershipPackageDiscount::query()->create([
            'family_package_id' => $package->id,
            'payment_type'      => MembershipPaymentType::LabTestBooking->value,
            'percentage'        => 20.00,
        ]);

        $subscription = (new UserFamilySubscription)->forceFill([
            'id'                 => 101,
            'hip_user_id'        => $this->user->id,
            'family_package_id'  => $package->id,
            'covered_member_ids' => [$this->person->id],
            'start_date'         => '2026-01-01',
            'end_date'           => '2026-12-31',
            'status'             => 'active',
            'payment_status'     => 'paid',
        ]);
        $subscription->save();

        $response = $this->getJson('/api/user/subscription');

        $response->assertOk();
        $response->assertJsonPath('status', 200);

        // Usage summary must be completely absent
        $this->assertArrayNotHasKey('usage_summary', $response->json('data'));

        // Benefits must match exact requested order and format, omitting appointment (0%)
        $benefits = $response->json('data.benefits');
        $this->assertCount(4, $benefits);

        $this->assertSame([
            [
                'payment_type' => 'op_consultation',
                'title'        => 'OP consultation',
                'percentage'   => 10,
                'label'        => 'OP consultation: 10% off',
            ],
            [
                'payment_type' => 'op_pharmacy',
                'title'        => 'OP pharmacy',
                'percentage'   => 5,
                'label'        => 'OP pharmacy: 5% off',
            ],
            [
                'payment_type' => 'lab_test_booking',
                'title'        => 'Lab test booking',
                'percentage'   => 20,
                'label'        => 'Lab test booking: 20% off',
            ],
            [
                'payment_type' => 'diagnostic_booking',
                'title'        => 'Diagnostic booking',
                'percentage'   => 15,
                'label'        => 'Diagnostic booking: 15% off',
            ],
        ], $benefits);
    }

    public function test_subscription_with_no_discounts_returns_empty_benefits_array(): void
    {
        Sanctum::actingAs($this->user);

        $package = FamilyPackage::query()->create([
            'id'            => 11,
            'name'          => 'Basic Plan',
            'duration_days' => 180,
            'price'         => 999,
            'max_members'   => 2,
        ]);

        $subscription = (new UserFamilySubscription)->forceFill([
            'id'                 => 102,
            'hip_user_id'        => $this->user->id,
            'family_package_id'  => $package->id,
            'covered_member_ids' => [$this->person->id],
            'start_date'         => '2026-06-01',
            'end_date'           => '2026-11-28',
            'status'             => 'active',
            'payment_status'     => 'paid',
        ]);
        $subscription->save();

        $response = $this->getJson('/api/user/subscription');

        $response->assertOk();
        $this->assertSame([], $response->json('data.benefits'));
        $this->assertArrayNotHasKey('usage_summary', $response->json('data'));
    }

    public function test_subscription_with_decimal_percentage(): void
    {
        Sanctum::actingAs($this->user);

        $package = FamilyPackage::query()->create([
            'id'            => 12,
            'name'          => 'Decimal Discount Plan',
            'duration_days' => 365,
            'price'         => 1499,
            'max_members'   => 3,
        ]);

        MembershipPackageDiscount::query()->create([
            'family_package_id' => $package->id,
            'payment_type'      => MembershipPaymentType::OpConsultation->value,
            'percentage'        => 2.50,
        ]);
        MembershipPackageDiscount::query()->create([
            'family_package_id' => $package->id,
            'payment_type'      => MembershipPaymentType::OpPharmacy->value,
            'percentage'        => 7.75,
        ]);

        $subscription = (new UserFamilySubscription)->forceFill([
            'id'                 => 103,
            'hip_user_id'        => $this->user->id,
            'family_package_id'  => $package->id,
            'covered_member_ids' => [$this->person->id],
            'start_date'         => '2026-01-01',
            'end_date'           => '2026-12-31',
            'status'             => 'active',
            'payment_status'     => 'paid',
        ]);
        $subscription->save();

        $response = $this->getJson('/api/user/subscription');

        $response->assertOk();
        $benefits = $response->json('data.benefits');
        $this->assertCount(2, $benefits);

        $this->assertSame(2.5, $benefits[0]['percentage']);
        $this->assertSame('OP consultation: 2.5% off', $benefits[0]['label']);

        $this->assertSame(7.75, $benefits[1]['percentage']);
        $this->assertSame('OP pharmacy: 7.75% off', $benefits[1]['label']);
    }

    public function test_all_other_keys_are_preserved(): void
    {
        Sanctum::actingAs($this->user);

        $hospital = Hospital::query()->create([
            'id'   => 5,
            'name' => 'Metro Branch',
        ]);

        $package = FamilyPackage::query()->create([
            'id'               => 13,
            'name'             => 'Complete Health Plus',
            'duration_days'    => 365,
            'price'            => 2999,
            'max_members'      => 5,
            'branch_ids'       => [$hospital->id],
            'terms_conditions' => ['Term 1', 'Term 2'],
        ]);

        $subscription = (new UserFamilySubscription)->forceFill([
            'id'                 => 104,
            'hip_user_id'        => $this->user->id,
            'family_package_id'  => $package->id,
            'covered_member_ids' => [$this->person->id],
            'start_date'         => '2026-03-01',
            'end_date'           => '2027-02-28',
            'status'             => 'active',
            'payment_status'     => 'paid',
        ]);
        $subscription->save();

        UserReward::query()->create([
            'entity_id'   => $subscription->id,
            'entity_type' => UserReward::ENTITY_TYPE_FAMILY_PACKAGE,
            'hip_user_id' => $this->user->id,
            'is_applied'  => true,
            'applied_at'  => now(),
        ]);

        $response = $this->getJson('/api/user/subscription');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertSame(104, $data['subscription_id']);
        $this->assertSame('Complete Health Plus', $data['package_name']);
        $this->assertSame('active', $data['status']);
        $this->assertSame('2026-03-01', $data['start_date']);
        $this->assertSame('2027-02-28', $data['end_date']);
        $this->assertSame('2027-02-28', $data['renewal_date']);
        $this->assertSame("1 Mar '26 - 28 Feb '27", $data['validity']);
        $this->assertIsArray($data['covered_members']);
        $this->assertSame(5, $data['covered_members_limit']);
        $this->assertSame(['Metro Branch'], $data['branch_access']);
        $this->assertSame(['Term 1', 'Term 2'], $data['terms_conditions']);
        $this->assertTrue($data['is_reward_applied']);

        // Explicitly assert usage_summary absence
        $this->assertArrayNotHasKey('usage_summary', $data);
    }

    public function test_all_seven_discount_types_ordered_correctly(): void
    {
        Sanctum::actingAs($this->user);

        $package = FamilyPackage::query()->create([
            'id'            => 20,
            'name'          => 'All Benefits Plan',
            'duration_days' => 365,
            'price'         => 4999,
            'max_members'   => 4,
        ]);

        // Insert in reverse order to ensure sorting works as specified
        $allTypes = [
            MembershipPaymentType::IpFinalSettlement->value   => 7,
            MembershipPaymentType::DiagnosticBooking->value   => 6,
            MembershipPaymentType::LabTestBooking->value      => 5,
            MembershipPaymentType::Appointment->value         => 4,
            MembershipPaymentType::OpPharmacy->value          => 3,
            MembershipPaymentType::LabRadiology->value        => 2,
            MembershipPaymentType::OpConsultation->value      => 1,
        ];

        foreach ($allTypes as $type => $percentage) {
            MembershipPackageDiscount::query()->create([
                'family_package_id' => $package->id,
                'payment_type'      => $type,
                'percentage'        => $percentage,
            ]);
        }

        $subscription = (new UserFamilySubscription)->forceFill([
            'id'                 => 105,
            'hip_user_id'        => $this->user->id,
            'family_package_id'  => $package->id,
            'covered_member_ids' => [$this->person->id],
            'start_date'         => '2026-01-01',
            'end_date'           => '2026-12-31',
            'status'             => 'active',
            'payment_status'     => 'paid',
        ]);
        $subscription->save();

        $response = $this->getJson('/api/user/subscription');

        $response->assertOk();
        $benefits = $response->json('data.benefits');
        $this->assertCount(7, $benefits);

        $this->assertSame([
            [
                'payment_type' => 'op_consultation',
                'title'        => 'OP consultation',
                'percentage'   => 1,
                'label'        => 'OP consultation: 1% off',
            ],
            [
                'payment_type' => 'lab_radiology',
                'title'        => 'Lab and radiology',
                'percentage'   => 2,
                'label'        => 'Lab and radiology: 2% off',
            ],
            [
                'payment_type' => 'op_pharmacy',
                'title'        => 'OP pharmacy',
                'percentage'   => 3,
                'label'        => 'OP pharmacy: 3% off',
            ],
            [
                'payment_type' => 'appointment',
                'title'        => 'Appointment',
                'percentage'   => 4,
                'label'        => 'Appointment: 4% off',
            ],
            [
                'payment_type' => 'lab_test_booking',
                'title'        => 'Lab test booking',
                'percentage'   => 5,
                'label'        => 'Lab test booking: 5% off',
            ],
            [
                'payment_type' => 'diagnostic_booking',
                'title'        => 'Diagnostic booking',
                'percentage'   => 6,
                'label'        => 'Diagnostic booking: 6% off',
            ],
            [
                'payment_type' => 'ip_final_settlement',
                'title'        => 'IP final settlement',
                'percentage'   => 7,
                'label'        => 'IP final settlement: 7% off',
            ],
        ], $benefits);
    }

    public function test_no_active_subscription_returns_null_data(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user/subscription');

        $response->assertOk();
        $response->assertJson([
            'status'  => 200,
            'message' => 'No active subscription found',
            'data'    => null,
        ]);
    }
}
