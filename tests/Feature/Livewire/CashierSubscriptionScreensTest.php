<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Cashier\Subscriptions\Create as CashierCreate;
use App\Livewire\Receptionist\Subscriptions\Create as ReceptionistCreate;
use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Models\UserFamilySubscription;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CashierSubscriptionScreensTest extends TestCase
{
    private HIPUser $user;
    private Persons $person;
    private FamilyPackage $packageA;
    private FamilyPackage $packageB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootSchema();

        $this->user = HIPUser::query()->create([
            'id' => (string) Str::uuid(),
            'hip_id' => 'HIP00021',
            'mobile_num' => '9876543210',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'is_active' => true,
        ]);

        $this->person = Persons::query()->create([
            'id' => (string) Str::uuid(),
            'hip_user_id' => $this->user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'mobile' => '9876543210',
            'is_primary' => true,
            'is_active' => true,
        ]);
        $this->person->update(['parent_id' => $this->person->id]);

        $this->packageA = FamilyPackage::query()->create([
            'name' => 'Gold Package',
            'price' => 1500,
            'duration_days' => 365,
            'max_members' => 3,
            'is_active' => true,
        ]);

        $this->packageB = FamilyPackage::query()->create([
            'name' => 'Platinum Package',
            'price' => 2500,
            'duration_days' => 365,
            'max_members' => 5,
            'is_active' => true,
        ]);
    }

    private function bootSchema(): void
    {
        foreach ([
            'reward_tier_transitions',
            'user_reward_progress',
            'reward_tier_configs',
            'reward_tiers',
            'reward_points_transitions',
            'subscription_usage_logs',
            'user_family_subscriptions',
            'user_rewards',
            'transactions',
            'invoices',
            'coins',
            'persons',
            'family_packages',
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
            $table->string('organization_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('profile_update')->nullable();
            $table->timestamps();
        });

        Schema::create('persons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->uuid('hip_user_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('mrn')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('relationship')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('family_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('duration_days')->default(365);
            $table->integer('max_members')->default(1);
            $table->integer('max_consultations')->default(0);
            $table->integer('max_lab_tests')->default(0);
            $table->integer('max_hip_coins')->default(0);
            $table->json('branch_ids')->nullable();
            $table->json('benefits')->nullable();
            $table->json('terms_conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
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
            $table->string('payment_mode')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->uuid('primary_person_id')->nullable();
            $table->uuid('person_id')->nullable();
            $table->json('service_types')->nullable();
            $table->json('invoice_details')->nullable();
            $table->string('prescription_img')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('total_gst', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('service_charges', 10, 2)->nullable();
            $table->decimal('payment_gateway_charges', 10, 2)->nullable();
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->integer('coins_applied')->nullable();
            $table->integer('coins_earned')->nullable();
            $table->string('payment_method')->nullable();
            $table->boolean('is_notified')->default(false);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->json('service_types')->nullable();
            $table->json('invoice_details')->nullable();
            $table->decimal('transaction_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('status')->default('completed');
            $table->string('payment_method')->default('cash');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('user_rewards', function (Blueprint $table): void {
            $table->id();
            $table->uuid('hip_user_id');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->boolean('is_applied')->default(false);
            $table->timestamps();
        });

        Schema::create('reward_points_transitions', function (Blueprint $table): void {
            $table->id();
            $table->string('person_id');
            $table->integer('previous_points')->default(0);
            $table->integer('current_points')->default(0);
            $table->integer('points_difference')->default(0);
            $table->string('change_type');
            $table->string('source')->nullable();
            $table->string('booking_source')->nullable();
            $table->string('booking_id')->nullable();
            $table->string('occurrence_id')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_bookings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('patient_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->date('booking_date')->nullable();
        });

        Schema::create('coins', function (Blueprint $table): void {
            $table->id();
            $table->string('person_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->integer('coins')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_family_subscription_id');
            $table->string('usage_type');
            $table->timestamps();
        });
    }

    public function test_cashier_screen_first_purchase_creates_subscription(): void
    {
        $comp = Livewire::test(CashierCreate::class)
            ->call('selectUser', $this->user->id)
            ->set('packageId', (int) $this->packageA->id)
            ->set('amount', '1500')
            ->set('selectedMemberIds', [(string) $this->person->id])
            ->set('paymentMode', 'cash')
            ->call('submit');

        $comp->assertHasNoErrors()
            ->assertRedirect(route('cashier.manage-subscriptions.index'));

        $this->assertDatabaseHas('user_family_subscriptions', [
            'hip_user_id' => $this->user->id,
            'family_package_id' => $this->packageA->id,
            'status' => 'active',
        ]);
    }

    public function test_cashier_screen_same_plan_renewal_extends_subscription(): void
    {
        $startDate = now()->subDays(30);
        $originalEndDate = $startDate->copy()->addDays(365);

        UserFamilySubscription::query()->create([
            'hip_user_id' => $this->user->id,
            'family_package_id' => $this->packageA->id,
            'covered_member_ids' => [(string) $this->person->id],
            'start_date' => $startDate->toDateString(),
            'end_date' => $originalEndDate->toDateString(),
            'status' => 'active',
            'payment_status' => 'paid',
            'amount_paid' => 1500,
        ]);

        Livewire::test(CashierCreate::class)
            ->call('selectUser', $this->user->id)
            ->set('packageId', (string) $this->packageA->id)
            ->set('amount', '1500')
            ->set('selectedMemberIds', [(string) $this->person->id])
            ->set('paymentMode', 'cash')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('cashier.manage-subscriptions.index'));

        $sub = UserFamilySubscription::query()->where('hip_user_id', $this->user->id)->first();
        $this->assertNotNull($sub);
        $expectedEndDate = $originalEndDate->copy()->addDays(365)->toDateString();
        $this->assertSame($expectedEndDate, $sub->end_date->toDateString());
        $this->assertEquals(3000, (float) $sub->amount_paid);
    }

    public function test_cashier_screen_different_plan_attempt_shows_validation_error_and_not_500(): void
    {
        UserFamilySubscription::query()->create([
            'hip_user_id' => $this->user->id,
            'family_package_id' => $this->packageA->id,
            'covered_member_ids' => [(string) $this->person->id],
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(365)->toDateString(),
            'status' => 'active',
            'payment_status' => 'paid',
            'amount_paid' => 1500,
        ]);

        Livewire::test(CashierCreate::class)
            ->call('selectUser', $this->user->id)
            ->set('packageId', (string) $this->packageB->id)
            ->set('amount', '2500')
            ->set('selectedMemberIds', [(string) $this->person->id])
            ->set('paymentMode', 'cash')
            ->call('submit')
            ->assertHasErrors(['packageId'])
            ->assertSee('User already has an active membership for a different plan.');

        // Verify only 1 subscription exists in database (still packageA)
        $this->assertSame(1, UserFamilySubscription::query()->count());
        $this->assertSame($this->packageA->id, UserFamilySubscription::query()->first()->family_package_id);
    }

    public function test_receptionist_screen_different_plan_attempt_shows_validation_error_and_not_500(): void
    {
        UserFamilySubscription::query()->create([
            'hip_user_id' => $this->user->id,
            'family_package_id' => $this->packageA->id,
            'covered_member_ids' => [(string) $this->person->id],
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(365)->toDateString(),
            'status' => 'active',
            'payment_status' => 'paid',
            'amount_paid' => 1500,
        ]);

        Livewire::test(ReceptionistCreate::class)
            ->call('selectUser', $this->user->id)
            ->set('packageId', (string) $this->packageB->id)
            ->set('amount', '2500')
            ->set('selectedMemberIds', [(string) $this->person->id])
            ->set('paymentMode', 'cash')
            ->call('submit')
            ->assertHasErrors(['packageId'])
            ->assertSee('User already has an active membership for a different plan.');
    }
}
