<?php

namespace Tests\Feature;

use App\Models\HIPUser;
use App\Models\Persons;
use App\Services\RewardTierService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthLoginExistingOnlyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->fail('Auth login tests must use the sqlite testing database.');
        }

        app('cache')->flush();

        $this->bootSchema();
        $this->registerSqliteRegexp();

        $this->mock(RewardTierService::class, function ($mock): void {
            $mock->shouldReceive('enrollUserOnRegister')->andReturnNull();
        });
    }

    public function test_unknown_mobile_returns_422_with_registration_message_and_stores_nothing(): void
    {
        $usersBefore = DB::table('healthinpocket_users')->count();
        $personsBefore = DB::table('persons')->count();
        $tokensBefore = DB::table('personal_access_tokens')->count();

        $response = $this->postJson('/api/auth/login', [
            'mobile' => '9999999999',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status_code' => 422,
                'message'     => 'Please register in the hospital',
            ]);

        $this->assertDatabaseCount('healthinpocket_users', $usersBefore);
        $this->assertDatabaseCount('persons', $personsBefore);
        $this->assertDatabaseCount('personal_access_tokens', $tokensBefore);
        $this->assertDatabaseMissing('healthinpocket_users', [
            'mobile_num' => '9999999999',
        ]);
        $this->assertDatabaseMissing('persons', [
            'mobile' => '9999999999',
        ]);
    }

    public function test_existing_hip_user_mobile_returns_200_with_otp_and_user_id(): void
    {
        $user = HIPUser::create([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'mobile_num' => '9876543210',
            'is_active'  => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'mobile' => '9876543210',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message'     => 'OTP Send Successfully',
                'user_id'     => $user->id,
            ]);

        $otp = $response->json('otp');
        $this->assertNotNull($otp);

        $fresh = $user->fresh();
        $this->assertSame((string) $otp, (string) $fresh->otp);
        $this->assertNotNull($fresh->otp_expires);
    }

    public function test_mobile_only_in_persons_creates_and_links_hip_user_without_duplicate_persons(): void
    {
        $person = Persons::create([
            'first_name'  => 'Jane',
            'last_name'   => 'Smith',
            'email'       => 'jane@example.com',
            'mobile'      => '9876543211',
            'gender'      => 'Female',
            'dob'         => '1990-05-15',
            'is_primary'  => true,
            'hip_user_id' => null,
        ]);
        $person->update(['parent_id' => $person->id]);

        $usersBefore = DB::table('healthinpocket_users')->count();
        $personsBefore = DB::table('persons')->count();

        $response = $this->postJson('/api/auth/login', [
            'mobile' => '9876543211',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message'     => 'OTP Send Successfully',
            ]);

        $this->assertDatabaseCount('healthinpocket_users', $usersBefore + 1);
        $this->assertDatabaseCount('persons', $personsBefore);

        $createdUser = HIPUser::where('mobile_num', '9876543211')->first();
        $this->assertNotNull($createdUser);
        $this->assertSame($createdUser->id, $response->json('user_id'));
        $this->assertSame('Jane', $createdUser->first_name);
        $this->assertSame('Smith', $createdUser->last_name);
        $this->assertSame('jane@example.com', $createdUser->email);
        $this->assertSame('Female', $createdUser->gender);
        $this->assertSame('1990-05-15', Carbon::parse($createdUser->dob)->toDateString());
        $this->assertNull($createdUser->password);

        $this->assertSame($createdUser->id, $person->fresh()->hip_user_id);
    }

    public function test_family_members_sharing_one_mobile_in_persons_creates_one_hip_user_and_links_primary(): void
    {
        $primary = Persons::create([
            'first_name'   => 'Father',
            'last_name'    => 'Kumar',
            'email'        => 'father@example.com',
            'mobile'       => '9876543212',
            'gender'       => 'Male',
            'dob'          => '1980-01-01',
            'is_primary'   => true,
            'relationship' => 'Self',
            'hip_user_id'  => null,
        ]);
        $primary->update(['parent_id' => $primary->id]);

        $child = Persons::create([
            'first_name'   => 'Child',
            'last_name'    => 'Kumar',
            'email'        => null,
            'mobile'       => '9876543212',
            'gender'       => 'Male',
            'dob'          => '2015-06-01',
            'is_primary'   => false,
            'parent_id'    => $primary->id,
            'relationship' => 'Child',
            'hip_user_id'  => null,
        ]);

        $usersBefore = DB::table('healthinpocket_users')->count();
        $personsBefore = DB::table('persons')->count();

        $response = $this->postJson('/api/auth/login', [
            'mobile' => '9876543212',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message'     => 'OTP Send Successfully',
            ]);

        $this->assertDatabaseCount('healthinpocket_users', $usersBefore + 1);
        $this->assertDatabaseCount('persons', $personsBefore);

        $createdUser = HIPUser::where('mobile_num', '9876543212')->first();
        $this->assertNotNull($createdUser);
        $this->assertSame('Father', $createdUser->first_name);

        $this->assertSame($createdUser->id, $primary->fresh()->hip_user_id);
        $this->assertNull($child->fresh()->hip_user_id);
    }

    public function test_mobile_sent_with_different_format_spaces_or_plus_91_still_finds_existing_user(): void
    {
        $user = HIPUser::create([
            'first_name' => 'Formatted',
            'last_name'  => 'User',
            'mobile_num' => '9876543213',
            'is_active'  => true,
        ]);

        // Spaces inside mobile
        $resp1 = $this->postJson('/api/auth/login', ['mobile' => '98765 43213']);
        $resp1->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'user_id'     => $user->id,
            ]);

        // +91 prefix
        $resp2 = $this->postJson('/api/auth/login', ['mobile' => '+919876543213']);
        $resp2->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'user_id'     => $user->id,
            ]);

        // +91 with space
        $resp3 = $this->postJson('/api/auth/login', ['mobile' => '+91 9876543213']);
        $resp3->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'user_id'     => $user->id,
            ]);

        // Persons-only with +91 format input
        $person = Persons::create([
            'first_name'  => 'PersonOnly',
            'last_name'   => 'Format',
            'mobile'      => '9876543214',
            'is_primary'  => true,
            'hip_user_id' => null,
        ]);
        $person->update(['parent_id' => $person->id]);

        $resp4 = $this->postJson('/api/auth/login', ['mobile' => '+91 98765 43214']);
        $resp4->assertStatus(200);

        $created = HIPUser::where('mobile_num', '9876543214')->first();
        $this->assertNotNull($created);
        $this->assertSame($created->id, $resp4->json('user_id'));
    }

    public function test_inactive_account_follows_the_current_rule(): void
    {
        $inactiveUser = HIPUser::create([
            'first_name' => 'Inactive',
            'last_name'  => 'Patient',
            'mobile_num' => '9876543215',
            'is_active'  => false,
        ]);

        // When enforce_active is true, 403 is returned
        config(['hip.enforce_active' => true]);

        $responseBlocked = $this->postJson('/api/auth/login', [
            'mobile' => '9876543215',
        ]);

        $responseBlocked->assertStatus(403)
            ->assertJson([
                'status_code' => 403,
                'message'     => 'Your account has been deactivated. Please contact support.',
            ]);

        // When enforce_active is false, login succeeds
        config(['hip.enforce_active' => false]);

        $responseAllowed = $this->postJson('/api/auth/login', [
            'mobile' => '9876543215',
        ]);

        $responseAllowed->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message'     => 'OTP Send Successfully',
                'user_id'     => $inactiveUser->id,
            ]);
    }

    public function test_persons_only_login_skips_email_when_already_in_use_by_another_hip_user(): void
    {
        // Another HIPUser already has this email
        HIPUser::create([
            'first_name' => 'Existing',
            'last_name'  => 'User',
            'email'      => 'shared@example.com',
            'mobile_num' => '9876543220',
            'is_active'  => true,
        ]);

        // A hospital-registered patient has the same email on their Persons chart
        $person = Persons::create([
            'first_name'  => 'New',
            'last_name'   => 'Patient',
            'email'       => 'shared@example.com',
            'mobile'      => '9876543221',
            'is_primary'  => true,
            'hip_user_id' => null,
        ]);
        $person->update(['parent_id' => $person->id]);

        $response = $this->postJson('/api/auth/login', [
            'mobile' => '9876543221',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message'     => 'OTP Send Successfully',
            ]);

        $this->assertNotNull($response->json('otp'));

        // The new HIPUser is created with email as null (skipped duplicate)
        $createdUser = HIPUser::where('mobile_num', '9876543221')->first();
        $this->assertNotNull($createdUser);
        $this->assertNull($createdUser->email);
        $this->assertSame($createdUser->id, $person->fresh()->hip_user_id);
    }

    public function test_unexpected_exception_logs_error_and_returns_generic_422_message(): void
    {
        $this->mock(\App\Services\AuthService::class, function ($mock): void {
            $mock->shouldReceive('login')->andThrow(new \RuntimeException('Database disk failure'));
        });

        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->once()
            ->with('Login error', \Mockery::on(function ($context): bool {
                return isset($context['error']) && $context['error'] === 'Database disk failure';
            }));

        $response = $this->postJson('/api/auth/login', [
            'mobile' => '9876543210',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status_code' => 422,
                'message'     => 'Unable to login. Please try again.',
            ]);
    }

    public function test_login_is_throttled_to_five_requests_per_minute(): void
    {
        HIPUser::create([
            'first_name' => 'Throttle',
            'last_name'  => 'User',
            'mobile_num' => '9876543230',
            'is_active'  => true,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'mobile' => '9876543230',
            ]);
            $response->assertStatus(200);
        }

        // 6th request within 1 minute should be blocked with 429
        $responseBlocked = $this->postJson('/api/auth/login', [
            'mobile' => '9876543230',
        ]);
        $responseBlocked->assertStatus(429);
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
        Schema::dropIfExists('doctor_bookings');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('persons');
        Schema::dropIfExists('healthinpocket_users');
        Schema::dropIfExists('organizations');

        Schema::create('organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
        });

        DB::table('organizations')->insert([
            'id'   => (string) Str::uuid(),
            'name' => 'Default Organization',
        ]);

        Schema::create('doctor_bookings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('patient_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->date('booking_date')->nullable();
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

        Schema::create('persons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('mrn')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('image')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->foreignUuid('hip_user_id')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('relationship')->nullable();
            $table->string('relationship_other')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number')->nullable();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('tokenable_type');
            $table->string('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
}
