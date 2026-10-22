<?php

namespace Tests\Unit;

use App\Models\DiagnosticPackage;
use App\Models\FamilyPackage;
use App\Models\Hospital;
use App\Models\SubscriptionUsageLog;
use App\Models\UserFamilySubscription;
use App\Services\FamilyPackageService;
use App\Services\InvoiceFeeCalculator;
use App\Services\MembershipEntitlementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MembershipEntitlementServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Membership entitlement tests must use the sqlite testing database.');
        }

        config([
            'settings.fees.service_charges' => 3,
            'services.service_charges_percent' => 3,
        ]);

        $this->bootSchema();

        foreach ([
            'subscription_usage_logs',
            'user_family_subscriptions',
            'family_packages',
            'diagnostic_packages',
            'hospitals',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    public function test_doctor_consultation_waives_base_fee_but_keeps_service_charge(): void
    {
        [$hospital, $subscription] = $this->seedMembership([
            'max_consultations' => 2,
            'branch_ids' => null,
            'benefits' => [],
        ]);

        $pricing = $this->service()->priceDoctorConsultation('user-1', 500.0, 0.0, (int) $hospital->id);

        $this->assertTrue($pricing['membership_benefit_applied']);
        $this->assertSame('free', $pricing['benefit_type']);
        $this->assertSame(500.0, $pricing['original_base_price']);
        $this->assertSame(500.0, $pricing['membership_discount']);
        $this->assertSame(0.0, $pricing['discounted_base_price']);
        $this->assertSame(15.0, $pricing['service_charges']);
        $this->assertSame(15.0, $pricing['final_payable_amount']);
        $this->assertSame($subscription->id, $pricing['subscription_id']);
    }

    public function test_exhausted_consultation_allowance_charges_full_price(): void
    {
        [$hospital, $subscription] = $this->seedMembership([
            'max_consultations' => 1,
            'branch_ids' => null,
            'benefits' => [],
        ]);

        SubscriptionUsageLog::create([
            'user_family_subscription_id' => $subscription->id,
            'hip_user_id' => 'user-1',
            'usage_type' => 'consultation',
            'booking_id' => 99,
            'booking_type' => 'doctor_booking',
            'used_at' => now(),
            'notes' => 'membership_consumed',
        ]);

        $pricing = $this->service()->priceDoctorConsultation('user-1', 500.0, 0.0, (int) $hospital->id);

        $this->assertFalse($pricing['membership_benefit_applied']);
        $this->assertSame('consultation_allowance_exhausted', $pricing['rejection_reason']);
        $this->assertSame(500.0, $pricing['discounted_base_price']);
        $this->assertSame(15.0, $pricing['service_charges']);
        $this->assertSame(515.0, $pricing['final_payable_amount']);
    }

    public function test_no_membership_keeps_normal_pricing(): void
    {
        $pricing = $this->service()->priceDoctorConsultation('nobody', 500.0);

        $this->assertFalse($pricing['membership_benefit_applied']);
        $this->assertSame('no_active_membership', $pricing['rejection_reason']);
        $this->assertSame(515.0, $pricing['final_payable_amount']);
    }

    public function test_zero_service_charge_config_allows_fully_free_eligible_booking(): void
    {
        config([
            'settings.fees.service_charges' => 0,
            'services.service_charges_percent' => 0,
        ]);

        $this->seedMembership([
            'max_consultations' => 0,
            'branch_ids' => null,
            'benefits' => [],
        ]);

        $pricing = $this->service()->priceDoctorConsultation('user-1', 500.0);

        $this->assertTrue($pricing['membership_benefit_applied']);
        $this->assertSame(0.0, $pricing['service_charges']);
        $this->assertSame(0.0, $pricing['final_payable_amount']);
    }

    public function test_diagnostic_package_free_only_when_configured_in_plan_benefits(): void
    {
        [$hospital, , $includedPackage, $otherPackage] = $this->seedMembershipWithPackages();

        $eligible = $this->service()->priceDiagnosticPackage(
            'user-1',
            1000.0,
            (int) $includedPackage->id,
            (int) $hospital->id,
            'diagnostic'
        );

        $this->assertTrue($eligible['membership_benefit_applied']);
        $this->assertSame(1000.0, $eligible['membership_discount']);
        $this->assertSame(30.0, $eligible['service_charges']);
        $this->assertSame(30.0, $eligible['final_payable_amount']);

        $notIncluded = $this->service()->priceDiagnosticPackage(
            'user-1',
            1000.0,
            (int) $otherPackage->id,
            (int) $hospital->id,
            'diagnostic'
        );

        $this->assertFalse($notIncluded['membership_benefit_applied']);
        $this->assertSame('package_not_in_plan_benefits', $notIncluded['rejection_reason']);
        $this->assertSame(1030.0, $notIncluded['final_payable_amount']);
    }

    public function test_diagnostic_package_rejects_disallowed_branch_and_exhausted_lab_allowance(): void
    {
        [$hospital, $subscription, $includedPackage] = $this->seedMembershipWithPackages();
        $otherBranch = $this->hospital('Other Branch', 99);

        $wrongBranch = $this->service()->priceDiagnosticPackage(
            'user-1',
            1000.0,
            (int) $includedPackage->id,
            (int) $otherBranch->id,
            'diagnostic'
        );
        $this->assertSame('branch_not_allowed', $wrongBranch['rejection_reason']);

        DB::table('diagnostic_test_bookings')->insert([
            'id' => 11,
            'package_id' => $includedPackage->id,
            'package_type' => 'diagnostic',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        SubscriptionUsageLog::create([
            'user_family_subscription_id' => $subscription->id,
            'hip_user_id' => 'user-1',
            'usage_type' => 'lab_test',
            'booking_id' => 11,
            'booking_type' => 'diagnostic_test_booking',
            'used_at' => now(),
            'notes' => 'membership_consumed',
        ]);

        $exhausted = $this->service()->priceDiagnosticPackage(
            'user-1',
            1000.0,
            (int) $includedPackage->id,
            (int) $hospital->id,
            'diagnostic'
        );

        $this->assertSame('lab_test_allowance_exhausted', $exhausted['rejection_reason']);
        $this->assertSame(1030.0, $exhausted['final_payable_amount']);
    }

    public function test_reserve_is_idempotent_and_release_only_clears_reserved_rows(): void
    {
        [, $subscription] = $this->seedMembership([
            'max_consultations' => 2,
            'branch_ids' => null,
            'benefits' => [],
        ]);

        $service = $this->service();
        $first = $service->reserveUsage((int) $subscription->id, 'consultation', 55, 'doctor_booking');
        $second = $service->reserveUsage((int) $subscription->id, 'consultation', 55, 'doctor_booking');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, SubscriptionUsageLog::query()->where('booking_id', 55)->count());

        $service->confirmUsage(55, 'doctor_booking', 'consultation');
        $this->assertSame(0, $service->releaseUsage(55, 'doctor_booking', 'consultation'));
        $this->assertSame(1, SubscriptionUsageLog::query()->where('booking_id', 55)->count());

        $service->reserveUsage((int) $subscription->id, 'consultation', 56, 'doctor_booking');
        $this->assertSame(1, $service->releaseUsage(56, 'doctor_booking', 'consultation'));
        $this->assertSame(0, SubscriptionUsageLog::query()->where('booking_id', 56)->count());
    }

    public function test_client_cannot_claim_fake_package_id_for_free_benefit(): void
    {
        [$hospital] = $this->seedMembershipWithPackages();

        $pricing = $this->service()->priceDiagnosticPackage(
            'user-1',
            1000.0,
            999999,
            (int) $hospital->id,
            'diagnostic'
        );

        $this->assertFalse($pricing['membership_benefit_applied']);
        $this->assertContains($pricing['rejection_reason'], [
            'package_not_available_at_branch',
            'package_not_in_plan_benefits',
        ]);
        $this->assertSame(1030.0, $pricing['final_payable_amount']);
    }

    private function service(): MembershipEntitlementService
    {
        return new MembershipEntitlementService(
            app(FamilyPackageService::class),
            app(InvoiceFeeCalculator::class),
        );
    }

    /**
     * @param  array{max_consultations?: int, branch_ids?: array<int>|null, benefits?: array<int, mixed>}  $overrides
     * @return array{0: Hospital, 1: UserFamilySubscription}
     */
    private function seedMembership(array $overrides): array
    {
        $hospital = $this->hospital('Nano', 10);

        $branchIds = array_key_exists('branch_ids', $overrides)
            ? $overrides['branch_ids']
            : [$hospital->id];

        $familyPackage = FamilyPackage::create([
            'name' => 'Membership',
            'price' => 999,
            'duration_days' => 365,
            'max_members' => 2,
            'max_consultations' => $overrides['max_consultations'] ?? 5,
            'max_lab_tests' => 0,
            'max_hip_coins' => 0,
            'branch_ids' => $branchIds,
            'benefits' => $overrides['benefits'] ?? [],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $subscription = UserFamilySubscription::create([
            'hip_user_id' => 'user-1',
            'family_package_id' => $familyPackage->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'active',
            'payment_status' => 'paid',
        ]);

        return [$hospital, $subscription];
    }

    /**
     * @return array{0: Hospital, 1: UserFamilySubscription, 2: DiagnosticPackage, 3: DiagnosticPackage}
     */
    private function seedMembershipWithPackages(): array
    {
        $hospital = $this->hospital('Nano', 10);

        $includedId = DB::table('diagnostic_packages')->insertGetId([
            'name' => 'Included Package',
            'diagnostic_id' => 10,
            'status' => 'active',
            'price' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherId = DB::table('diagnostic_packages')->insertGetId([
            'name' => 'Other Package',
            'diagnostic_id' => 10,
            'status' => 'active',
            'price' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $included = DiagnosticPackage::query()->findOrFail($includedId);
        $other = DiagnosticPackage::query()->findOrFail($otherId);

        [, $subscription] = $this->seedMembership([
            'max_consultations' => 5,
            'branch_ids' => [$hospital->id],
            'benefits' => [[
                'diagnostic_package_id' => $included->id,
                'benefit_type' => 'free',
                'max_lab_tests' => 1,
            ]],
        ]);

        return [$hospital, $subscription, $included, $other];
    }

    private function hospital(string $name, int $diagnosticCenterId): Hospital
    {
        $id = DB::table('hospitals')->insertGetId([
            'name' => $name,
            'status' => 'active',
            'diagnostic_center_id' => $diagnosticCenterId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Hospital::query()->findOrFail($id);
    }

    private function bootSchema(): void
    {
        if (Schema::hasTable('subscription_usage_logs')
            && Schema::hasTable('diagnostic_test_bookings')
            && Schema::hasTable('user_family_subscriptions')
            && Schema::hasTable('family_packages')
            && Schema::hasTable('diagnostic_packages')
            && Schema::hasTable('hospitals')) {
            return;
        }

        Schema::dropIfExists('subscription_usage_logs');
        Schema::dropIfExists('diagnostic_test_bookings');
        Schema::dropIfExists('user_family_subscriptions');
        Schema::dropIfExists('family_packages');
        Schema::dropIfExists('diagnostic_packages');
        Schema::dropIfExists('hospitals');

        Schema::create('hospitals', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('diagnostic_center_id')->nullable();
            $table->timestamps();
        });

        Schema::create('diagnostic_packages', function ($table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('diagnostic_id')->nullable();
            $table->string('status')->default('active');
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });

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
            $table->timestamps();
            $table->softDeletes();
        });

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

        Schema::create('diagnostic_test_bookings', function ($table) {
            $table->id();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('package_type')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }
}
