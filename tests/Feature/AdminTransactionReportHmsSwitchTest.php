<?php

namespace Tests\Feature;

use App\Enums\HmsPaymentType;
use App\Http\Controllers\Admin\TransactionsExportController;
use App\Livewire\Admin\Transaction\Index;
use App\Models\HIPUser;
use App\Models\HmisTransaction;
use App\Models\HmsIntegration;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Persons;
use App\Models\Transactions;
use App\Services\Admin\TransactionsExportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTransactionReportHmsSwitchTest extends TestCase
{
    private HIPUser $admin;
    private Organization $org1;
    private Organization $org2;
    private Hospital $hospital1;
    private Hospital $hospital2;
    private HmsIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootSchema();

        $role = Role::create([
            'name' => 'super-admin-hip',
            'guard_name' => 'filament',
        ]);

        $this->admin = HIPUser::withoutEvents(function () {
            return HIPUser::query()->create([
                'id' => (string) Str::uuid(),
                'hip_id' => 'HIP00099',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'superadmin@example.com',
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]);
        });
        $this->admin->assignRole($role);

        $this->org1 = Organization::query()->create(['name' => 'Apex Healthcare', 'code' => 'APEX']);
        $this->org2 = Organization::query()->create(['name' => 'Beacon Health', 'code' => 'BEACON']);

        $this->hospital1 = Hospital::query()->create([
            'name' => 'City Hospital',
            'code' => 'CITY',
            'organization_id' => $this->org1->id,
            'status' => 'active',
        ]);

        $this->hospital2 = Hospital::query()->create([
            'name' => 'Metro Care',
            'code' => 'METRO',
            'organization_id' => $this->org2->id,
            'status' => 'active',
        ]);

        $this->integration = HmsIntegration::query()->create([
            'name' => 'City HMS Integration',
            'hospital_ids' => [$this->hospital1->id],
            'organization_id' => $this->org1->id,
            'is_enabled' => true,
        ]);
    }

    private function bootSchema(): void
    {
        foreach ([
            'model_has_roles', 'roles', 'hmis_transactions', 'hms_integrations',
            'transactions', 'invoices', 'doctor_bookings', 'second_opinions',
            'diagnostic_test_bookings', 'persons', 'hospitals', 'organizations',
            'healthinpocket_users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('healthinpocket_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('hip_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_num')->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->uuid('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code', 50)->nullable();
            $table->userstamps();
            $table->timestamps();
        });

        Schema::create('hospitals', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code', 50)->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('status')->default('active');
            $table->userstamps();
            $table->timestamps();
        });

        Schema::create('hms_integrations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('hospital_ids')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('api_key_hash')->nullable();
            $table->text('signing_secret')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->integer('rate_limit_per_minute')->default(60);
            $table->timestamp('last_used_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('persons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('hip_user_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_bookings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('second_opinions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('diagnostic_test_bookings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('primary_person_id')->nullable();
            $table->string('person_id')->nullable();
            $table->unsignedBigInteger('doctor_booking_id')->nullable();
            $table->unsignedBigInteger('second_opinion_id')->nullable();
            $table->unsignedBigInteger('diagnostic_test_booking_id')->nullable();
            $table->userstamps();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->json('service_types')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('transaction_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('completed');
            $table->userstamps();
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
            $table->decimal('original_amount', 12, 2)->default(0);
            $table->boolean('is_membership_applied')->default(false);
            $table->unsignedBigInteger('membership_id')->nullable();
            $table->decimal('percentage_discount_applied', 5, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->boolean('is_coins_used')->default(false);
            $table->unsignedInteger('coins_used')->default(0);
            $table->decimal('coin_discount_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->unsignedInteger('coins_earned')->default(0);
            $table->unsignedInteger('coins_reversed')->default(0);
            $table->boolean('is_refund')->default(false);
            $table->string('refund_of_transaction_id')->nullable();
            $table->json('flags')->nullable();
            $table->json('other_data')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function test_flag_false_leaves_original_transactions_behaviour_completely_unchanged(): void
    {
        config(['hms.transaction_report_use_hms' => false]);
        $this->actingAs($this->admin, 'filament');

        $person = Persons::query()->create([
            'id' => (string) Str::uuid(),
            'first_name' => 'Legacy',
            'last_name' => 'Patient',
            'mobile' => '9988776655',
        ]);

        $booking = DB::table('doctor_bookings')->insertGetId([
            'hospital_id' => $this->hospital1->id,
            'branch_id' => $this->hospital1->id,
        ]);

        $invoice = Invoice::query()->create([
            'primary_person_id' => $person->id,
            'doctor_booking_id' => $booking,
        ]);

        Transactions::query()->forceCreate([
            'id' => 101,
            'invoice_id' => $invoice->id,
            'service_types' => ['doctor_consultation'],
            'total_amount' => 750.00,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        // Put a row in hmis_transactions to prove it is ignored when flag is false
        HmisTransaction::query()->create([
            'transaction_id' => 'TX-HMS-IGNORED',
            'mrn_id' => 'MRN-IGNORE',
            'payment_type' => 'op_consultation',
            'paid_amount' => 9999.00,
            'status' => 'recorded',
            'paid_at' => now(),
        ]);

        $component = Livewire::test(Index::class);

        // Flag false defaults date range to current month
        $this->assertSame(now()->startOfMonth()->format('Y-m-d'), $component->get('fromDate'));
        $this->assertSame(now()->endOfMonth()->format('Y-m-d'), $component->get('toDate'));

        // Reset in flag false sets dates back to current month
        $component->set('fromDate', '2026-01-01')
            ->set('toDate', '2026-01-31')
            ->call('resetFilters');

        $this->assertSame(now()->startOfMonth()->format('Y-m-d'), $component->get('fromDate'));
        $this->assertSame(now()->endOfMonth()->format('Y-m-d'), $component->get('toDate'));

        $component->assertDontSee('Showing HMS transactions')
            ->assertSee('TXN-00000101')
            ->assertSee('Legacy Patient')
            ->assertSee('Rs750.00')
            ->assertDontSee('TX-HMS-IGNORED');

        $csv = app(TransactionsExportService::class)->getCsvContent();
        $this->assertStringContainsString('TXN-00000101', $csv);
        $this->assertStringNotContainsString('TX-HMS-IGNORED', $csv);
    }

    public function test_flag_true_renders_hmis_transactions_list_cards_and_badge(): void
    {
        config(['hms.transaction_report_use_hms' => true]);
        $this->actingAs($this->admin, 'filament');

        $person = Persons::query()->create([
            'id' => (string) Str::uuid(),
            'first_name' => 'Aarav',
            'last_name' => 'Sharma',
            'mobile' => '9123456780',
        ]);

        // Record 1: Created in September (earliest), paid in September
        HmisTransaction::query()->forceCreate([
            'transaction_id' => 'TX-HMS-001',
            'hospital_code' => 'CITY',
            'mrn_id' => 'MRN-101',
            'person_id' => $person->id,
            'payment_type' => 'op_consultation',
            'paid_amount' => 500.00,
            'status' => 'recorded',
            'paid_at' => '2026-09-08 09:00:00',
            'created_at' => '2026-09-10 10:00:00',
            'raw_payload' => ['payment_method' => 'card'],
        ]);

        // Record 2: Created in October (middle), paid in November (newer paid_at than Record 3 to verify created_at sort)
        HmisTransaction::query()->forceCreate([
            'transaction_id' => 'TX-HMS-002',
            'hospital_code' => null, // Resolves via integration's hospital (City Hospital)
            'mrn_id' => 'MRN-102',
            'person_id' => null,
            'payment_type' => 'membership',
            'paid_amount' => 1200.00,
            'status' => 'pending',
            'paid_at' => '2026-11-01 08:00:00',
            'created_at' => '2026-10-15 11:00:00',
            'raw_payload' => ['patient_name' => 'Kavita Patel', 'payment_method' => 'upi'],
        ]);

        // Record 3: Created in November (newest), paid in October
        HmisTransaction::query()->forceCreate([
            'transaction_id' => 'TX-HMS-003',
            'hospital_code' => 'METRO',
            'mrn_id' => 'MRN-UNMATCHED-99',
            'person_id' => null,
            'payment_type' => 'lab_radiology',
            'paid_amount' => 350.00,
            'status' => 'unmatched',
            'paid_at' => '2026-10-20 09:00:00',
            'created_at' => '2026-11-03 12:00:00',
        ]);

        // Record 4: Refund record (is_refund = true) should be excluded from listing
        HmisTransaction::query()->forceCreate([
            'transaction_id' => 'TX-HMS-REFUND-001',
            'hospital_code' => 'CITY',
            'mrn_id' => 'MRN-101',
            'payment_type' => 'op_consultation',
            'paid_amount' => 500.00,
            'is_refund' => true,
            'status' => 'recorded',
            'paid_at' => '2026-11-03 12:00:00',
            'created_at' => '2026-11-03 12:00:00',
        ]);

        $component = Livewire::test(Index::class);

        // HMS mode defaults fromDate and toDate to empty
        $this->assertSame('', $component->get('fromDate'));
        $this->assertSame('', $component->get('toDate'));

        // Badge is shown
        $component->assertSee('Showing HMS transactions');

        // All 3 records across September, October, November show on first load
        $component->assertSee('TX-HMS-001')
            ->assertSee('TX-HMS-002')
            ->assertSee('TX-HMS-003');

        // Verify order is ORDER BY created_at DESC, id DESC:
        // TX-HMS-003 (created Nov 3) appears before TX-HMS-002 (created Oct 15) before TX-HMS-001 (created Sep 10)
        $html = $component->html();
        $pos3 = strpos($html, 'TX-HMS-003');
        $pos2 = strpos($html, 'TX-HMS-002');
        $pos1 = strpos($html, 'TX-HMS-001');
        $this->assertTrue($pos3 !== false && $pos2 !== false && $pos1 !== false);
        $this->assertTrue($pos3 < $pos2, 'TX-HMS-003 must appear before TX-HMS-002 based on created_at DESC');
        $this->assertTrue($pos2 < $pos1, 'TX-HMS-002 must appear before TX-HMS-001 based on created_at DESC');

        // Date column still displays paid_at
        $component->assertSee('08 Sep, 2026')
            ->assertSee('01 Nov, 2026')
            ->assertSee('20 Oct, 2026');

        // Refund row excluded
        $component->assertDontSee('TX-HMS-REFUND-001');

        // Summary Cards check across all months
        // Total received = 500.00 (only recorded rows)
        // Total transactions = 3 (non-refund rows)
        // Successful = 1
        // Pending = 1
        $component->assertSee('Rs500.00')
            ->assertSee('3')
            ->assertSee('1');
    }

    public function test_flag_true_filters_status_service_organization_hospital_search_and_dates(): void
    {
        config(['hms.transaction_report_use_hms' => true]);
        $this->actingAs($this->admin, 'filament');

        HmisTransaction::query()->forceCreate([
            'transaction_id' => 'TX-FILTER-01',
            'hospital_code' => 'CITY',
            'mrn_id' => 'MRN-DOC-1',
            'doctor_name' => 'Dr. Alok Verma',
            'payment_type' => 'op_consultation',
            'paid_amount' => 600.00,
            'status' => 'recorded',
            'paid_at' => '2026-11-02 10:00:00',
            'created_at' => '2026-11-02 10:00:00',
            'raw_payload' => ['patient_name' => 'Sameer Khan'],
        ]);

        HmisTransaction::query()->forceCreate([
            'transaction_id' => 'TX-FILTER-02',
            'hospital_code' => 'METRO',
            'mrn_id' => 'MRN-LAB-2',
            'lab_test_name' => 'Lipid Profile',
            'payment_type' => 'lab_test_booking',
            'paid_amount' => 900.00,
            'status' => 'pending',
            'paid_at' => '2026-11-03 15:30:00',
            'created_at' => '2026-11-03 15:30:00',
            'raw_payload' => ['patient_name' => 'Rohan Mehta'],
        ]);

        HmisTransaction::query()->forceCreate([
            'transaction_id' => 'TX-FILTER-03',
            'hospital_code' => 'CITY',
            'mrn_id' => 'MRN-IP-3',
            'payment_type' => 'ip_advance_deposit',
            'paid_amount' => 2500.00,
            'status' => 'recorded',
            'paid_at' => '2026-11-05 18:00:00',
            'created_at' => '2026-11-05 18:00:00',
            'raw_payload' => ['patient_name' => 'Pooja Verma'],
        ]);

        $component = Livewire::test(Index::class);

        // Status filter
        $component->set('statusFilter', 'recorded')
            ->assertSee('TX-FILTER-01')
            ->assertSee('TX-FILTER-03')
            ->assertDontSee('TX-FILTER-02')
            ->set('statusFilter', 'pending')
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-03')
            ->set('statusFilter', 'all');

        // Service Type filter
        $component->set('serviceTypeFilter', 'op_consultation')
            ->assertSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-03')
            ->set('serviceTypeFilter', 'lab_test_booking')
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-03')
            ->set('serviceTypeFilter', 'all');

        // Organization filter
        $component->set('organizationFilter', (string) $this->org1->id)
            ->assertSee('TX-FILTER-01')
            ->assertSee('TX-FILTER-03')
            ->assertDontSee('TX-FILTER-02')
            ->set('organizationFilter', (string) $this->org2->id)
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-03')
            ->set('organizationFilter', 'all');

        // Hospital filter
        $component->set('hospitalFilter', (string) $this->hospital1->id)
            ->assertSee('TX-FILTER-01')
            ->assertSee('TX-FILTER-03')
            ->assertDontSee('TX-FILTER-02')
            ->set('hospitalFilter', (string) $this->hospital2->id)
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-03')
            ->set('hospitalFilter', 'all');

        // Search by transaction_id
        $component->set('search', 'TX-FILTER-01')
            ->assertSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-03')
            ->set('search', '');

        // Search by doctor_name
        $component->set('search', 'Dr. Alok Verma')
            ->assertSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-03')
            ->set('search', '');

        // Search by lab_test_name
        $component->set('search', 'Lipid Profile')
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-03')
            ->set('search', '');

        // Date range filter: From only (>= start of 2026-11-03)
        $component->set('fromDate', '2026-11-03')
            ->set('toDate', '')
            ->assertSee('TX-FILTER-02')
            ->assertSee('TX-FILTER-03')
            ->assertDontSee('TX-FILTER-01');

        // Date range filter: To only (<= end of 2026-11-03)
        $component->set('fromDate', '')
            ->set('toDate', '2026-11-03')
            ->assertSee('TX-FILTER-01')
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-03');

        // Date range filter: Both (from 2026-11-03 to 2026-11-03, inclusive)
        $component->set('fromDate', '2026-11-03')
            ->set('toDate', '2026-11-03')
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-03');

        // Reset clears dates back to empty in HMS mode
        $component->call('resetFilters');
        $this->assertSame('', $component->get('fromDate'));
        $this->assertSame('', $component->get('toDate'));
        $component->assertSee('TX-FILTER-01')
            ->assertSee('TX-FILTER-02')
            ->assertSee('TX-FILTER-03');
    }

    public function test_flag_true_converts_hms_paid_at_with_or_without_offset_to_app_timezone(): void
    {
        config(['hms.transaction_report_use_hms' => true]);
        $this->actingAs($this->admin, 'filament');

        // UTC timestamp 2026-11-02T19:30:00Z corresponds to 2026-11-03 01:00:00 in Asia/Kolkata (+05:30)
        HmisTransaction::query()->create([
            'transaction_id' => 'TX-TZ-001',
            'hospital_code' => 'CITY',
            'mrn_id' => 'MRN-TZ-1',
            'payment_type' => 'op_consultation',
            'paid_amount' => 450.00,
            'status' => 'recorded',
            'paid_at' => '2026-11-02T19:30:00Z',
            'raw_payload' => ['patient_name' => 'Midnight Payer'],
        ]);

        $component = Livewire::test(Index::class);

        // Displayed formatted date must be in app timezone (03 Nov 2026, 01:00 AM)
        $component->assertSee('TX-TZ-001')
            ->assertSee('03 Nov, 2026 01:00 AM');

        // Filter for 2026-11-03 includes this record
        $component->set('fromDate', '2026-11-03')
            ->set('toDate', '2026-11-03')
            ->assertSee('TX-TZ-001');

        // Filter for 2026-11-02 does NOT include this record
        $component->set('fromDate', '2026-11-02')
            ->set('toDate', '2026-11-02')
            ->assertDontSee('TX-TZ-001');
    }

    public function test_flag_true_exports_hmis_transactions_csv(): void
    {
        config(['hms.transaction_report_use_hms' => true]);
        $this->actingAs($this->admin, 'filament');

        HmisTransaction::query()->forceCreate([
            'transaction_id' => 'TX-EXPORT-MAY',
            'hospital_code' => 'CITY',
            'mrn_id' => 'MRN-EXP-88',
            'payment_type' => 'membership',
            'paid_amount' => 1499.00,
            'status' => 'recorded',
            'paid_at' => '2026-05-15 14:30:00',
            'created_at' => '2026-05-15 14:30:00',
            'raw_payload' => ['patient_name' => 'May User', 'payment_method' => 'netbanking'],
        ]);

        HmisTransaction::query()->forceCreate([
            'transaction_id' => 'TX-EXPORT-NOV',
            'hospital_code' => 'CITY',
            'mrn_id' => 'MRN-EXP-99',
            'payment_type' => 'op_consultation',
            'paid_amount' => 500.00,
            'status' => 'recorded',
            'paid_at' => '2026-11-03 10:00:00',
            'created_at' => '2026-11-03 10:00:00',
            'raw_payload' => ['patient_name' => 'Nov User', 'payment_method' => 'card'],
        ]);

        $service = app(TransactionsExportService::class);
        // With empty dates, export covers rows from all months
        $csv = $service->getCsvContent(['from_date' => '', 'to_date' => '']);

        $this->assertStringContainsString('"Payment ID"', $csv);
        $this->assertStringContainsString('"Member Name"', $csv);
        $this->assertStringContainsString('"Service Type"', $csv);
        $this->assertStringContainsString('Amount', $csv);
        $this->assertStringContainsString('Status', $csv);
        $this->assertStringContainsString('Date', $csv);

        $this->assertStringContainsString('TX-EXPORT-MAY', $csv);
        $this->assertStringContainsString('TX-EXPORT-NOV', $csv);

        // Verify CSV export order is created_at DESC (Nov User before May User)
        $posNov = strpos($csv, 'TX-EXPORT-NOV');
        $posMay = strpos($csv, 'TX-EXPORT-MAY');
        $this->assertTrue($posNov !== false && $posMay !== false);
        $this->assertTrue($posNov < $posMay, 'Export order must be created_at DESC');

        // Controller endpoint test
        $response = $this->get('/admin/transactions/export?from_date=&to_date=');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('TX-EXPORT-NOV', $response->getContent());
        $this->assertStringContainsString('TX-EXPORT-MAY', $response->getContent());
    }
}
