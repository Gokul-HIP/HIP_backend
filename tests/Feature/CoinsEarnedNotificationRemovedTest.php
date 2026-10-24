<?php

namespace Tests\Feature;

use App\Models\Coins;
use App\Models\HIPUser;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\RewardTier;
use App\Models\RewardTierConfig;
use App\Models\UserRewardProgress;
use App\Models\RewardPointsTransition;
use App\Modules\Automation\Events\RewardTierUpgraded;
use App\Services\Api\PaymentApiService;
use App\Services\DoctorNotificationService;
use App\Services\NotificationService;
use App\Services\RewardTierService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoinsEarnedNotificationRemovedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Coins earned notification tests must use the sqlite testing database.');
        }

        $this->bootSchema();
    }

    public function test_payment_still_awards_coins_and_upgrades_tier_without_the_coins_earned_push(): void
    {
        Event::fake([RewardTierUpgraded::class]);

        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifyUser')->never();
        $notifications->shouldReceive('sendToDevice')->never();
        $this->app->instance(NotificationService::class, $notifications);

        $this->mock(DoctorNotificationService::class, function ($mock): void {
            $mock->shouldReceive('notifyDoctorOfNewBooking')->once()->andReturn(null);
        });

        $user = HIPUser::withoutEvents(fn () => HIPUser::query()->create([
            'email' => 'coins-removed@example.com',
            'first_name' => 'Coins',
            'last_name' => 'Patient',
            'hip_id' => 'HIP-COINS',
            'is_active' => true,
            'password' => null,
            'coins_balance' => 0,
        ]));

        $person = Persons::withoutEvents(fn () => Persons::query()->create([
            'first_name' => 'Coins',
            'last_name' => 'Patient',
            'hip_user_id' => $user->id,
            'is_primary' => true,
        ]));

        $bronze = RewardTier::query()->create([
            'name' => 'Bronze',
            'min_coins' => 0,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        RewardTierConfig::query()->create([
            'reward_tier_id' => $bronze->id,
            'total_discount_percentage' => 0,
            'free_checkup_count' => 0,
            'earned_coins_per_booking' => 0,
        ]);
        $silver = RewardTier::query()->create([
            'name' => 'Silver',
            'min_coins' => 5,
            'sort_order' => 2,
            'is_active' => true,
        ]);
        RewardTierConfig::query()->create([
            'reward_tier_id' => $silver->id,
            'total_discount_percentage' => 0,
            'free_checkup_count' => 0,
            'earned_coins_per_booking' => 0,
        ]);
        app(RewardTierService::class)->enrollUserOnRegister((string) $user->id);

        DB::table('doctor_bookings')->insert([
            'id' => 77,
            'member_id' => $user->id,
            'patient_id' => $person->id,
            'hospital_id' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = new Invoice();
        $invoice->stopUserstamping();
        $invoice->forceFill([
            'primary_person_id' => $person->id,
            'person_id' => $person->id,
            'doctor_booking_id' => 77,
            'service_types' => ['doctor_consultation'],
            'status' => 'pending',
            'amount' => 500,
            'total_amount' => 500,
            'coins_applied' => 0,
            'coins_earned' => 0,
            'service_charges' => 0,
            'payment_gateway_charges' => 0,
            'total_gst' => 0,
            'discount_price' => 0,
        ])->save();

        $result = app(PaymentApiService::class)->completeInvoicePayment([
            'invoice_id' => $invoice->id,
            'payment_method' => 'cash',
            'total_amount' => 500,
            'original_amount' => 500,
            'status' => 'completed',
            'primary_person_id' => $person->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $result['status']);
        $this->assertSame(5, $result['coins_earned']);
        $this->assertSame(5, (int) $invoice->fresh()->coins_earned);
        $this->assertSame('completed', $invoice->fresh()->status);
        $this->assertSame(5, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertSame(5, (int) $user->fresh()->coins_balance);

        $progress = UserRewardProgress::query()->where('hip_user_id', $user->id)->firstOrFail();
        $this->assertSame($silver->id, (int) $progress->reward_tier_id);

        Event::assertDispatched(RewardTierUpgraded::class, function (RewardTierUpgraded $event) use ($user): bool {
            $payload = $event->payload();

            return $payload['member_id'] === (string) $user->id
                && data_get($payload, 'rewards.current_tier.name') === 'Silver';
        });

        $transition = RewardPointsTransition::query()->where('hip_user_id', (string) $user->id)->first();
        $this->assertNotNull($transition);
        $this->assertSame(5, (int) $transition->points_changed);
        $this->assertSame('earned', $transition->change_type);
        $this->assertSame('appointment_payment', $transition->reward_source);
        $this->assertStringStartsWith('reward-points-updated:transition:', (string) $transition->occurrence_id);
    }

    private function bootSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('healthinpocket_users', function ($table) {
            $table->uuid('id')->primary();
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('password')->nullable();
            $table->string('hip_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->integer('coins_balance')->default(0);
            $table->timestamps();
        });

        Schema::create('persons', function ($table) {
            $table->uuid('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->uuid('hip_user_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->boolean('is_primary')->default(false);
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

        Schema::create('invoices', function ($table) {
            $table->id();
            $table->uuid('primary_person_id')->nullable();
            $table->uuid('person_id')->nullable();
            $table->unsignedBigInteger('doctor_booking_id')->nullable();
            $table->json('service_types')->nullable();
            $table->json('invoice_details')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('service_charges', 10, 2)->default(0);
            $table->decimal('payment_gateway_charges', 10, 2)->default(0);
            $table->decimal('total_gst', 10, 2)->default(0);
            $table->decimal('discount_price', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->integer('coins_applied')->default(0);
            $table->integer('coins_earned')->default(0);
            $table->string('payment_method')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function ($table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->json('service_types')->nullable();
            $table->json('invoice_details')->nullable();
            $table->decimal('transaction_amount', 10, 2)->default(0);
            $table->decimal('service_charges', 10, 2)->default(0);
            $table->decimal('payment_gateway_charges', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_gst', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('status')->nullable();
            $table->string('payment_method')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_bookings', function ($table) {
            $table->id();
            $table->string('member_id')->nullable();
            $table->string('patient_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('status')->nullable();
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

        Schema::create('user_reward_progress', function ($table) {
            $table->id();
            $table->string('hip_user_id')->unique();
            $table->unsignedBigInteger('reward_tier_id')->nullable();
            $table->unsignedInteger('earned_coins_in_tier')->default(0);
            $table->unsignedInteger('total_lifetime_coins')->default(0);
            $table->timestamps();
        });

        Schema::create('reward_tier_transitions', function ($table) {
            $table->id();
            $table->string('hip_user_id')->index();
            $table->unsignedBigInteger('previous_tier_id')->nullable();
            $table->unsignedBigInteger('new_tier_id');
            $table->string('occurrence_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('reward_points_transitions', function ($table) {
            $table->id();
            $table->string('hip_user_id')->index();
            $table->integer('previous_balance');
            $table->integer('current_balance');
            $table->integer('points_changed');
            $table->integer('coins_earned')->nullable();
            $table->string('change_type');
            $table->string('reward_source');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('booking_source')->nullable();
            $table->string('booking_id')->nullable();
            $table->string('occurrence_id')->nullable()->unique();
            $table->timestamps();
        });
    }
}
