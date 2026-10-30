<?php

namespace Tests\Feature;

use App\Livewire\Admin\FamilyPackage\ManageFamilyPackages;
use App\Models\DiagnosticPackage;
use App\Models\FamilyPackage;
use App\Models\Hospital;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ManageFamilyPackagePlanBenefitsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Membership package benefit tests must use the sqlite testing database.');
        }

        $this->bootSchema();

        foreach ([
            'subscription_usage_logs',
            'diagnostic_test_bookings',
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

    public function test_eligible_packages_are_filtered_by_selected_branch_diagnostic_centers(): void
    {
        [$nano, $nanoTest] = $this->seedBranchesAndPackages();

        $component = Livewire::test(ManageFamilyPackages::class);

        $component->call('toggleBranch', $nano->id)
            ->assertSet('branch_ids', [$nano->id]);

        $eligible = $component->instance()->eligibleDiagnosticPackagesForBranches([$nano->id]);
        $this->assertSame(['Nano Health Package'], $eligible->pluck('name')->all());

        $component->call('toggleBranch', $nanoTest->id);
        $eligibleBoth = $component->instance()->eligibleDiagnosticPackagesForBranches([
            $nano->id,
            $nanoTest->id,
        ]);
        $this->assertEqualsCanonicalizing(
            ['Nano Health Package', 'Nano Test Package'],
            $eligibleBoth->pluck('name')->all()
        );

        $component->call('toggleBranch', $nano->id)
            ->call('toggleBranch', $nanoTest->id);

        $this->assertSame([], $component->get('branch_ids'));
        $this->assertTrue(
            $component->instance()->eligibleDiagnosticPackagesForBranches([])->isEmpty()
        );
    }

    public function test_changing_branches_clears_ineligible_benefit_package_selections(): void
    {
        [$nano, $nanoTest, $nanoPackage] = $this->seedBranchesAndPackages();

        Livewire::test(ManageFamilyPackages::class)
            ->call('toggleBranch', $nano->id)
            ->call('addBenefitRow')
            ->set('benefits.0.diagnostic_package_id', (string) $nanoPackage->id)
            ->set('benefits.0.benefit_type', 'free')
            ->set('benefits.0.max_lab_tests', 2)
            ->call('toggleBranch', $nano->id)
            ->call('toggleBranch', $nanoTest->id)
            ->assertSet('benefits.0.diagnostic_package_id', '');
    }

    public function test_saves_structured_package_benefits_and_restores_them_on_edit(): void
    {
        [$nano, , $nanoPackage, $nanoTestPackage] = $this->seedBranchesAndPackages();

        Livewire::test(ManageFamilyPackages::class)
            ->call('openCreateModal')
            ->set('name', 'Family Care')
            ->set('price', 999)
            ->set('duration_days', 365)
            ->set('max_members', 4)
            ->set('max_consultations', 10)
            ->set('max_hip_coins', 100)
            ->call('toggleBranch', $nano->id)
            ->call('addBenefitRow')
            ->set('benefits.0.diagnostic_package_id', (string) $nanoPackage->id)
            ->set('benefits.0.benefit_type', 'free')
            ->set('benefits.0.max_lab_tests', 3)
            ->call('addBenefitRow')
            ->set('benefits.1.diagnostic_package_id', (string) $nanoTestPackage->id)
            ->set('benefits.1.benefit_type', 'free')
            ->set('benefits.1.max_lab_tests', 1)
            ->call('savePackage')
            ->assertHasErrors(['benefits.1.diagnostic_package_id']);

        Livewire::test(ManageFamilyPackages::class)
            ->call('openCreateModal')
            ->set('name', 'Family Care')
            ->set('price', 999)
            ->set('duration_days', 365)
            ->set('max_members', 4)
            ->set('max_consultations', 10)
            ->set('max_hip_coins', 100)
            ->call('toggleBranch', $nano->id)
            ->call('addBenefitRow')
            ->set('benefits.0.diagnostic_package_id', (string) $nanoPackage->id)
            ->set('benefits.0.benefit_type', 'free')
            ->set('benefits.0.max_lab_tests', 3)
            ->call('savePackage')
            ->assertHasNoErrors();

        $package = FamilyPackage::query()->where('name', 'Family Care')->first();
        $this->assertNotNull($package);
        $this->assertSame(0, (int) $package->max_lab_tests);
        $this->assertSame(10, (int) $package->max_consultations);
        $this->assertSame([
            [
                'diagnostic_package_id' => $nanoPackage->id,
                'benefit_type' => 'free',
                'max_lab_tests' => 3,
            ],
        ], $package->benefits);
        $this->assertArrayNotHasKey('free_consultations', $package->benefits[0]);

        Livewire::test(ManageFamilyPackages::class)
            ->call('openEditModal', $package->id)
            ->assertSet('branch_ids', [$nano->id])
            ->assertSet('benefits.0.diagnostic_package_id', (string) $nanoPackage->id)
            ->assertSet('benefits.0.max_lab_tests', 3)
            ->assertSet('benefits.0.benefit_type', 'free')
            ->assertSet('max_consultations', 10);
    }

    public function test_rejects_duplicate_packages_negative_counts_and_unselected_branch_packages(): void
    {
        [$nano, $nanoTest, $nanoPackage, $nanoTestPackage] = $this->seedBranchesAndPackages();

        Livewire::test(ManageFamilyPackages::class)
            ->call('openCreateModal')
            ->set('name', 'Dup Test')
            ->set('price', 100)
            ->set('duration_days', 30)
            ->set('max_members', 1)
            ->call('toggleBranch', $nano->id)
            ->call('addBenefitRow')
            ->call('addBenefitRow')
            ->set('benefits.0.diagnostic_package_id', (string) $nanoPackage->id)
            ->set('benefits.0.benefit_type', 'free')
            ->set('benefits.0.max_lab_tests', 1)
            ->set('benefits.1.diagnostic_package_id', (string) $nanoPackage->id)
            ->set('benefits.1.benefit_type', 'free')
            ->set('benefits.1.max_lab_tests', 2)
            ->call('savePackage')
            ->assertHasErrors(['benefits.0.diagnostic_package_id']);

        Livewire::test(ManageFamilyPackages::class)
            ->call('openCreateModal')
            ->set('name', 'Neg Lab Test')
            ->set('price', 100)
            ->set('duration_days', 30)
            ->set('max_members', 1)
            ->call('toggleBranch', $nano->id)
            ->call('addBenefitRow')
            ->set('benefits.0.diagnostic_package_id', (string) $nanoPackage->id)
            ->set('benefits.0.benefit_type', 'free')
            ->set('benefits.0.max_lab_tests', -2)
            ->call('savePackage')
            ->assertHasErrors(['benefits.0.max_lab_tests']);

        Livewire::test(ManageFamilyPackages::class)
            ->call('openCreateModal')
            ->set('name', 'Cross Branch')
            ->set('price', 100)
            ->set('duration_days', 30)
            ->set('max_members', 1)
            ->call('toggleBranch', $nano->id)
            ->call('addBenefitRow')
            ->set('benefits.0.diagnostic_package_id', (string) $nanoTestPackage->id)
            ->set('benefits.0.benefit_type', 'free')
            ->set('benefits.0.max_lab_tests', 1)
            ->call('savePackage')
            ->assertHasErrors(['benefits.0.diagnostic_package_id']);

        $this->assertNotNull($nanoTest);
    }

    public function test_legacy_string_benefits_still_load_and_are_preserved_when_no_package_rows_saved(): void
    {
        $nano = $this->hospital('Nano', 10);

        $package = FamilyPackage::create([
            'name' => 'Legacy Plan',
            'price' => 500,
            'duration_days' => 365,
            'max_members' => 2,
            'max_consultations' => 0,
            'max_lab_tests' => 0,
            'max_hip_coins' => 0,
            'branch_ids' => [$nano->id],
            'benefits' => ['Free annual checkup', 'Priority support'],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Livewire::test(ManageFamilyPackages::class)
            ->call('openEditModal', $package->id)
            ->assertSet('benefits', [])
            ->assertSet('legacyStringBenefits', ['Free annual checkup', 'Priority support'])
            ->set('name', 'Legacy Plan Updated')
            ->call('savePackage')
            ->assertHasNoErrors();

        $package->refresh();
        $this->assertSame(['Free annual checkup', 'Priority support'], $package->benefits);
        $this->assertSame('Legacy Plan Updated', $package->name);
    }

    public function test_edit_ignores_legacy_free_consultations_without_failing(): void
    {
        [$nano, , $nanoPackage] = $this->seedBranchesAndPackages();

        $package = FamilyPackage::create([
            'name' => 'Legacy Free Consult Benefit',
            'price' => 200,
            'duration_days' => 30,
            'max_members' => 1,
            'max_consultations' => 5,
            'max_lab_tests' => 0,
            'max_hip_coins' => 0,
            'branch_ids' => [$nano->id],
            'benefits' => [[
                'diagnostic_package_id' => $nanoPackage->id,
                'benefit_type' => 'free',
                'free_consultations' => 4,
                'max_lab_tests' => 2,
            ]],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Livewire::test(ManageFamilyPackages::class)
            ->call('openEditModal', $package->id)
            ->assertSet('max_consultations', 5)
            ->assertSet('benefits.0.diagnostic_package_id', (string) $nanoPackage->id)
            ->assertSet('benefits.0.max_lab_tests', 2)
            ->assertSet('benefits.0.benefit_type', 'free')
            ->set('benefits.0.max_lab_tests', 6)
            ->call('savePackage')
            ->assertHasNoErrors();

        $package->refresh();
        $this->assertSame([
            [
                'diagnostic_package_id' => $nanoPackage->id,
                'benefit_type' => 'free',
                'max_lab_tests' => 6,
            ],
        ], $package->benefits);
        $this->assertArrayNotHasKey('free_consultations', $package->benefits[0]);
        $this->assertSame(5, (int) $package->max_consultations);
    }

    public function test_lab_test_allowance_uses_matching_plan_benefit_not_package_level_field(): void
    {
        [$nano, , $nanoPackage] = $this->seedBranchesAndPackages();

        $familyPackage = FamilyPackage::create([
            'name' => 'Benefit Lab Plan',
            'price' => 100,
            'duration_days' => 30,
            'max_members' => 1,
            'max_consultations' => 0,
            'max_lab_tests' => 99, // legacy field must be ignored when benefits exist
            'max_hip_coins' => 0,
            'branch_ids' => [$nano->id],
            'benefits' => [[
                'diagnostic_package_id' => $nanoPackage->id,
                'benefit_type' => 'free',
                'max_lab_tests' => 1,
            ]],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $subscriptionId = DB::table('user_family_subscriptions')->insertGetId([
            'hip_user_id' => 'user-1',
            'family_package_id' => $familyPackage->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'active',
            'payment_status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bookingId = DB::table('diagnostic_test_bookings')->insertGetId([
            'package_id' => $nanoPackage->id,
            'package_type' => 'diagnostic',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(\App\Services\FamilyPackageService::class);

        $service->logUsage($subscriptionId, 'lab_test', $bookingId, 'diagnostic_test_booking', null, $nanoPackage->id);

        $secondBookingId = DB::table('diagnostic_test_bookings')->insertGetId([
            'package_id' => $nanoPackage->id,
            'package_type' => 'diagnostic',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Lab test limit reached for this package benefit.');

        $service->logUsage($subscriptionId, 'lab_test', $secondBookingId, 'diagnostic_test_booking', null, $nanoPackage->id);
    }

    /**
     * @return array{0: Hospital, 1: Hospital, 2: DiagnosticPackage, 3: DiagnosticPackage}
     */
    private function seedBranchesAndPackages(): array
    {
        $nano = $this->hospital('Nano', 10);
        $nanoTest = $this->hospital('Nano Test', 20);

        $nanoPackageId = DB::table('diagnostic_packages')->insertGetId([
            'name' => 'Nano Health Package',
            'diagnostic_id' => 10,
            'status' => 'active',
            'price' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $nanoTestPackageId = DB::table('diagnostic_packages')->insertGetId([
            'name' => 'Nano Test Package',
            'diagnostic_id' => 20,
            'status' => 'active',
            'price' => 800,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            $nano,
            $nanoTest,
            DiagnosticPackage::query()->findOrFail($nanoPackageId),
            DiagnosticPackage::query()->findOrFail($nanoTestPackageId),
        ];
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
        if (Schema::hasTable('family_packages')
            && Schema::hasTable('diagnostic_packages')
            && Schema::hasTable('hospitals')
            && Schema::hasTable('user_family_subscriptions')
            && Schema::hasTable('subscription_usage_logs')
            && Schema::hasTable('diagnostic_test_bookings')) {
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
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('diagnostic_packages', function ($table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('diagnostic_id')->nullable();
            $table->string('status')->default('active');
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
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
