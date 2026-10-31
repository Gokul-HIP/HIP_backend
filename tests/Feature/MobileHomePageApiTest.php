<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Coins;
use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Models\HmisPatientSaving;
use App\Models\HmisTransaction;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Persons;
use App\Models\UserFamilySubscription;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileHomePageApiTest extends TestCase
{
    private HIPUser $user;
    private Persons $primaryPerson;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('This test suite requires SQLite.');
        }

        $this->bootSchema();

        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('JSON_VALID', function ($val): int {
            if ($val === null) return 0;
            json_decode((string) $val);
            return json_last_error() === JSON_ERROR_NONE ? 1 : 0;
        });
        $pdo->sqliteCreateFunction('JSON_CONTAINS', function ($json, $val): int {
            if ($json === null) return 0;
            $decoded = json_decode((string) $json, true);
            if (! is_array($decoded)) return 0;
            $target = trim((string) $val, '"\'');
            foreach ($decoded as $item) {
                if ((string) $item === $target) {
                    return 1;
                }
            }
            return 0;
        });

        $this->user = HIPUser::query()->create([
            'hip_id'     => 'HIP00100',
            'first_name' => 'Gokul',
            'last_name'  => 'Dev',
            'mobile_num' => '9988776655',
            'password'   => null,
        ]);

        $this->primaryPerson = Persons::query()->create([
            'id'          => 'PERSON-001',
            'hip_user_id' => $this->user->id,
            'first_name'  => 'Gokul',
            'last_name'   => 'Dev',
            'mobile_num'  => '9988776655',
            'is_primary'  => true,
            'status'      => 'active',
        ]);

        DB::table('hospitals')->insert([
            'id'         => 1,
            'name'       => 'City Hospital',
            'code'       => 'CITY',
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function bootSchema(): void
    {
        Schema::dropIfExists('hmis_transactions');
        Schema::dropIfExists('hmis_patient_savings');
        Schema::dropIfExists('subscription_usage_logs');
        Schema::dropIfExists('user_rewards');
        Schema::dropIfExists('user_family_subscriptions');
        Schema::dropIfExists('family_packages');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('coins');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('ad_placements');
        Schema::dropIfExists('ads');
        Schema::dropIfExists('location_masters');
        Schema::dropIfExists('specialities_masters');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('doctor_bookings');
        Schema::dropIfExists('doctor_assignments');
        Schema::dropIfExists('procedures');
        Schema::dropIfExists('doctor_reviews');
        Schema::dropIfExists('persons');
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
            $table->string('profile_image')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('preferred_branch_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('hospitals', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('logo')->nullable();
            $table->string('status')->default('active');
            $table->decimal('admin_latitude', 10, 7)->nullable();
            $table->decimal('admin_longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('persons', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->uuid('hip_user_id')->nullable();
            $table->string('parent_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile_num')->nullable();
            $table->string('relationship')->nullable();
            $table->string('mrn_id')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('status')->default('active');
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

        Schema::create('family_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('duration_days')->default(365);
            $table->unsignedInteger('max_members')->default(4);
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
            $table->uuid('hip_user_id');
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

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('primary_person_id')->nullable();
            $table->string('person_id')->nullable();
            $table->unsignedBigInteger('doctor_booking_id')->nullable();
            $table->unsignedBigInteger('second_opinion_id')->nullable();
            $table->unsignedBigInteger('diagnostic_test_booking_id')->nullable();
            $table->unsignedBigInteger('pharmacy_order_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->unsignedInteger('coins_earned')->default(0);
            $table->unsignedInteger('coins_applied')->default(0);
            $table->string('status')->default('completed');
            $table->json('service_types')->nullable();
            $table->json('invoice_details')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('success');
            $table->timestamps();
        });

        Schema::create('hmis_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('hospital_code')->nullable();
            $table->string('mrn_id');
            $table->string('person_id')->nullable();
            $table->string('user_id')->nullable();
            $table->string('payment_type');
            $table->timestamp('paid_at')->nullable();
            $table->string('time_slot', 50)->nullable();
            $table->boolean('is_paid')->nullable();
            $table->string('status');
            $table->string('doctor_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('lab_test_name')->nullable();
            $table->decimal('original_amount', 12, 2);
            $table->boolean('is_membership_applied')->default(false);
            $table->unsignedBigInteger('membership_id')->nullable();
            $table->decimal('percentage_discount_applied', 5, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->boolean('is_coins_used')->default(false);
            $table->unsignedInteger('coins_used')->default(0);
            $table->decimal('coin_discount_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2);
            $table->unsignedInteger('coins_earned')->default(0);
            $table->unsignedInteger('coins_reversed')->default(0);
            $table->boolean('is_refund')->default(false);
            $table->string('refund_of_transaction_id')->nullable();
            $table->json('flags')->nullable();
            $table->json('other_data')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('hmis_patient_savings', function (Blueprint $table): void {
            $table->id();
            $table->string('person_id')->unique();
            $table->decimal('lifetime_discount_total', 12, 2)->default(0);
            $table->decimal('membership_discount_total', 12, 2)->default(0);
            $table->decimal('coins_value_total', 12, 2)->default(0);
            $table->decimal('lifetime_paid_total', 12, 2)->default(0);
            $table->unsignedInteger('transactions_count')->default(0);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('user_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('ads', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('media_type')->nullable();
            $table->string('media_url')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('redirect_type')->nullable();
            $table->string('redirect_url')->nullable();
            $table->string('priority_type')->nullable();
            $table->integer('priority')->default(0);
            $table->string('status')->default('active');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_placements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ad_id')->nullable();
            $table->string('placement_type')->nullable();
        });

        Schema::create('location_masters', function (Blueprint $table): void {
            $table->id();
            $table->string('area')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('specialities_masters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('name')->nullable();
            $table->json('diseases')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('doctor_image')->nullable();
            $table->string('qualifications')->nullable();
            $table->string('speciality')->nullable();
            $table->string('assigned_speciality')->nullable();
            $table->string('working_since')->nullable();
            $table->json('assigned_hospital')->nullable();
            $table->json('hospital_ids')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('doctor_bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('patient_id')->nullable();
            $table->uuid('member_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('status')->nullable();
            $table->date('booking_date')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('speciality_id')->nullable();
            $table->string('time_slots')->nullable();
            $table->json('procedure_ids')->nullable();
            $table->string('day')->nullable();
            $table->date('date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('doctor_reviews', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->decimal('rating', 3, 2)->default(5);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('procedures', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /* -------------------------------------------------------------------------
     * PART 1: GET /home/homepage-data
     * ---------------------------------------------------------------------- */

    public function test_homepage_data_with_active_plan_and_savings(): void
    {
        Sanctum::actingAs($this->user);

        // Active package & subscription
        $package = new FamilyPackage;
        $package->forceFill([
            'id'            => 10,
            'name'          => 'Family Care Plan',
            'duration_days' => 365,
            'price'         => 1999,
        ])->save();

        (new UserFamilySubscription)->forceFill([
            'id'                => 24,
            'hip_user_id'       => $this->user->id,
            'family_package_id' => $package->id,
            'start_date'        => now()->subMonths(2)->toDateString(),
            'end_date'          => now()->addMonths(10)->toDateString(),
            'status'            => 'active',
            'payment_status'    => 'paid',
        ])->save();

        // Savings row for wallet person
        HmisPatientSaving::query()->create([
            'person_id'                 => $this->primaryPerson->id,
            'lifetime_discount_total'   => 450.50,
            'membership_discount_total' => 300.00,
            'coins_value_total'         => 150.50,
            'lifetime_paid_total'       => 2500.00,
            'transactions_count'        => 5,
        ]);

        $res = $this->getJson('/api/home/homepage-data?hospital_id=1');
        $res->assertOk();
        $res->assertJsonPath('status', 200);

        // Check lifetime_savings
        $this->assertEquals([
            'amount'     => 450.5,
            'membership' => 300.0,
            'coins'      => 150.5,
        ], $res->json('data.lifetime_savings'));

        // Check membership_plan
        $this->assertTrue($res->json('data.membership_plan.has_active_plan'));
        $this->assertSame('Family Care Plan', $res->json('data.membership_plan.plan_name'));
        $this->assertSame('FAMILY', $res->json('data.membership_plan.plan_badge'));
        $this->assertSame('HIP-0024-FAM', $res->json('data.membership_plan.member_hip_id'));
        $this->assertSame(24, $res->json('data.membership_plan.subscription_id'));
        $this->assertSame(10, $res->json('data.membership_plan.package_id'));

        // Verify existing keys are intact
        $this->assertArrayHasKey('hospital_id', $res->json('data'));
        $this->assertArrayHasKey('preferred_branch_id', $res->json('data'));
        $this->assertArrayHasKey('user_coins', $res->json('data'));
        $this->assertArrayHasKey('unread_notification_count', $res->json('data'));
        $this->assertArrayHasKey('user_profile_image', $res->json('data'));
        $this->assertArrayHasKey('ads', $res->json('data'));
    }

    public function test_homepage_data_with_active_plan_without_savings_returns_all_zeros(): void
    {
        Sanctum::actingAs($this->user);

        $package = FamilyPackage::query()->create([
            'id'            => 12,
            'name'          => 'Gold Health',
            'duration_days' => 365,
            'price'         => 2999,
        ]);

        (new UserFamilySubscription)->forceFill([
            'id'                => 5,
            'hip_user_id'       => $this->user->id,
            'family_package_id' => $package->id,
            'start_date'        => now()->subMonths(1)->toDateString(),
            'end_date'          => now()->addMonths(11)->toDateString(),
            'status'            => 'active',
            'payment_status'    => 'paid',
        ])->save();

        $res = $this->getJson('/api/home/homepage-data?hospital_id=1');
        $res->assertOk();

        $this->assertEquals([
            'amount'     => 0,
            'membership' => 0,
            'coins'      => 0,
        ], $res->json('data.lifetime_savings'));

        $this->assertTrue($res->json('data.membership_plan.has_active_plan'));
        $this->assertSame('HIP-0005-GOL', $res->json('data.membership_plan.member_hip_id'));
    }

    public function test_homepage_data_without_active_plan(): void
    {
        Sanctum::actingAs($this->user);

        $res = $this->getJson('/api/home/homepage-data?hospital_id=1');
        $res->assertOk();

        $this->assertEquals([
            'amount'     => 0,
            'membership' => 0,
            'coins'      => 0,
        ], $res->json('data.lifetime_savings'));

        $this->assertFalse($res->json('data.membership_plan.has_active_plan'));
        $this->assertNull($res->json('data.membership_plan.member_hip_id'));
    }

    /* -------------------------------------------------------------------------
     * PART 2: GET /home/coins-page
     * ---------------------------------------------------------------------- */

    public function test_coins_page_history_with_all_plain_hms_bill_types_as_credited(): void
    {
        Sanctum::actingAs($this->user);

        Coins::query()->create([
            'person_id' => $this->primaryPerson->id,
            'coins'     => 350,
        ]);

        $typesAndExpectedTitles = [
            'appointment'         => 'Appointment Booking',
            'op_consultation'     => 'Appointment Booking',
            'lab_radiology'       => 'Lab Test',
            'lab_test_booking'    => 'Lab Test',
            'diagnostic_booking'  => 'Health Package',
            'op_pharmacy'         => 'Pharmacy',
            'ip_final_settlement' => 'Hospital Service',
        ];

        $idx = 1;
        foreach ($typesAndExpectedTitles as $type => $expectedTitle) {
            HmisTransaction::query()->create([
                'transaction_id'  => 'TX-PLAIN-' . $idx,
                'mrn_id'          => 'MRN-001',
                'person_id'       => $this->primaryPerson->id,
                'payment_type'    => $type,
                'paid_at'         => Carbon::parse('2026-10-01 10:00:00')->addDays($idx),
                'is_paid'         => true,
                'status'          => 'recorded',
                'original_amount' => 500,
                'paid_amount'     => 500,
                'coins_earned'    => 50,
                'flags'           => [],
            ]);
            $idx++;
        }

        $res = $this->getJson('/api/home/coins-page?type=history&per_page=20');
        $res->assertOk();

        $data = $res->json('data');
        $this->assertSame(350, $data['total_remaining_coins']);
        $this->assertSame(350, $data['total_earned']);
        $this->assertSame(0, $data['total_redeemed']);
        $this->assertSame(7, $data['pagination']['total']);

        $history = $data['history'];
        $this->assertCount(7, $history);

        foreach ($history as $item) {
            $this->assertSame('CREDITED', $item['status']);
            $this->assertSame('+50', $item['coins_label']);
            $this->assertContains($item['title'], array_values($typesAndExpectedTitles));
            $this->assertMatchesRegularExpression('/\d{2} [A-Z][a-z]{2} \d{4}/', $item['date']);
        }
    }

    public function test_coins_page_history_with_coin_use_bill(): void
    {
        Sanctum::actingAs($this->user);

        Coins::query()->create([
            'person_id' => $this->primaryPerson->id,
            'coins'     => 80,
        ]);

        HmisTransaction::query()->create([
            'transaction_id'       => 'TX-USE-1',
            'mrn_id'               => 'MRN-001',
            'person_id'            => $this->primaryPerson->id,
            'payment_type'         => 'appointment',
            'paid_at'              => Carbon::parse('2026-10-15 12:00:00'),
            'is_paid'              => true,
            'status'               => 'recorded',
            'original_amount'      => 500,
            'paid_amount'          => 480,
            'is_coins_used'        => true,
            'coins_used'           => 20,
            'coin_discount_amount' => 20,
            'coins_earned'         => 0,
            'flags'                => [],
        ]);

        $res = $this->getJson('/api/home/coins-page?type=history');
        $res->assertOk();

        $data = $res->json('data');
        $this->assertSame(80, $data['total_remaining_coins']);
        $this->assertSame(0, $data['total_earned']);
        $this->assertSame(20, $data['total_redeemed']);
        $this->assertSame(1, $data['pagination']['total']);

        $item = $data['history'][0];
        $this->assertSame('Appointment Booking Discount', $item['title']);
        $this->assertSame('-20', $item['coins_label']);
        $this->assertSame('REDEEMED', $item['status']);
        $this->assertSame('15 Oct 2026', $item['date']);
    }

    public function test_coins_page_history_with_refund_shows_reversed(): void
    {
        Sanctum::actingAs($this->user);

        Coins::query()->create([
            'person_id' => $this->primaryPerson->id,
            'coins'     => 0,
        ]);

        // Original transaction refunded
        $original = HmisTransaction::query()->create([
            'transaction_id'  => 'TX-ORIG-1',
            'mrn_id'          => 'MRN-001',
            'person_id'       => $this->primaryPerson->id,
            'payment_type'    => 'appointment',
            'paid_at'         => Carbon::parse('2026-10-10 10:00:00'),
            'is_paid'         => true,
            'status'          => 'refunded',
            'original_amount' => 500,
            'paid_amount'     => 500,
            'coins_earned'    => 50,
            'coins_reversed'  => 50,
            'flags'           => [],
        ]);

        // Refund transaction row
        HmisTransaction::query()->create([
            'transaction_id'           => 'TX-REFUND-1',
            'mrn_id'                   => 'MRN-001',
            'person_id'                => $this->primaryPerson->id,
            'payment_type'             => 'appointment',
            'paid_at'                  => Carbon::parse('2026-10-12 11:00:00'),
            'is_paid'                  => true,
            'status'                   => 'recorded',
            'is_refund'                => true,
            'refund_of_transaction_id' => $original->transaction_id,
            'original_amount'          => 500,
            'paid_amount'              => 500,
            'coins_reversed'           => 50,
            'flags'                    => [],
        ]);

        $res = $this->getJson('/api/home/coins-page?type=history');
        $res->assertOk();

        $data = $res->json('data');
        $this->assertSame(0, $data['total_remaining_coins']);
        $this->assertSame(2, $data['pagination']['total']);

        // Check entries order: newest first (Refund on Oct 12, Original on Oct 10)
        $history = $data['history'];
        $this->assertCount(2, $history);

        $this->assertSame('Appointment Booking', $history[0]['title']);
        $this->assertSame('-50', $history[0]['coins_label']);
        $this->assertSame('REVERSED', $history[0]['status']);
        $this->assertSame('12 Oct 2026', $history[0]['date']);

        $this->assertSame('Appointment Booking', $history[1]['title']);
        $this->assertSame('+50', $history[1]['coins_label']);
        $this->assertSame('CREDITED', $history[1]['status']);
        $this->assertSame('10 Oct 2026', $history[1]['date']);
    }

    public function test_coins_page_history_pagination(): void
    {
        Sanctum::actingAs($this->user);

        Coins::query()->create([
            'person_id' => $this->primaryPerson->id,
            'coins'     => 150,
        ]);

        for ($i = 1; $i <= 15; $i++) {
            HmisTransaction::query()->create([
                'transaction_id'  => 'TX-PAG-' . $i,
                'mrn_id'          => 'MRN-001',
                'person_id'       => $this->primaryPerson->id,
                'payment_type'    => 'appointment',
                'paid_at'         => Carbon::parse('2026-10-01')->addDays($i),
                'is_paid'         => true,
                'status'          => 'recorded',
                'original_amount' => 100,
                'paid_amount'     => 100,
                'coins_earned'    => 10,
                'flags'           => [],
            ]);
        }

        // Page 1 with per_page=10
        $res1 = $this->getJson('/api/home/coins-page?type=history&per_page=10&page=1');
        $res1->assertOk();
        $this->assertSame(15, $res1->json('data.pagination.total'));
        $this->assertSame(10, $res1->json('data.pagination.count'));
        $this->assertSame(1, $res1->json('data.pagination.current_page'));
        $this->assertSame(2, $res1->json('data.pagination.last_page'));
        $this->assertCount(10, $res1->json('data.history'));

        // Page 2 with per_page=10
        $res2 = $this->getJson('/api/home/coins-page?type=history&per_page=10&page=2');
        $res2->assertOk();
        $this->assertSame(15, $res2->json('data.pagination.total'));
        $this->assertSame(5, $res2->json('data.pagination.count'));
        $this->assertSame(2, $res2->json('data.pagination.current_page'));
        $this->assertCount(5, $res2->json('data.history'));
    }

    /* -------------------------------------------------------------------------
     * PART 3: GET /home/payment-history
     * ---------------------------------------------------------------------- */

    public function test_payment_history_shape_and_attributes(): void
    {
        Sanctum::actingAs($this->user);

        HmisTransaction::query()->create([
            'transaction_id'  => 'TX-SHAPE-1',
            'mrn_id'          => 'MRN-001',
            'person_id'       => $this->primaryPerson->id,
            'payment_type'    => 'appointment',
            'doctor_name'     => 'Dr. Rajesh Sharma',
            'paid_at'         => Carbon::parse('2026-10-29 14:30:00'),
            'is_paid'         => true,
            'status'          => 'recorded',
            'original_amount' => 3989.50,
            'paid_amount'     => 3989.50,
            'flags'           => [],
        ]);

        $res = $this->getJson('/api/home/payment-history');
        $res->assertOk();
        $res->assertJsonPath('status', true);
        $res->assertJsonPath('message', 'Payment history fetched successfully');

        $items = $res->json('data');
        $this->assertCount(1, $items);
        $item = $items[0];

        $this->assertArrayHasKey('id', $item);
        $this->assertSame('Doctor Appointment', $item['title']);
        $this->assertSame('Dr. Rajesh Sharma', $item['description']);
        $this->assertSame('29 OCT 2026', $item['date']);
        $this->assertSame('₹3,989.50', $item['amount_label']);
        $this->assertSame('Paid', $item['status']);
        $this->assertSame(url('assets/notification-icon/coins.webp'), $item['icon']);

        $this->assertSame(1, $res->json('pagination.total'));
    }

    public function test_payment_history_titles_and_descriptions_by_type(): void
    {
        Sanctum::actingAs($this->user);

        $typesMap = [
            'appointment' => [
                'title'       => 'Doctor Appointment',
                'doctor_name' => 'Dr. Jane Doe',
                'lab_name'    => null,
                'desc'        => 'Dr. Jane Doe',
            ],
            'op_consultation' => [
                'title'       => 'Doctor Appointment',
                'doctor_name' => 'Dr. Robert Smith',
                'lab_name'    => null,
                'desc'        => 'Dr. Robert Smith',
            ],
            'diagnostic_booking' => [
                'title'       => 'Health Package',
                'doctor_name' => null,
                'lab_name'    => 'Full Body Checkup',
                'desc'        => 'Full Body Checkup',
            ],
            'lab_test_booking' => [
                'title'       => 'Lab Test',
                'doctor_name' => null,
                'lab_name'    => 'Blood Sugar Fasting',
                'desc'        => 'Blood Sugar Fasting',
            ],
            'lab_radiology' => [
                'title'       => 'Lab Test',
                'doctor_name' => null,
                'lab_name'    => 'Chest X-Ray',
                'desc'        => 'Chest X-Ray',
            ],
            'op_pharmacy' => [
                'title'       => 'Pharmacy',
                'doctor_name' => null,
                'lab_name'    => null,
                'desc'        => 'Pharmacy',
            ],
            'ip_final_settlement' => [
                'title'       => 'Hospital Bill',
                'doctor_name' => null,
                'lab_name'    => null,
                'desc'        => 'Hospital Bill',
            ],
            'membership' => [
                'title'       => 'Membership',
                'doctor_name' => null,
                'lab_name'    => null,
                'desc'        => 'Membership',
            ],
        ];

        $i = 1;
        foreach ($typesMap as $type => $info) {
            HmisTransaction::query()->create([
                'transaction_id'  => 'TX-MAP-' . $i,
                'mrn_id'          => 'MRN-001',
                'person_id'       => $this->primaryPerson->id,
                'payment_type'    => $type,
                'doctor_name'     => $info['doctor_name'],
                'lab_test_name'   => $info['lab_name'],
                'paid_at'         => Carbon::parse('2026-10-01')->addDays($i),
                'is_paid'         => true,
                'status'          => 'recorded',
                'original_amount' => 500,
                'paid_amount'     => 500,
                'flags'           => [],
            ]);
            $i++;
        }

        $res = $this->getJson('/api/home/payment-history?per_page=20');
        $res->assertOk();

        $items = collect($res->json('data'))->keyBy('title');
        foreach ($typesMap as $type => $info) {
            $matched = collect($res->json('data'))->firstWhere('description', $info['desc']);
            $this->assertNotNull($matched, "Failed to find item with description {$info['desc']} for type {$type}");
            $this->assertSame($info['title'], $matched['title']);
        }
    }

    public function test_payment_history_skips_unmatched_and_refund_rows(): void
    {
        Sanctum::actingAs($this->user);

        // Matched recorded row
        HmisTransaction::query()->create([
            'transaction_id'  => 'TX-VALID-1',
            'mrn_id'          => 'MRN-001',
            'person_id'       => $this->primaryPerson->id,
            'payment_type'    => 'appointment',
            'paid_at'         => now(),
            'is_paid'         => true,
            'status'          => 'recorded',
            'original_amount' => 500,
            'paid_amount'     => 500,
            'flags'           => [],
        ]);

        // Unmatched row
        HmisTransaction::query()->create([
            'transaction_id'  => 'TX-UNMATCHED-1',
            'mrn_id'          => 'MRN-UNKNOWN',
            'person_id'       => null,
            'payment_type'    => 'appointment',
            'paid_at'         => now(),
            'is_paid'         => true,
            'status'          => 'unmatched',
            'original_amount' => 500,
            'paid_amount'     => 500,
            'flags'           => [],
        ]);

        // Refund row (is_refund = true)
        HmisTransaction::query()->create([
            'transaction_id'           => 'TX-REF-1',
            'mrn_id'                   => 'MRN-001',
            'person_id'                => $this->primaryPerson->id,
            'payment_type'             => 'appointment',
            'paid_at'                  => now(),
            'is_paid'                  => true,
            'status'                   => 'recorded',
            'is_refund'                => true,
            'refund_of_transaction_id' => 'TX-VALID-1',
            'original_amount'          => 500,
            'paid_amount'              => 500,
            'flags'                    => [],
        ]);

        $res = $this->getJson('/api/home/payment-history');
        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertSame('TX-VALID-1', HmisTransaction::find($res->json('data.0.id'))->transaction_id);
    }

    public function test_payment_history_status_mapping(): void
    {
        Sanctum::actingAs($this->user);

        // Recorded -> Paid
        HmisTransaction::query()->create([
            'transaction_id'  => 'TX-ST-1',
            'mrn_id'          => 'MRN-001',
            'person_id'       => $this->primaryPerson->id,
            'payment_type'    => 'appointment',
            'paid_at'         => now()->subDays(3),
            'is_paid'         => true,
            'status'          => 'recorded',
            'original_amount' => 500,
            'paid_amount'     => 500,
            'flags'           => [],
        ]);

        // Pending -> Pending
        HmisTransaction::query()->create([
            'transaction_id'  => 'TX-ST-2',
            'mrn_id'          => 'MRN-001',
            'person_id'       => $this->primaryPerson->id,
            'payment_type'    => 'appointment',
            'paid_at'         => now()->subDays(2),
            'is_paid'         => false,
            'status'          => 'pending',
            'original_amount' => 500,
            'paid_amount'     => 0,
            'flags'           => [],
        ]);

        // Refunded -> Refunded
        HmisTransaction::query()->create([
            'transaction_id'  => 'TX-ST-3',
            'mrn_id'          => 'MRN-001',
            'person_id'       => $this->primaryPerson->id,
            'payment_type'    => 'appointment',
            'paid_at'         => now()->subDays(1),
            'is_paid'         => true,
            'status'          => 'refunded',
            'original_amount' => 500,
            'paid_amount'     => 500,
            'flags'           => [],
        ]);

        $res = $this->getJson('/api/home/payment-history');
        $res->assertOk();
        $this->assertCount(3, $res->json('data'));

        $statuses = collect($res->json('data'))->pluck('status')->all();
        $this->assertContains('Paid', $statuses);
        $this->assertContains('Pending', $statuses);
        $this->assertContains('Refunded', $statuses);
    }

    public function test_payment_history_type_and_paid_by_filters(): void
    {
        Sanctum::actingAs($this->user);

        // Appointment with membership
        HmisTransaction::query()->create([
            'transaction_id'        => 'TX-FILT-1',
            'mrn_id'                => 'MRN-001',
            'person_id'             => $this->primaryPerson->id,
            'payment_type'          => 'appointment',
            'paid_at'               => now()->subDays(4),
            'is_paid'               => true,
            'status'                => 'recorded',
            'is_membership_applied' => true,
            'membership_id'         => 10,
            'discount_amount'       => 50,
            'original_amount'       => 500,
            'paid_amount'           => 450,
            'flags'                 => [],
        ]);

        // Appointment with coins
        HmisTransaction::query()->create([
            'transaction_id'  => 'TX-FILT-2',
            'mrn_id'          => 'MRN-001',
            'person_id'       => $this->primaryPerson->id,
            'payment_type'    => 'appointment',
            'paid_at'         => now()->subDays(3),
            'is_paid'         => true,
            'status'          => 'recorded',
            'is_coins_used'   => true,
            'coins_used'      => 30,
            'original_amount' => 500,
            'paid_amount'     => 470,
            'flags'           => [],
        ]);

        // Lab test without discount (no_discount)
        HmisTransaction::query()->create([
            'transaction_id'  => 'TX-FILT-3',
            'mrn_id'          => 'MRN-001',
            'person_id'       => $this->primaryPerson->id,
            'payment_type'    => 'lab_test_booking',
            'paid_at'         => now()->subDays(2),
            'is_paid'         => true,
            'status'          => 'recorded',
            'original_amount' => 400,
            'paid_amount'     => 400,
            'flags'           => [],
        ]);

        // Pharmacy with coins
        HmisTransaction::query()->create([
            'transaction_id'  => 'TX-FILT-4',
            'mrn_id'          => 'MRN-001',
            'person_id'       => $this->primaryPerson->id,
            'payment_type'    => 'op_pharmacy',
            'paid_at'         => now()->subDays(1),
            'is_paid'         => true,
            'status'          => 'recorded',
            'is_coins_used'   => true,
            'coins_used'      => 20,
            'original_amount' => 200,
            'paid_amount'     => 180,
            'flags'           => [],
        ]);

        // Filter by type = appointment (should return 2)
        $resType = $this->getJson('/api/home/payment-history?type=appointment');
        $resType->assertOk();
        $this->assertCount(2, $resType->json('data'));

        // Filter by paid_by = membership (should return 1: TX-FILT-1)
        $resPaidMembership = $this->getJson('/api/home/payment-history?paid_by=membership');
        $resPaidMembership->assertOk();
        $this->assertCount(1, $resPaidMembership->json('data'));
        $this->assertSame(450.0, (float) HmisTransaction::find($resPaidMembership->json('data.0.id'))->paid_amount);

        // Filter by paid_by = coins (should return 2: TX-FILT-2, TX-FILT-4)
        $resPaidCoins = $this->getJson('/api/home/payment-history?paid_by=coins');
        $resPaidCoins->assertOk();
        $this->assertCount(2, $resPaidCoins->json('data'));

        // Filter by paid_by = no_discount (should return 1: TX-FILT-3)
        $resPaidNoDisc = $this->getJson('/api/home/payment-history?paid_by=no_discount');
        $resPaidNoDisc->assertOk();
        $this->assertCount(1, $resPaidNoDisc->json('data'));
        $this->assertSame(400.0, (float) HmisTransaction::find($resPaidNoDisc->json('data.0.id'))->paid_amount);

        // Combined filter: type=appointment AND paid_by=coins (should return 1: TX-FILT-2)
        $resCombined = $this->getJson('/api/home/payment-history?type=appointment&paid_by=coins');
        $resCombined->assertOk();
        $this->assertCount(1, $resCombined->json('data'));
        $this->assertSame(470.0, (float) HmisTransaction::find($resCombined->json('data.0.id'))->paid_amount);

        // Combined filter: type=op_pharmacy AND paid_by=membership (should return 0)
        $resEmpty = $this->getJson('/api/home/payment-history?type=op_pharmacy&paid_by=membership');
        $resEmpty->assertOk();
        $this->assertCount(0, $resEmpty->json('data'));
    }

    public function test_payment_history_invalid_type_returns_422_with_allowed_values(): void
    {
        Sanctum::actingAs($this->user);

        $res = $this->getJson('/api/home/payment-history?type=super_consultation');
        $res->assertStatus(422);
        $res->assertJsonPath('status', false);
        $message = $res->json('message');
        $this->assertStringContainsString('Allowed values', $message);
        $this->assertStringContainsString('op_consultation', $message);
        $this->assertStringContainsString('appointment', $message);
    }

    public function test_payment_history_invalid_paid_by_returns_422_with_allowed_values(): void
    {
        Sanctum::actingAs($this->user);

        $res = $this->getJson('/api/home/payment-history?paid_by=bitcoin');
        $res->assertStatus(422);
        $res->assertJsonPath('status', false);
        $message = $res->json('message');
        $this->assertStringContainsString('Allowed values', $message);
        $this->assertStringContainsString('membership', $message);
        $this->assertStringContainsString('coins', $message);
        $this->assertStringContainsString('no_discount', $message);
    }
}
