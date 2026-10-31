<?php

namespace Tests\Feature;

use App\Livewire\Admin\MemberProfile\MemberIndex;
use App\Livewire\ReceptionistAdmin\Patients\Index;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Services\AuthService;
use App\Services\RewardTierService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class PersonMrnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('MRN tests must use the sqlite testing database.');
        }

        $this->bootSchema();
        $this->registerSqliteRegexp();

        $this->mock(RewardTierService::class, function ($mock): void {
            $mock->shouldReceive('enrollUserOnRegister')->andReturnNull();
        });
    }

    public function test_register_creates_a_person_with_a_null_mrn(): void
    {
        $result = app(AuthService::class)->register($this->account('Asha', '9000000001', 'asha@example.com'));

        $person = Persons::query()->where('hip_user_id', $result['user_id'])->first();

        $this->assertNotNull($person);
        $this->assertNull($person->mrn);
    }

    public function test_register_does_not_fill_an_existing_persons_mrn(): void
    {
        Persons::create([
            'first_name' => 'Existing',
            'mobile' => '9000000002',
            'hip_user_id' => null,
            'mrn' => 'HOSP-KEEP',
            'is_primary' => true,
        ]);

        app(AuthService::class)->register($this->account('Existing', '9000000002', 'existing@example.com'));

        $this->assertSame('HOSP-KEEP', Persons::query()->where('mobile', '9000000002')->value('mrn'));
    }

    public function test_login_creates_a_person_with_a_null_mrn(): void
    {
        $result = app(AuthService::class)->login(['mobile' => '9000000003']);

        $person = Persons::query()->where('hip_user_id', $result['user_id'])->first();

        $this->assertNotNull($person);
        $this->assertNull($person->mrn);
    }

    public function test_login_keeps_an_existing_null_mrn_null(): void
    {
        Persons::create([
            'first_name' => 'Linked',
            'mobile' => '9000000004',
            'hip_user_id' => null,
            'mrn' => null,
            'is_primary' => true,
        ]);

        app(AuthService::class)->login(['mobile' => '9000000004']);

        $this->assertNull(Persons::query()->where('mobile', '9000000004')->value('mrn'));
        $this->assertSame(1, Persons::query()->where('mobile', '9000000004')->count());
    }

    public function test_member_index_saves_with_and_without_an_mrn_and_rejects_a_duplicate(): void
    {
        Livewire::test(MemberIndex::class)
            ->set($this->patientFields('Asha', 'Rao', '9000000011', 'HOSP-11'))
            ->call('savePatient')
            ->assertHasNoErrors();

        $this->assertSame('HOSP-11', Persons::query()->where('mobile', '9000000011')->value('mrn'));

        Livewire::test(MemberIndex::class)
            ->set($this->patientFields('Meera', 'Nair', '9000000012', ''))
            ->call('savePatient')
            ->assertHasNoErrors();

        $this->assertNull(Persons::query()->where('mobile', '9000000012')->value('mrn'));

        Livewire::test(MemberIndex::class)
            ->set($this->patientFields('Ravi', 'Das', '9000000013', 'HOSP-11'))
            ->call('savePatient')
            ->assertHasErrors(['mrn']);

        $this->assertSame(0, Persons::query()->where('mobile', '9000000013')->count());

        $kept = Persons::query()->where('mobile', '9000000011')->first();
        $userId = HIPUser::query()->where('mobile_num', '9000000011')->value('id');

        Livewire::test(MemberIndex::class)
            ->call('openEditModal', $userId)
            ->call('savePatient')
            ->assertHasNoErrors();

        $this->assertSame('HOSP-11', $kept->fresh()->mrn);
    }

    public function test_receptionist_patient_form_saves_with_and_without_an_mrn_and_rejects_a_duplicate(): void
    {
        Livewire::test(Index::class)
            ->set($this->patientFields('Asha', 'Rao', '9000000021', 'CLINIC-21'))
            ->call('saveMember')
            ->assertHasNoErrors();

        $this->assertSame('CLINIC-21', Persons::query()->where('mobile', '9000000021')->value('mrn'));

        Livewire::test(Index::class)
            ->set($this->patientFields('Meera', 'Nair', '9000000022', '   '))
            ->call('saveMember')
            ->assertHasNoErrors();

        $this->assertNull(Persons::query()->where('mobile', '9000000022')->value('mrn'));

        Livewire::test(Index::class)
            ->set($this->patientFields('Ravi', 'Das', '9000000023', 'CLINIC-21'))
            ->call('saveMember')
            ->assertHasErrors(['mrn']);

        $this->assertSame(0, Persons::query()->where('mobile', '9000000023')->count());

        $kept = Persons::query()->where('mobile', '9000000021')->first();

        Livewire::test(Index::class)
            ->call('openEditModal', $kept->id)
            ->call('saveMember')
            ->assertHasNoErrors();

        $this->assertSame('CLINIC-21', $kept->fresh()->mrn);
    }

    private function account(string $firstName, string $mobile, string $email): array
    {
        return [
            'firstName' => $firstName,
            'lastName' => 'Rao',
            'mobile' => $mobile,
            'email' => $email,
            'gender' => 'Female',
            'dob' => '1990-01-15',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function patientFields(string $firstName, string $lastName, string $mobile, string $mrn): array
    {
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'mobile' => $mobile,
            'dob' => '1990-01-15',
            'gender' => 'Female',
            'blood_group' => 'O+',
            'hospital_id' => 1,
            'relationship' => 'Self',
            'email' => strtolower($firstName).'@example.com',
            'mrn' => $mrn,
        ];
    }

    private function registerSqliteRegexp(): void
    {
        DB::connection()->getPdo()->sqliteCreateFunction('regexp', function ($pattern, $value): int {
            if ($pattern === null || $value === null) {
                return 0;
            }

            return preg_match('/'.str_replace('/', '\\/', (string) $pattern).'/', (string) $value) === 1 ? 1 : 0;
        }, 2);
    }

    private function bootSchema(): void
    {
        Schema::dropIfExists('persons');
        Schema::dropIfExists('doctor_bookings');
        Schema::dropIfExists('hospitals');
        Schema::dropIfExists('healthinpocket_users');
        Schema::dropIfExists('organizations');

        Schema::create('organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('healthinpocket_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('hip_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('pending_email')->nullable();
            $table->string('mobile_num')->nullable();
            $table->string('password')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('otp')->nullable();
            $table->timestamp('otp_expires')->nullable();
            $table->string('organization_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->integer('profile_update')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('blood_group')->nullable();
            $table->unsignedBigInteger('preferred_branch_id')->nullable();
            $table->string('emergency_contact_person_name')->nullable();
            $table->string('emergency_contact_person_phone')->nullable();
            $table->string('emergency_contact_person_relationship')->nullable();
            $table->string('house_number')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('profile_image')->nullable();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('hospitals', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('organization_id')->nullable();
            $table->unsignedBigInteger('diagnostic_center_id')->nullable();
        });

        DB::table('hospitals')->insert([
            'id' => 1,
            'name' => 'City Hospital',
        ]);

        Schema::create('doctor_bookings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('patient_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->date('booking_date')->nullable();
        });

        Schema::create('persons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('mrn', 64)->nullable()->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable()->index();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->uuid('hip_user_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('relationship')->nullable();
            $table->string('relationship_other')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number')->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });
    }
};
