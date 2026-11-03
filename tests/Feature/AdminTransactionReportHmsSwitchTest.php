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

        // Record 1: Matched person, recorded (Successful), op_consultation
        HmisTransaction::query()->create([
            'transaction_id' => 'TX-HMS-001',
            'hospital_code' => 'CITY',
            'mrn_id' => 'MRN-101',
            'person_id' => $person->id,
            'payment_type' => 'op_consultation',
            'paid_amount' => 500.00,
            'status' => 'recorded',
            'paid_at' => now(),
            'raw_payload' => ['payment_method' => 'card'],
        ]);

        // Record 2: Unmatched person, has raw_payload patient_name, membership, pending
        HmisTransaction::query()->create([
            'transaction_id' => 'TX-HMS-002',
            'hospital_code' => null, // Resolves via integration's hospital (City Hospital)
            'mrn_id' => 'MRN-102',
            'person_id' => null,
            'payment_type' => 'membership',
            'paid_amount' => 1200.00,
            'status' => 'pending',
            'paid_at' => now(),
            'raw_payload' => ['patient_name' => 'Kavita Patel', 'payment_method' => 'upi'],
        ]);

        // Record 3: Unmatched status, falls back to mrn_id as member name
        HmisTransaction::query()->create([
            'transaction_id' => 'TX-HMS-003',
            'hospital_code' => 'METRO',
            'mrn_id' => 'MRN-UNMATCHED-99',
            'person_id' => null,
            'payment_type' => 'lab_radiology',
            'paid_amount' => 350.00,
            'status' => 'unmatched',
            'paid_at' => now(),
        ]);

        // Record 4: Refund record (is_refund = true) should be excluded from listing
        HmisTransaction::query()->create([
            'transaction_id' => 'TX-HMS-REFUND-001',
            'hospital_code' => 'CITY',
            'mrn_id' => 'MRN-101',
            'payment_type' => 'op_consultation',
            'paid_amount' => 500.00,
            'is_refund' => true,
            'status' => 'recorded',
            'paid_at' => now(),
        ]);

        $component = Livewire::test(Index::class);

        // Badge is shown
        $component->assertSee('Showing HMS transactions');

        // Record 1 verification
        $component->assertSee('TX-HMS-001')
            ->assertSee('Aarav Sharma')
            ->assertSee('OP consultation')
            ->assertSee('City Hospital')
            ->assertSee('Apex Healthcare')
            ->assertSee('Rs500.00')
            ->assertSee('card')
            ->assertSee('Successful');

        // Record 2 verification (raw_payload patient_name and integration hospital resolution)
        $component->assertSee('TX-HMS-002')
            ->assertSee('Kavita Patel')
            ->assertSee('Membership')
            ->assertSee('City Hospital')
            ->assertSee('Rs1,200.00')
            ->assertSee('Pending');

        // Record 3 verification (fallback to MRN)
        $component->assertSee('TX-HMS-003')
            ->assertSee('MRN-UNMATCHED-99')
            ->assertSee('Lab and radiology')
            ->assertSee('Metro Care')
            ->assertSee('Beacon Health')
            ->assertSee('Rs350.00')
            ->assertSee('Unmatched');

        // Refund row excluded
        $component->assertDontSee('TX-HMS-REFUND-001');

        // Summary Cards check
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

        HmisTransaction::query()->create([
            'transaction_id' => 'TX-FILTER-01',
            'hospital_code' => 'CITY',
            'mrn_id' => 'MRN-DOC-1',
            'doctor_name' => 'Dr. Alok Verma',
            'payment_type' => 'op_consultation',
            'paid_amount' => 600.00,
            'status' => 'recorded',
            'paid_at' => now()->startOfMonth()->addDays(2),
            'raw_payload' => ['patient_name' => 'Sameer Khan'],
        ]);

        HmisTransaction::query()->create([
            'transaction_id' => 'TX-FILTER-02',
            'hospital_code' => 'METRO',
            'mrn_id' => 'MRN-LAB-2',
            'lab_test_name' => 'Lipid Profile',
            'payment_type' => 'lab_test_booking',
            'paid_amount' => 900.00,
            'status' => 'pending',
            'paid_at' => now()->startOfMonth()->addDays(5),
            'raw_payload' => ['patient_name' => 'Rohan Mehta'],
        ]);

        $component = Livewire::test(Index::class);

        // Status filter
        $component->set('statusFilter', 'recorded')
            ->assertSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-02')
            ->set('statusFilter', 'pending')
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->set('statusFilter', 'all');

        // Service Type filter
        $component->set('serviceTypeFilter', 'op_consultation')
            ->assertSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-02')
            ->set('serviceTypeFilter', 'lab_test_booking')
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->set('serviceTypeFilter', 'all');

        // Organization filter
        $component->set('organizationFilter', (string) $this->org1->id)
            ->assertSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-02')
            ->set('organizationFilter', (string) $this->org2->id)
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->set('organizationFilter', 'all');

        // Hospital filter
        $component->set('hospitalFilter', (string) $this->hospital1->id)
            ->assertSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-02')
            ->set('hospitalFilter', (string) $this->hospital2->id)
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->set('hospitalFilter', 'all');

        // Search by transaction_id
        $component->set('search', 'TX-FILTER-01')
            ->assertSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-02')
            ->set('search', '');

        // Search by doctor_name
        $component->set('search', 'Dr. Alok Verma')
            ->assertSee('TX-FILTER-01')
            ->assertDontSee('TX-FILTER-02')
            ->set('search', '');

        // Search by lab_test_name
        $component->set('search', 'Lipid Profile')
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->set('search', '');

        // Search by patient_name from raw_payload
        $component->set('search', 'Rohan Mehta')
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->set('search', '');

        // Date range filter
        $component->set('fromDate', now()->startOfMonth()->addDays(4)->format('Y-m-d'))
            ->assertSee('TX-FILTER-02')
            ->assertDontSee('TX-FILTER-01')
            ->call('resetFilters')
            ->assertSee('TX-FILTER-01')
            ->assertSee('TX-FILTER-02');
    }

    public function test_flag_true_exports_hmis_transactions_csv(): void
    {
        config(['hms.transaction_report_use_hms' => true]);
        $this->actingAs($this->admin, 'filament');

        HmisTransaction::query()->create([
            'transaction_id' => 'TX-EXPORT-88',
            'hospital_code' => 'CITY',
            'mrn_id' => 'MRN-EXP-88',
            'payment_type' => 'membership',
            'paid_amount' => 1499.00,
            'status' => 'recorded',
            'paid_at' => '2026-05-15 14:30:00',
            'raw_payload' => ['patient_name' => 'Export User', 'payment_method' => 'netbanking'],
        ]);

        $service = app(TransactionsExportService::class);
        $csv = $service->getCsvContent(['search' => 'TX-EXPORT-88']);

        $this->assertStringContainsString('"Payment ID"', $csv);
        $this->assertStringContainsString('"Member Name"', $csv);
        $this->assertStringContainsString('"Service Type"', $csv);
        $this->assertStringContainsString('Amount', $csv);
        $this->assertStringContainsString('Status', $csv);
        $this->assertStringContainsString('Date', $csv);
        $this->assertStringContainsString('TX-EXPORT-88', $csv);
        $this->assertStringContainsString('Export User', $csv);
        $this->assertStringContainsString('Membership', $csv);
        $this->assertStringContainsString('City Hospital', $csv);
        $this->assertStringContainsString('Apex Healthcare', $csv);
        $this->assertStringContainsString('1499.00', $csv);
        $this->assertStringContainsString('netbanking', $csv);
        $this->assertStringContainsString('Successful', $csv);
        $this->assertStringContainsString('2026-05-15 14:30:00', $csv);

        // Controller endpoint test
        $response = $this->get('/admin/transactions/export?search=TX-EXPORT-88');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('TX-EXPORT-88', $response->getContent());
    }
}
