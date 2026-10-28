<?php

namespace Tests\Feature;

use App\Models\HIPUser;
use App\Modules\Automation\Events\MembershipRenewed;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FamilyPackageSubscribeSecurityTest extends TestCase
{
    private const REJECTION = [
        'status' => false,
        'message' => 'A verified paid invoice is required to activate a membership.',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureAccountIsActive::class);
        Event::fake([MembershipRenewed::class]);
        $this->createTables();
        config(['memberships.allow_unverified_self_subscribe' => false]);
    }

    public function test_subscribe_rejects_a_missing_invoice(): void
    {
        $user = $this->createUserWithPerson();
        $packageId = $this->createPackage();

        $this->subscribe($user, $packageId, ['payment_status' => 'paid', 'amount_paid' => 1000])
            ->assertForbidden()
            ->assertExactJson(self::REJECTION);

        $this->assertDatabaseCount('user_family_subscriptions', 0);
    }

    public function test_subscribe_rejects_an_invoice_owned_by_another_user(): void
    {
        $user = $this->createUserWithPerson();
        $otherUser = $this->createUserWithPerson();
        $packageId = $this->createPackage();
        $invoiceId = $this->createInvoice($otherUser, $packageId);

        $this->subscribe($user, $packageId, ['invoice_id' => $invoiceId])
            ->assertForbidden()
            ->assertExactJson(self::REJECTION);
    }

    public function test_subscribe_rejects_an_invoice_for_a_different_package(): void
    {
        $user = $this->createUserWithPerson();
        $packageId = $this->createPackage();
        $otherPackageId = $this->createPackage('Another plan');
        $invoiceId = $this->createInvoice($user, $otherPackageId);

        $this->subscribe($user, $packageId, ['invoice_id' => $invoiceId])
            ->assertForbidden()
            ->assertExactJson(self::REJECTION);
    }

    public function test_subscribe_rejects_a_pending_invoice(): void
    {
        $user = $this->createUserWithPerson();
        $packageId = $this->createPackage();
        $invoiceId = $this->createInvoice($user, $packageId, 'pending');

        $this->subscribe($user, $packageId, ['invoice_id' => $invoiceId])
            ->assertForbidden()
            ->assertExactJson(self::REJECTION);
    }

    public function test_paid_matching_invoice_activates_and_uses_its_amount(): void
    {
        $user = $this->createUserWithPerson();
        $packageId = $this->createPackage();
        $invoiceId = $this->createInvoice($user, $packageId, 'completed', 735.25);

        $this->subscribe($user, $packageId, ['invoice_id' => $invoiceId])
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.amount_paid', 735.25)
            ->assertJsonPath('data.package_id', $packageId);

        $this->assertDatabaseHas('user_family_subscriptions', [
            'hip_user_id' => $user->id,
            'family_package_id' => $packageId,
            'invoice_id' => $invoiceId,
            'status' => 'active',
            'payment_status' => 'paid',
            'amount_paid' => 735.25,
        ]);
    }

    public function test_subscribe_rejects_an_invoice_already_linked_to_a_subscription(): void
    {
        $user = $this->createUserWithPerson();
        $packageId = $this->createPackage();
        $invoiceId = $this->createInvoice($user, $packageId);

        $this->subscribe($user, $packageId, ['invoice_id' => $invoiceId])->assertOk();
        DB::table('user_family_subscriptions')->where('invoice_id', $invoiceId)->update([
            'status' => 'cancelled',
        ]);

        $this->subscribe($user, $packageId, ['invoice_id' => $invoiceId])
            ->assertForbidden()
            ->assertExactJson(self::REJECTION);

        $this->assertDatabaseCount('user_family_subscriptions', 1);
    }

    public function test_legacy_flag_preserves_unverified_behavior_and_logs_warning(): void
    {
        config(['memberships.allow_unverified_self_subscribe' => true]);
        Log::spy();
        $user = $this->createUserWithPerson();
        $packageId = $this->createPackage();

        $this->subscribe($user, $packageId, [
            'payment_status' => 'pending',
            'amount_paid' => 0,
        ])->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.amount_paid', 0);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Unverified family package self-subscribe used.', [
                'user_id' => $user->id,
                'package_id' => $packageId,
            ]);
    }

    public function test_client_pending_payment_status_cannot_override_a_valid_paid_invoice(): void
    {
        $user = $this->createUserWithPerson();
        $packageId = $this->createPackage();
        $invoiceId = $this->createInvoice($user, $packageId, 'completed', 412.50);

        $this->subscribe($user, $packageId, [
            'invoice_id' => $invoiceId,
            'payment_status' => 'pending',
            'amount_paid' => 0,
        ])->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.amount_paid', 412.50);
    }

    private function subscribe(HIPUser $user, int $packageId, array $payload = [])
    {
        Sanctum::actingAs($user);

        return $this->postJson('/api/family-packages/'.$packageId.'/subscribe', $payload);
    }

    private function createUserWithPerson(): HIPUser
    {
        $userId = (string) Str::uuid();
        DB::table('healthinpocket_users')->insert([
            'id' => $userId,
            'first_name' => 'Test',
            'last_name' => 'Member',
            'mobile_num' => (string) random_int(6000000000, 9999999999),
            'password' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $personId = (string) Str::uuid();
        DB::table('persons')->insert([
            'id' => $personId,
            'hip_user_id' => $userId,
            'parent_id' => $personId,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return HIPUser::query()->findOrFail($userId);
    }

    private function createPackage(string $name = 'Test Family Plan'): int
    {
        return (int) DB::table('family_packages')->insertGetId([
            'name' => $name,
            'price' => 999,
            'duration_days' => 30,
            'max_members' => 1,
            'max_consultations' => 0,
            'max_lab_tests' => 0,
            'max_hip_coins' => 0,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createInvoice(HIPUser $user, int $packageId, string $status = 'completed', float $amount = 999): int
    {
        $personId = (string) DB::table('persons')->where('hip_user_id', $user->id)->value('id');

        return (int) DB::table('invoices')->insertGetId([
            'primary_person_id' => $personId,
            'person_id' => $personId,
            'service_types' => json_encode(['family_package']),
            'invoice_details' => json_encode([
                'family_package' => [[
                    'package_id' => $packageId,
                    'package_name' => 'Test Family Plan',
                ]],
            ]),
            'total_amount' => $amount,
            'amount' => $amount,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTables(): void
    {
        foreach (['subscription_usage_logs', 'user_rewards', 'user_family_subscriptions', 'invoices', 'family_packages', 'persons', 'healthinpocket_users'] as $table) {
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
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
        Schema::create('family_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
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
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->uuid('primary_person_id')->nullable();
            $table->uuid('person_id')->nullable();
            $table->json('service_types')->nullable();
            $table->json('invoice_details')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
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
        Schema::create('user_rewards', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_type')->default('family_package');
            $table->uuid('hip_user_id');
            $table->boolean('is_applied')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }
}