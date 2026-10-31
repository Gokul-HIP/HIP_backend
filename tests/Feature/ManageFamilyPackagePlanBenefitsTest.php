<?php

namespace Tests\Feature;

use App\Livewire\Admin\FamilyPackage\ManageFamilyPackages;
use App\Livewire\HospitalAdmin\Settings\ManageMembershipPackages;
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
            'membership_package_discounts',
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

    public function test_package_form_has_no_plan_benefits_editor(): void
    {
        foreach ([ManageFamilyPackages::class, ManageMembershipPackages::class] as $screen) {
            Livewire::test($screen)
                ->assertSee('Branch Access')
                ->assertSee('Payment discounts')
                ->assertSee('Terms & Conditions')
                ->assertSee('Max Members')
                ->assertDontSee('Max Consultations')
                ->assertDontSee('Plan Benefits')
                ->assertDontSee('Add Package')
                ->assertDontSee('Previous text benefits');
        }
    }

    public function test_saving_a_package_leaves_existing_benefits_unchanged(): void
    {
        [$nano, , $nanoPackage] = $this->seedBranchesAndPackages();

        $structured = [[
            'diagnostic_package_id' => $nanoPackage->id,
            'benefit_type' => 'free',
            'free_consultations' => 4,
            'max_lab_tests' => 2,
        ]];
        $package = FamilyPackage::create([
            'name' => 'Legacy Free Consult Benefit',
            'price' => 200,
            'duration_days' => 30,
            'max_members' => 1,
            'max_consultations' => 5,
            'max_lab_tests' => 7,
            'max_hip_coins' => 0,
            'branch_ids' => [$nano->id],
            'benefits' => $structured,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Livewire::test(ManageFamilyPackages::class)
            ->call('openEditModal', $package->id)
            ->set('name', 'Renamed Benefit Plan')
            ->set('price', 250)
            ->call('savePackage')
            ->assertHasNoErrors();

        $package->refresh();
        $this->assertSame('Renamed Benefit Plan', $package->name);
        $this->assertEquals(250, (float) $package->price);
        $this->assertSame(5, (int) $package->max_consultations);
        $this->assertSame(0, (int) $package->max_hip_coins);
        $this->assertSame(7, (int) $package->max_lab_tests);
        $this->assertSame($structured, $package->benefits);

        $text = FamilyPackage::create([
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
            ->call('openEditModal', $text->id)
            ->set('name', 'Legacy Plan Updated')
            ->call('savePackage')
            ->assertHasNoErrors();

        $this->assertSame(['Free annual checkup', 'Priority support'], $text->fresh()->benefits);

        Livewire::test(ManageFamilyPackages::class)
            ->call('openCreateModal')
            ->set('name', 'New Plan')
            ->set('price', 100)
            ->set('duration_days', 30)
            ->set('max_members', 1)
            ->call('savePackage')
            ->assertHasNoErrors();

        $created = FamilyPackage::query()->where('name', 'New Plan')->first();
        $this->assertNotNull($created);
        $this->assertNull($created->benefits);
        $this->assertSame(0, (int) $created->max_hip_coins);
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

    private function ensureDiscountTable(): void
    {
        if (Schema::hasTable('membership_package_discounts')) {
            return;
        }

        Schema::create('membership_package_discounts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('family_package_id');
            $table->string('payment_type', 64);
            $table->decimal('percentage', 5, 2);
            $table->timestamps();
            $table->unique(['family_package_id', 'payment_type']);
        });
    }

    private function bootSchema(): void
    {
        if (Schema::hasTable('family_packages')
            && Schema::hasTable('diagnostic_packages')
            && Schema::hasTable('hospitals')
            && Schema::hasTable('user_family_subscriptions')
            && Schema::hasTable('subscription_usage_logs')
            && Schema::hasTable('diagnostic_test_bookings')) {
            $this->ensureDiscountTable();

            return;
        }

        Schema::dropIfExists('membership_package_discounts');
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

        $this->ensureDiscountTable();

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
