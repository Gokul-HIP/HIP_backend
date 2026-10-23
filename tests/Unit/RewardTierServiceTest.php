<?php

namespace Tests\Unit;

use App\Models\RewardTier;
use App\Models\RewardTierConfig;
use App\Models\RewardTierPackageDiscount;
use App\Models\UserRewardProgress;
use App\Modules\Automation\Events\RewardTierUpgraded;
use App\Services\InvoiceFeeCalculator;
use App\Services\MembershipEntitlementService;
use App\Services\RewardTierService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RewardTierServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Reward tier tests must use the sqlite testing database.');
        }

        Event::fake([RewardTierUpgraded::class]);

        config([
            'settings.fees.service_charges' => 0,
            'services.service_charges_percent' => 0,
        ]);

        $this->bootSchema();

        foreach ([
            'reward_tier_package_discounts',
            'user_reward_progress',
            'reward_tier_configs',
            'reward_tiers',
            'diagnostic_packages',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    public function test_tier_upgrade_uses_earned_coins_in_tier_and_resets_progress(): void
    {
        Event::fake([RewardTierUpgraded::class]);

        [$bronze, $silver] = $this->seedBronzeAndSilver();
        $service = app(RewardTierService::class);

        $service->enrollUserOnRegister('user-rt');
        $progress = UserRewardProgress::where('hip_user_id', 'user-rt')->firstOrFail();
        $this->assertSame($bronze->id, (int) $progress->reward_tier_id);

        // Silver threshold is 100 coins earned while on Bronze (not wallet balance).
        $service->addEarnedCoins('user-rt', 99);
        $progress->refresh();
        $this->assertSame($bronze->id, (int) $progress->reward_tier_id);
        $this->assertSame(99, (int) $progress->earned_coins_in_tier);

        $service->addEarnedCoins('user-rt', 1);
        $progress->refresh();
        $this->assertSame($silver->id, (int) $progress->reward_tier_id);
        $this->assertSame(0, (int) $progress->earned_coins_in_tier);
        $this->assertSame(100, (int) $progress->total_lifetime_coins);

        Event::assertDispatched(RewardTierUpgraded::class);
    }

    public function test_inactive_next_tier_is_skipped_for_progression_and_progress_payload(): void
    {
        [$bronze] = $this->seedBronzeAndSilver();
        RewardTier::where('name', 'Silver')->update(['is_active' => false]);

        $gold = RewardTier::create([
            'name' => 'Gold',
            'description' => null,
            'min_coins' => 200,
            'sort_order' => 3,
            'is_active' => true,
        ]);
        RewardTierConfig::create([
            'reward_tier_id' => $gold->id,
            'total_discount_percentage' => 10,
            'free_checkup_count' => 0,
            'earned_coins_per_booking' => 0,
        ]);

        $service = app(RewardTierService::class);
        $service->enrollUserOnRegister('user-inactive');
        $service->addEarnedCoins('user-inactive', 200);

        $progress = UserRewardProgress::where('hip_user_id', 'user-inactive')->firstOrFail();
        $this->assertSame($gold->id, (int) $progress->reward_tier_id);

        $payload = $service->getRewardProgressPayload('user-inactive');
        $this->assertSame('Gold', $payload['current_tier_name']);
        $this->assertNull($payload['next_tier_name']);
        $this->assertSame($bronze->name, 'Bronze');
    }

    public function test_total_discount_percentage_applies_to_doctor_base_not_service_charge(): void
    {
        $this->seedBronzeAndSilver(silverDiscountPercent: 5.0);
        app(RewardTierService::class)->enrollUserOnRegister('user-disc');
        // Put user on Silver
        app(RewardTierService::class)->addEarnedCoins('user-disc', 100);

        config([
            'settings.fees.service_charges' => 3,
            'services.service_charges_percent' => 3,
        ]);

        $pricing = $this->membershipService()->priceDoctorConsultation('user-disc', 1000.0);

        $this->assertFalse($pricing['membership_benefit_applied']);
        $this->assertSame(50.0, $pricing['reward_discount']);
        $this->assertSame('tier_percentage', $pricing['reward_discount_source']);
        $this->assertSame(950.0, $pricing['discounted_base_price']);
        // Service charge is 3% of discounted base (950), not of original.
        $this->assertSame(28.5, $pricing['service_charges']);
        $this->assertSame(978.5, $pricing['final_payable_amount']);
    }

    public function test_package_free_promotion_waives_base_keeps_service_charge_and_does_not_free_other_packages(): void
    {
        [, $silver] = $this->seedBronzeAndSilver(silverDiscountPercent: 5.0);
        $freePackageId = $this->diagnosticPackage('Respiratory Health Package');
        $otherPackageId = $this->diagnosticPackage('Other Package');

        RewardTierPackageDiscount::create([
            'reward_tier_id' => $silver->id,
            'diagnostic_package_id' => $freePackageId,
            'promotion_type' => 'free',
            'discount_type' => null,
            'discount_value' => null,
        ]);

        app(RewardTierService::class)->enrollUserOnRegister('user-pkg');
        app(RewardTierService::class)->addEarnedCoins('user-pkg', 100);

        config([
            'settings.fees.service_charges' => 3,
            'services.service_charges_percent' => 3,
        ]);

        $free = $this->membershipService()->priceDiagnosticPackage(
            'user-pkg',
            1000.0,
            $freePackageId,
            1,
            'diagnostic'
        );

        $this->assertSame(1000.0, $free['reward_discount']);
        $this->assertSame('package_promotion', $free['reward_discount_source']);
        $this->assertSame(0.0, $free['discounted_base_price']);
        // Fully waived base → service charge on original fee.
        $this->assertSame(30.0, $free['service_charges']);
        $this->assertSame(30.0, $free['final_payable_amount']);

        $other = $this->membershipService()->priceDiagnosticPackage(
            'user-pkg',
            1000.0,
            $otherPackageId,
            1,
            'diagnostic'
        );

        // Falls back to tier 5% — not free.
        $this->assertSame(50.0, $other['reward_discount']);
        $this->assertSame('tier_percentage', $other['reward_discount_source']);
        $this->assertSame(950.0, $other['discounted_base_price']);
        $this->assertSame(28.5, $other['service_charges']);
        $this->assertSame(978.5, $other['final_payable_amount']);
    }

    public function test_package_percentage_discount_promotion(): void
    {
        [, $silver] = $this->seedBronzeAndSilver(silverDiscountPercent: 0.0);
        $packageId = $this->diagnosticPackage('Promo Package');

        RewardTierPackageDiscount::create([
            'reward_tier_id' => $silver->id,
            'diagnostic_package_id' => $packageId,
            'promotion_type' => 'discount',
            'discount_type' => 'percentage',
            'discount_value' => 20,
        ]);

        app(RewardTierService::class)->enrollUserOnRegister('user-pct');
        app(RewardTierService::class)->addEarnedCoins('user-pct', 100);

        $pricing = $this->membershipService()->priceDiagnosticPackage(
            'user-pct',
            1000.0,
            $packageId,
            1,
            'diagnostic'
        );

        $this->assertSame(200.0, $pricing['reward_discount']);
        $this->assertSame(800.0, $pricing['discounted_base_price']);
        $this->assertSame(800.0, $pricing['final_payable_amount']); // 0% service charge in setUp default
    }

    public function test_membership_takes_priority_over_reward_discount_no_double_waive(): void
    {
        $this->seedBronzeAndSilver(silverDiscountPercent: 5.0);
        app(RewardTierService::class)->enrollUserOnRegister('user-stack');
        app(RewardTierService::class)->addEarnedCoins('user-stack', 100);

        $familyPackageId = DB::table('family_packages')->insertGetId([
            'name' => 'Mem',
            'price' => 100,
            'duration_days' => 30,
            'max_members' => 1,
            'max_consultations' => 5,
            'max_lab_tests' => 0,
            'max_hip_coins' => 0,
            'branch_ids' => null,
            'benefits' => json_encode([]),
            'is_active' => 1,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_family_subscriptions')->insert([
            'hip_user_id' => 'user-stack',
            'family_package_id' => $familyPackageId,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
            'payment_status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pricing = $this->membershipService()->priceDoctorConsultation('user-stack', 1000.0);

        $this->assertTrue($pricing['membership_benefit_applied']);
        $this->assertSame(1000.0, $pricing['membership_discount']);
        $this->assertSame(0.0, $pricing['reward_discount']);
        $this->assertSame(0.0, $pricing['discounted_base_price']);
        $this->assertSame(0.0, $pricing['final_payable_amount']);
    }

    public function test_earned_coins_per_booking_bonus_reads_active_tier_config(): void
    {
        $this->seedBronzeAndSilver(silverBonus: 2);
        $service = app(RewardTierService::class);
        $service->enrollUserOnRegister('user-bonus');

        $this->assertSame(0, $service->earnedCoinsPerBookingBonus('user-bonus'));

        $service->addEarnedCoins('user-bonus', 100);
        $this->assertSame(2, $service->earnedCoinsPerBookingBonus('user-bonus'));
    }

    public function test_reward_progress_payload_exposes_benefits_including_free_checkup_display_only(): void
    {
        [, $silver] = $this->seedBronzeAndSilver(
            silverDiscountPercent: 5.0,
            silverBonus: 2,
            silverFreeCheckups: 5
        );
        $packageId = $this->diagnosticPackage('Respiratory Health Package');
        RewardTierPackageDiscount::create([
            'reward_tier_id' => $silver->id,
            'diagnostic_package_id' => $packageId,
            'promotion_type' => 'free',
            'discount_type' => null,
            'discount_value' => null,
        ]);

        $service = app(RewardTierService::class);
        $service->enrollUserOnRegister('user-api');
        $service->addEarnedCoins('user-api', 100);

        $payload = $service->getRewardProgressPayload('user-api');

        $this->assertSame('Silver', $payload['current_tier_name']);
        $this->assertSame(5.0, $payload['total_discount_percentage']);
        $this->assertSame(5, $payload['free_checkup_count']);
        $this->assertSame(2, $payload['earned_coins_per_booking']);
        $this->assertCount(1, $payload['package_discounts']);
        $this->assertSame('free', $payload['package_discounts'][0]['promotion_type']);
    }

    public function test_add_earned_coins_ignores_zero_and_negative(): void
    {
        $this->seedBronzeAndSilver();
        $service = app(RewardTierService::class);
        $service->enrollUserOnRegister('user-zero');

        $service->addEarnedCoins('user-zero', 0);
        $service->addEarnedCoins('user-zero', -5);

        $progress = UserRewardProgress::where('hip_user_id', 'user-zero')->firstOrFail();
        $this->assertSame(0, (int) $progress->earned_coins_in_tier);
        $this->assertSame(0, (int) $progress->total_lifetime_coins);
    }

    /**
     * @return array{0: RewardTier, 1: RewardTier}
     */
    private function seedBronzeAndSilver(
        float $silverDiscountPercent = 0.0,
        int $silverBonus = 0,
        int $silverFreeCheckups = 0
    ): array {
        $bronze = RewardTier::create([
            'name' => 'Bronze',
            'description' => null,
            'min_coins' => 0,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        RewardTierConfig::create([
            'reward_tier_id' => $bronze->id,
            'total_discount_percentage' => 0,
            'free_checkup_count' => 0,
            'earned_coins_per_booking' => 0,
        ]);

        $silver = RewardTier::create([
            'name' => 'Silver',
            'description' => null,
            'min_coins' => 100,
            'sort_order' => 2,
            'is_active' => true,
        ]);
        RewardTierConfig::create([
            'reward_tier_id' => $silver->id,
            'total_discount_percentage' => $silverDiscountPercent,
            'free_checkup_count' => $silverFreeCheckups,
            'earned_coins_per_booking' => $silverBonus,
        ]);

        return [$bronze, $silver];
    }

    private function diagnosticPackage(string $name): int
    {
        return (int) DB::table('diagnostic_packages')->insertGetId([
            'name' => $name,
            'status' => 'active',
            'price' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function membershipService(): MembershipEntitlementService
    {
        return new MembershipEntitlementService(
            app(\App\Services\FamilyPackageService::class),
            app(InvoiceFeeCalculator::class),
            app(RewardTierService::class),
        );
    }

    private function bootSchema(): void
    {
        if (Schema::hasTable('reward_tiers')
            && Schema::hasTable('reward_tier_configs')
            && Schema::hasTable('reward_tier_package_discounts')
            && Schema::hasTable('user_reward_progress')
            && Schema::hasTable('diagnostic_packages')
            && Schema::hasTable('family_packages')
            && Schema::hasTable('user_family_subscriptions')
            && Schema::hasTable('subscription_usage_logs')) {
            return;
        }

        Schema::dropIfExists('subscription_usage_logs');
        Schema::dropIfExists('reward_tier_package_discounts');
        Schema::dropIfExists('user_reward_progress');
        Schema::dropIfExists('reward_tier_configs');
        Schema::dropIfExists('reward_tiers');
        Schema::dropIfExists('user_family_subscriptions');
        Schema::dropIfExists('family_packages');
        Schema::dropIfExists('diagnostic_packages');

        Schema::create('diagnostic_packages', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('reward_tiers', function ($table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('min_coins')->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
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
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('user_reward_progress', function ($table) {
            $table->id();
            $table->string('hip_user_id')->unique();
            $table->unsignedBigInteger('reward_tier_id')->nullable();
            $table->unsignedInteger('earned_coins_in_tier')->default(0);
            $table->unsignedInteger('total_lifetime_coins')->default(0);
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
    }
}
