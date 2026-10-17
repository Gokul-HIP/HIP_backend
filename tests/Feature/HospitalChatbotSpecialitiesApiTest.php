<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\SpecialitiesMaster;
use App\Models\Speciality;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HospitalChatbotSpecialitiesApiTest extends TestCase
{
    private static bool $schemaReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Chatbot specialities tests must use the sqlite testing database.');
        }

        $this->bootSchema();
        $this->withoutMiddleware(EnsureAccountIsActive::class);

        foreach (['specialities', 'specialities_masters', 'hospitals', 'healthinpocket_users'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    public function test_requires_bearer_auth(): void
    {
        $hospital = $this->hospital();

        $this->getJson('/api/hospital/'.$hospital->id.'/specialities')
            ->assertUnauthorized();
    }

    public function test_returns_404_when_hospital_is_missing(): void
    {
        Sanctum::actingAs($this->member());

        $this->getJson('/api/hospital/99999/specialities')
            ->assertNotFound()
            ->assertJsonPath('status', 404)
            ->assertJsonPath('data', []);
    }

    public function test_returns_only_enabled_specialities_with_diseases(): void
    {
        Sanctum::actingAs($this->member());

        $hospital = $this->hospital();
        $otherHospital = $this->hospital('Other Branch');

        $enabledMaster = $this->master('Cardiology', 'CARD', 'active', [
            ['name' => 'Asthma', 'symptoms' => ['wheeze']],
            ['name' => 'Angina'],
            ['name' => ''],
        ]);
        $emptyMaster = $this->master('Empty', 'EMPTY', 'active', []);
        $blankNameMaster = $this->master('Blank', 'BLANK', 'active', [
            ['name' => ''],
            ['symptoms' => ['x']],
        ]);
        $inactiveMaster = $this->master('Inactive Master', 'INACT', 'inactive', [
            ['name' => 'Flu'],
        ]);

        $this->department($hospital->id, $enabledMaster->id, 'active', 'Cardio Dept');
        $this->department($hospital->id, $emptyMaster->id, 'active', 'Empty Dept');
        $this->department($hospital->id, $blankNameMaster->id, 'active', 'Blank Dept');
        $this->department($hospital->id, $inactiveMaster->id, 'active', 'Inactive Master Dept');
        $this->department($hospital->id, $enabledMaster->id, 'inactive', 'Inactive Row');
        $this->department($hospital->id, null, 'active', 'No Master');
        $this->department($otherHospital->id, $enabledMaster->id, 'active', 'Other Hospital Cardio');

        $response = $this->getJson('/api/hospital/'.$hospital->id.'/specialities');

        $response->assertOk()
            ->assertJsonPath('status', 200)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.id', $enabledMaster->id)
            ->assertJsonPath('data.0.name', 'Cardiology')
            ->assertJsonPath('data.0.code', 'CARD')
            ->assertJsonPath('data.0.disease_count', 2);

        $this->assertNull($response->json('data.0.image'));
    }

    public function test_image_uses_master_display_image_basename(): void
    {
        Sanctum::actingAs($this->member());

        $hospital = $this->hospital();
        $master = $this->master('Derm', 'DERM', 'active', [
            ['name' => 'Eczema'],
        ], 'speciality/folder/derm.png');
        $this->department($hospital->id, $master->id, 'active', 'Derm Dept');

        $this->getJson('/api/hospital/'.$hospital->id.'/specialities')
            ->assertOk()
            ->assertJsonPath('data.0.image', url('storage/speciality/derm.png'));
    }

    public function test_existing_all_specialities_list_is_unchanged(): void
    {
        Sanctum::actingAs($this->member());

        $hospital = $this->hospital();
        $master = $this->master('Ortho', 'ORTHO', 'active', [
            ['name' => 'Fracture'],
        ]);
        $row = $this->department($hospital->id, $master->id, 'inactive', 'Ortho Dept');

        $legacy = $this->postJson('/api/hospital/all-specialities-list', [
            'hospital_id' => $hospital->id,
        ]);

        $legacy->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.id', $row->id)
            ->assertJsonPath('data.0.speciality_id', $master->id)
            ->assertJsonPath('data.0.name', 'Ortho Dept');

        $this->getJson('/api/hospital/'.$hospital->id.'/specialities')
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('data', []);
    }

    private function member(): HIPUser
    {
        return HIPUser::withoutEvents(fn () => HIPUser::query()->create([
            'email' => 'chatbot-spec@example.com',
            'first_name' => 'Chat',
            'last_name' => 'Bot',
            'hip_id' => 'HIPCHAT01',
            'is_active' => true,
            'password' => null,
        ]));
    }

    private function hospital(string $name = 'Test Hospital'): Hospital
    {
        return Hospital::withoutEvents(fn () => Hospital::query()->create([
            'name' => $name,
        ]));
    }

    private function master(
        string $name,
        string $code,
        string $status,
        array $diseases,
        ?string $displayImage = null
    ): SpecialitiesMaster {
        $master = new SpecialitiesMaster();
        $master->forceFill([
            'name' => $name,
            'code' => $code,
            'status' => $status,
            'diseases' => $diseases,
            'display_image' => $displayImage,
        ]);
        $master->save();

        return $master->fresh();
    }

    private function department(
        int $hospitalId,
        ?int $masterId,
        string $status,
        string $name
    ): Speciality {
        $row = new Speciality();
        $row->forceFill([
            'speciality_name' => $name,
            'speciality_code' => strtoupper(substr(md5($name.$hospitalId.(string) $masterId), 0, 8)),
            'department_category' => $name,
            'status' => $status,
            'hospital_id' => $hospitalId,
            'organization_id' => 1,
            'speciality_master_id' => $masterId,
        ]);
        $row->save();

        return $row->fresh();
    }

    private function bootSchema(): void
    {
        if (self::$schemaReady && Schema::hasTable('specialities')) {
            return;
        }

        Schema::dropIfExists('specialities');
        Schema::dropIfExists('specialities_masters');
        Schema::dropIfExists('hospitals');
        Schema::dropIfExists('healthinpocket_users');

        Schema::create('healthinpocket_users', function ($table) {
            $table->uuid('id')->primary();
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('password')->nullable();
            $table->string('hip_id')->nullable();
            $table->string('mobile_num')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hospitals', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('specialities_masters', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->json('diseases')->nullable();
            $table->string('department_name')->nullable();
            $table->string('department_image')->nullable();
            $table->unsignedBigInteger('legacy_department_id')->nullable();
            $table->string('display_image')->nullable();
            $table->string('status')->default('inactive');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('specialities', function ($table) {
            $table->id();
            $table->string('speciality_name');
            $table->string('speciality_code');
            $table->string('speciality_description')->nullable();
            $table->string('speciality_logo')->nullable();
            $table->string('department_category');
            $table->string('status')->default('inactive');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('speciality_master_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
        });

        self::$schemaReady = true;
    }
}
