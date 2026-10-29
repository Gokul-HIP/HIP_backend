<?php

namespace Tests\Feature;

use App\Livewire\Admin\MemberProfile\MemberIndex;
use App\Livewire\ReceptionistAdmin\Patients\Index as ReceptionistPatients;
use App\Models\Coins;
use App\Models\HIPUser;
use App\Models\Persons;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MemberIndexPatientCreateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'user_reward_progress', 'reward_tier_configs', 'reward_tiers', 'coins', 'persons', 'doctor_bookings', 'hospitals', 'healthinpocket_users',
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
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
        });
        Schema::create('persons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('hip_user_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->string('mrn')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('relationship')->nullable();
            $table->string('relationship_other')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('hospitals', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
        });
        Schema::create('doctor_bookings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('patient_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->date('booking_date')->nullable();
        });
        Schema::create('coins', function (Blueprint $table): void {
            $table->id();
            $table->uuid('person_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->integer('coins')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('reward_tiers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('min_coins')->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('reward_tier_configs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('reward_tier_id');
            $table->decimal('total_discount_percentage', 5, 2)->nullable();
            $table->unsignedInteger('free_checkup_count')->default(0);
            $table->unsignedInteger('earned_coins_per_booking')->default(0);
            $table->timestamps();
        });
        Schema::create('user_reward_progress', function (Blueprint $table): void {
            $table->id();
            $table->uuid('hip_user_id');
            $table->unsignedBigInteger('reward_tier_id');
            $table->unsignedInteger('tier_generation')->default(0);
            $table->unsignedInteger('earned_coins_in_tier')->default(0);
            $table->unsignedInteger('total_lifetime_coins')->default(0);
            $table->timestamps();
        });

        DB::table('hospitals')->insert(['id' => 1, 'name' => 'Nano Test']);
        $tierId = DB::table('reward_tiers')->insertGetId([
            'name' => 'Bronze',
            'min_coins' => 0,
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('reward_tier_configs')->insert([
            'reward_tier_id' => $tierId,
            'total_discount_percentage' => 10,
            'free_checkup_count' => 2,
            'earned_coins_per_booking' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_member_index_creates_a_passwordless_patient_with_wallet_and_progress(): void
    {
        Livewire::test(MemberIndex::class)
            ->set($this->patientFields('MRN00011', '9000000011', 'Admin', 'Patient'))
            ->call('savePatient')
            ->assertHasNoErrors();

        $this->assertProvisioned('9000000011', 'MRN00011');
    }

    public function test_receptionist_patient_index_creates_a_passwordless_patient_with_wallet_and_progress(): void
    {
        Livewire::test(ReceptionistPatients::class)
            ->set($this->patientFields('MRN00012', '9000000012', 'Front', 'Desk'))
            ->call('saveMember')
            ->assertHasNoErrors();

        $this->assertProvisioned('9000000012', 'MRN00012');
    }

    /**
     * @return array<string, mixed>
     */
    private function patientFields(string $mrn, string $mobile, string $first, string $last): array
    {
        return [
            'mrn' => $mrn,
            'mrnStatus' => 'available',
            'first_name' => $first,
            'last_name' => $last,
            'mobile' => $mobile,
            'dob' => '1990-01-15',
            'gender' => 'Male',
            'blood_group' => 'A+',
            'hospital_id' => 1,
            'relationship' => 'Self',
            'email' => null,
        ];
    }

    private function assertProvisioned(string $mobile, string $mrn): void
    {
        $user = HIPUser::query()->where('mobile_num', $mobile)->first();
        $this->assertNotNull($user);
        $this->assertNull($user->password);
        $this->assertNotSame('', (string) $user->hip_id);

        $person = Persons::query()->where('hip_user_id', $user->id)->where('is_primary', true)->first();
        $this->assertNotNull($person);
        $this->assertSame($mrn, $person->mrn);
        $this->assertSame(0, (int) Coins::query()->where('person_id', $person->id)->value('coins'));
        $this->assertSame(1, DB::table('user_reward_progress')->where('hip_user_id', $user->id)->count());
    }
}
