<?php

namespace Tests\Feature;

use App\Livewire\Admin\MemberProfile\ViewMemberProfile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPatientTransactionsBreakdownTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
    }

    public function test_transactions_tab_renders_payment_breakdown_and_source_badges(): void
    {
        DB::table('healthinpocket_users')->insert([
            'id' => 'patient-uuid', 'first_name' => 'Mira', 'last_name' => 'Shah',
            'email' => 'mira@example.test', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('persons')->insert([
            'id' => 1, 'hip_user_id' => 'patient-uuid', 'is_primary' => 1, 'parent_id' => 1,
            'first_name' => 'Mira', 'last_name' => 'Shah', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('doctor_bookings')->insert([
            'id' => 41, 'patient_id' => 1, 'consultation_fee' => 100, 'total_discount' => 10,
            'coins_used' => 0, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('invoices')->insert([
            'id' => 51, 'doctor_booking_id' => 41, 'primary_person_id' => 1, 'person_id' => 1,
            'service_types' => '["doctor_consultation"]', 'amount' => 100, 'discount_price' => 10,
            'coins_applied' => 0, 'service_charges' => 5, 'total_gst' => 0, 'payment_gateway_charges' => 0,
            'total_amount' => 95, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('transactions')->insert([
            'id' => 61, 'invoice_id' => 51, 'transaction_amount' => 95, 'discount_amount' => 0,
            'service_charges' => 5, 'total_gst' => 0, 'payment_gateway_charges' => 0, 'total_amount' => 95,
            'status' => 'completed', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('invoices')->insert([
            'id' => 52, 'pharmacy_order_id' => 87, 'primary_person_id' => 1, 'person_id' => 1,
            'service_types' => '["pharmacy"]', 'amount' => 45, 'total_amount' => 45,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('transactions')->insert([
            'id' => 62, 'invoice_id' => 52, 'transaction_amount' => 45, 'discount_amount' => 0,
            'service_charges' => 0, 'total_gst' => 0, 'payment_gateway_charges' => 0, 'total_amount' => 45,
            'status' => 'completed', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('reward_tiers')->insert(['id' => 2, 'name' => 'Silver']);
        DB::table('reward_tier_configs')->insert(['reward_tier_id' => 2, 'total_discount_percentage' => 5]);
        DB::table('reward_tier_discount_usages')->insert([
            'hip_user_id' => 'patient-uuid', 'reward_tier_id' => 2, 'tier_generation' => 1,
            'booking_type' => 'doctor_booking', 'booking_id' => 41, 'status' => 'counted',
        ]);

        Livewire::test(ViewMemberProfile::class, ['user' => 'patient-uuid'])
            ->set('tab', 'transactions')
            ->assertSee('Doctor Consultation')
            ->assertSee('Reward tier')
            ->assertSee('Silver')
            ->assertSee('Doctor consultation')
            ->assertSee('#41')
            ->assertSee('Pharmacy')
            ->assertSee('#87')
            ->assertSee('colspan="6"', false)
            ->assertSee('aria-controls="sp-tx-detail-panel-61"', false)
            ->assertSee('x-bind:aria-expanded=', false)
            ->assertSee('Original amount')
            ->assertSee('Reward discount')
            ->assertSee('Total paid')
            ->assertSee('Rs 95.00');
    }

    public function test_coin_card_totals_recorded_booking_and_bill_analysis_earnings_and_zero_is_numeric(): void
    {
        $this->createPatient('earned-user');
        DB::table('invoices')->insert([
            'id' => 71, 'doctor_booking_id' => 41, 'primary_person_id' => 1, 'person_id' => 1,
            'status' => 'completed', 'coins_earned' => 120, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('reward_points_transitions')->insert([
            'hip_user_id' => 'earned-user', 'previous_balance' => 0, 'current_balance' => 40,
            'points_changed' => 40, 'change_type' => 'earned', 'reward_source' => 'bill_analysis',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Livewire::test(ViewMemberProfile::class, ['user' => 'earned-user'])
            ->set('tab', 'transactions')
            ->assertSee('Coins earned')
            ->assertSee('160')
            ->assertSee('Bookings 120')
            ->assertSee('Bill analysis 40')
            ->assertSee('Older non-invoice wallet earnings may not be fully recorded');

        $this->createPatient('content-earned-user', 2);
        DB::table('reward_points_transitions')->insert([
            'hip_user_id' => 'content-earned-user', 'previous_balance' => 0, 'current_balance' => 55,
            'points_changed' => 55, 'change_type' => 'earned', 'reward_source' => 'content',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Livewire::test(ViewMemberProfile::class, ['user' => 'content-earned-user'])
            ->set('tab', 'transactions')
            ->assertSee('Coins earned')
            ->assertSee('55')
            ->assertSee('Content 55')
            ->assertDontSee('Bookings 0');

        $this->createPatient('zero-earned-user', 3);
        Livewire::test(ViewMemberProfile::class, ['user' => 'zero-earned-user'])
            ->set('tab', 'transactions')
            ->assertSee('Coins earned')
            ->assertSee('<p class="sp-tile-value">0</p>', false)
            ->assertSee('No recorded coin earnings')
            ->assertDontSee('Earned coins are not recorded');
    }

    public function test_reward_tier_card_renders_progress_and_no_progress_page_view_does_not_enroll_user(): void
    {
        $this->createPatient('tier-user');
        DB::table('reward_tiers')->insert([
            ['id' => 1, 'name' => 'Bronze', 'min_coins' => 0, 'sort_order' => 1, 'is_active' => 1],
            ['id' => 2, 'name' => 'Silver ', 'min_coins' => 100, 'sort_order' => 2, 'is_active' => 1],
            ['id' => 5, 'name' => 'Gold', 'min_coins' => 500, 'sort_order' => 3, 'is_active' => 1],
        ]);
        DB::table('reward_tier_configs')->insert([
            ['reward_tier_id' => 1, 'total_discount_percentage' => 0, 'free_checkup_count' => 0],
            ['reward_tier_id' => 2, 'total_discount_percentage' => 5, 'free_checkup_count' => 3],
        ]);
        DB::table('user_reward_progress')->insert([
            'hip_user_id' => 'tier-user', 'reward_tier_id' => 2, 'tier_generation' => 4,
            'earned_coins_in_tier' => 60, 'total_lifetime_coins' => 460,
        ]);
        DB::table('reward_tier_discount_usages')->insert([
            'hip_user_id' => 'tier-user', 'reward_tier_id' => 2, 'tier_generation' => 4,
            'booking_type' => 'doctor_booking', 'booking_id' => 99, 'status' => 'consumed',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $tierView = Livewire::test(ViewMemberProfile::class, ['user' => 'tier-user'])
            ->set('tab', 'transactions')
            ->assertSee('Silver')
            ->assertSee('5%')
            ->assertSee('discount')
            ->assertSee('60 / 500 coins - 440 coins to Gold')
            ->assertSee('460 lifetime coins')
            ->assertSee('1 of 3')
            ->assertSee('aria-label="Reward tier progress to Gold"', false)
            ->assertSee('aria-valuenow="12"', false);
        $tierView->set('tab', 'overview')->assertSee('<span class="sp-pill sp-tier-header-badge">Silver</span>', false);

        $this->createPatient('without-tier-progress', 3);
        Livewire::test(ViewMemberProfile::class, ['user' => 'without-tier-progress'])
            ->set('tab', 'transactions')
            ->assertSee('No reward tier yet')
            ->assertDontSee('role="progressbar"', false)
            ->assertSee('.sp-profile-detail-card:only-child{grid-column:1/-1}', false);
        $this->assertDatabaseMissing('user_reward_progress', ['hip_user_id' => 'without-tier-progress']);
    }

    public function test_reward_tier_card_shows_highest_tier_unlimited_and_exhausted_limits(): void
    {
        $this->createPatient('top-tier-user');
        DB::table('reward_tiers')->insert(['id' => 3, 'name' => 'Gold', 'min_coins' => 500, 'sort_order' => 3, 'is_active' => 1]);
        DB::table('reward_tier_configs')->insert(['reward_tier_id' => 3, 'total_discount_percentage' => 10, 'free_checkup_count' => 0]);
        DB::table('user_reward_progress')->insert([
            'hip_user_id' => 'top-tier-user', 'reward_tier_id' => 3, 'tier_generation' => 1,
            'earned_coins_in_tier' => 10, 'total_lifetime_coins' => 510,
        ]);
        Livewire::test(ViewMemberProfile::class, ['user' => 'top-tier-user'])
            ->set('tab', 'transactions')
            ->assertSee('Gold')
            ->assertSee('Highest tier reached')
            ->assertSee('aria-valuenow="100"', false)
            ->assertSee('sp-reward-progress-fill is-complete', false)
            ->assertSee('0 of Unlimited')
            ->assertSee('Remaining:')
            ->assertSee('Unlimited');

        $this->createPatient('exhausted-tier-user', 4);
        DB::table('reward_tiers')->insert(['id' => 4, 'name' => 'Silver', 'min_coins' => 100, 'sort_order' => 2, 'is_active' => 1]);
        DB::table('reward_tier_configs')->insert(['reward_tier_id' => 4, 'total_discount_percentage' => 5, 'free_checkup_count' => 2]);
        DB::table('user_reward_progress')->insert([
            'hip_user_id' => 'exhausted-tier-user', 'reward_tier_id' => 4, 'tier_generation' => 2,
            'earned_coins_in_tier' => 0, 'total_lifetime_coins' => 100,
        ]);
        foreach ([81, 82] as $bookingId) {
            DB::table('reward_tier_discount_usages')->insert([
                'hip_user_id' => 'exhausted-tier-user', 'reward_tier_id' => 4, 'tier_generation' => 2,
                'booking_type' => 'doctor_booking', 'booking_id' => $bookingId, 'status' => 'consumed',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        Livewire::test(ViewMemberProfile::class, ['user' => 'exhausted-tier-user'])
            ->set('tab', 'transactions')
            ->assertSee('2 of 2')
            ->assertSee('Remaining:')
            ->assertSee('0');
    }

    public function test_membership_card_shows_active_usage_and_only_expired_membership_line(): void
    {
        $this->createPatient('member-user');
        DB::table('hospitals')->insert(['id' => 1, 'name' => 'Central Hospital', 'diagnostic_center_id' => 7]);
        $this->createMembership('member-user', 1, 'Active Family Plan', now()->subDays(10)->toDateString(), now()->addDays(20)->toDateString());
        DB::table('subscription_usage_logs')->insert([
            ['user_family_subscription_id' => 1, 'hip_user_id' => 'member-user', 'usage_type' => 'consultation', 'booking_id' => 21, 'booking_type' => 'doctor_booking', 'used_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['user_family_subscription_id' => 1, 'hip_user_id' => 'member-user', 'usage_type' => 'lab_test', 'booking_id' => 88, 'booking_type' => 'diagnostic_test_booking', 'used_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('diagnostic_test_bookings')->insert([
            'id' => 88, 'patient_id' => 1, 'package_id' => 71, 'package_type' => 'diagnostic',
            'status' => 'completed', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('diagnostic_packages')->insert(['id' => 71, 'name' => 'Annual Blood Panel', 'diagnostic_id' => 7, 'status' => 'active']);

        $memberView = Livewire::test(ViewMemberProfile::class, ['user' => 'member-user'])
            ->set('tab', 'transactions')
            ->assertSee('Active Family Plan')
            ->assertSee('Member')
            ->assertSee('1 used of 2')
            ->assertSee('1 used of 3 lab tests')
            ->assertSee('Annual Blood Panel')
            ->assertSee('Central Hospital');
        $memberView->set('tab', 'overview')->assertSee('<span class="sp-pill sp-member-header-badge">Member</span>', false);

        $this->createPatient('expired-member-user', 5);
        $this->createMembership('expired-member-user', 2, 'Expired Plan', now()->subDays(40)->toDateString(), now()->subDays(10)->toDateString(), 'expired');
        Livewire::test(ViewMemberProfile::class, ['user' => 'expired-member-user'])
            ->set('tab', 'transactions')
            ->assertSee('Membership expired on');

        $this->createPatient('no-membership-user', 6);
        Livewire::test(ViewMemberProfile::class, ['user' => 'no-membership-user'])
            ->set('tab', 'transactions')
            ->assertDontSee('Membership expired on')
            ->assertDontSee('Member');

        $this->createPatient('pending-membership-user', 7);
        $this->createMembership('pending-membership-user', 3, 'Unpaid Plan', now()->subDays(40)->toDateString(), now()->subDays(10)->toDateString(), 'pending');
        Livewire::test(ViewMemberProfile::class, ['user' => 'pending-membership-user'])
            ->set('tab', 'transactions')
            ->assertDontSee('Membership expired on');
    }

    private function createPatient(string $userId, int $personId = 1): void
    {
        if (! DB::table('healthinpocket_users')->where('id', $userId)->exists()) {
            DB::table('healthinpocket_users')->insert([
                'id' => $userId, 'first_name' => 'Test', 'last_name' => 'Patient',
                'email' => $userId.'@example.test', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        if (! DB::table('persons')->where('id', $personId)->exists()) {
            DB::table('persons')->insert([
                'id' => $personId, 'hip_user_id' => $userId, 'is_primary' => 1, 'parent_id' => $personId,
                'first_name' => 'Test', 'last_name' => 'Patient', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function createMembership(string $userId, int $id, string $name, string $start, string $end, string $status = 'active'): void
    {
        DB::table('family_packages')->insert([
            'id' => $id, 'name' => $name, 'price' => 100, 'duration_days' => 30,
            'max_members' => 1, 'max_consultations' => 2, 'max_lab_tests' => 0, 'max_hip_coins' => 0,
            'branch_ids' => json_encode([1]),
            'benefits' => json_encode([['diagnostic_package_id' => 71, 'benefit_type' => 'free', 'max_lab_tests' => 3]]),
            'is_active' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('user_family_subscriptions')->insert([
            'id' => $id, 'hip_user_id' => $userId, 'family_package_id' => $id,
            'start_date' => $start, 'end_date' => $end, 'status' => $status, 'payment_status' => 'paid',
            'amount_paid' => 100, 'auto_renew' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function createTables(): void
    {
        Schema::create('healthinpocket_users', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->date('dob')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('persons', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('hip_user_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('parent_id')->nullable();
            $table->unsignedInteger('hospital_id')->nullable();
            $table->string('created_by')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('dob')->nullable();
            $table->timestamps();
        });
        Schema::create('hospitals', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->unsignedInteger('diagnostic_center_id')->nullable();
        });
        Schema::create('h_i_p_cards', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('patient_id')->nullable();
            $table->string('hip_card_id')->nullable();
        });
        Schema::create('coins', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('person_id')->nullable();
            $table->integer('coins')->default(0);
        });
        foreach (['doctor_bookings', 'second_opinions', 'diagnostic_test_bookings'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('patient_id')->nullable();
                $table->string('status')->nullable();
                $table->decimal('coins_used', 10, 2)->default(0);
                $table->timestamps();
            });
        }
        Schema::table('doctor_bookings', function (Blueprint $table): void {
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->decimal('total_discount', 10, 2)->default(0);
        });
        Schema::table('diagnostic_test_bookings', function (Blueprint $table): void {
            $table->unsignedInteger('package_id')->nullable();
            $table->string('package_type')->nullable();
        });
        Schema::create('invoices', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('doctor_booking_id')->nullable();
            $table->unsignedInteger('second_opinion_id')->nullable();
            $table->unsignedInteger('diagnostic_test_booking_id')->nullable();
            $table->unsignedInteger('pharmacy_order_id')->nullable();
            $table->unsignedInteger('primary_person_id')->nullable();
            $table->unsignedInteger('person_id')->nullable();
            $table->string('status')->default('completed');
            $table->text('service_types')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('discount_price', 12, 2)->default(0);
            $table->decimal('coins_applied', 12, 2)->default(0);
            $table->integer('coins_earned')->nullable();
            $table->decimal('service_charges', 12, 2)->default(0);
            $table->decimal('total_gst', 12, 2)->default(0);
            $table->decimal('payment_gateway_charges', 12, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('invoice_id')->nullable();
            $table->decimal('transaction_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('service_charges', 12, 2)->default(0);
            $table->decimal('total_gst', 12, 2)->default(0);
            $table->decimal('payment_gateway_charges', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('reward_tiers', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->unsignedInteger('min_coins')->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
        Schema::create('reward_tier_configs', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('reward_tier_id');
            $table->decimal('total_discount_percentage', 8, 2)->nullable();
            $table->unsignedInteger('free_checkup_count')->default(0);
            $table->unsignedInteger('earned_coins_per_booking')->default(0);
        });
        Schema::create('user_reward_progress', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('hip_user_id')->unique();
            $table->unsignedInteger('reward_tier_id');
            $table->unsignedInteger('tier_generation')->default(0);
            $table->unsignedInteger('earned_coins_in_tier')->default(0);
            $table->unsignedInteger('total_lifetime_coins')->default(0);
            $table->timestamps();
        });
        Schema::create('reward_tier_package_discounts', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('reward_tier_id');
            $table->unsignedInteger('diagnostic_package_id');
            $table->string('promotion_type')->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('diagnostic_packages', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->unsignedInteger('diagnostic_id')->nullable();
            $table->string('status')->nullable();
        });
        Schema::create('reward_tier_discount_usages', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('hip_user_id');
            $table->unsignedInteger('reward_tier_id');
            $table->unsignedInteger('tier_generation')->default(1);
            $table->string('booking_type');
            $table->unsignedInteger('booking_id');
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('family_packages', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
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
        Schema::create('user_family_subscriptions', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('hip_user_id');
            $table->unsignedInteger('family_package_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active');
            $table->string('payment_status')->default('paid');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->boolean('auto_renew')->default(false);
            $table->timestamps();
        });
        Schema::create('subscription_usage_logs', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_family_subscription_id');
            $table->string('hip_user_id');
            $table->string('usage_type');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('booking_type')->nullable();
            $table->timestamp('used_at');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('reward_points_transitions', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('hip_user_id');
            $table->integer('previous_balance');
            $table->integer('current_balance');
            $table->integer('points_changed');
            $table->integer('coins_earned')->nullable();
            $table->string('change_type');
            $table->string('reward_source');
            $table->unsignedInteger('invoice_id')->nullable();
            $table->unsignedInteger('transaction_id')->nullable();
            $table->timestamps();
        });
    }
}
