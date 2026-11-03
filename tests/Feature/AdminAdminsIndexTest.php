<?php

namespace Tests\Feature;

use App\Livewire\Admin\Admins\Index;
use App\Models\HIPUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAdminsIndexTest extends TestCase
{
    private HIPUser $admin;

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
                'hip_id' => 'HIP00001',
                'first_name' => 'John',
                'last_name' => 'Admin',
                'email' => 'john.admin@example.com',
                'mobile_num' => '9876543210',
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]);
        });

        $this->admin->assignRole($role);
    }

    private function bootSchema(): void
    {
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('healthinpocket_users');

        Schema::create('healthinpocket_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('hip_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('pending_email')->nullable();
            $table->string('mobile_num')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
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
    }

    public function test_component_renders_and_retains_elements_after_livewire_update(): void
    {
        $this->actingAs($this->admin, 'filament');

        $component = Livewire::test(Index::class);

        // Assert initial render displays heading, subtitle, clear button, and admin rows
        $component->assertSee('Admins')
            ->assertSee('Accounts with a password. App patients are listed under Patients.')
            ->assertSee('Clear')
            ->assertSee('John Admin')
            ->assertSee('john.admin@example.com');

        // Set filter then clear it (Livewire update cycle)
        $component->set('roleFilter', 'super-admin-hip')
            ->assertSee('Admins')
            ->assertSee('Clear')
            ->assertSee('John Admin')
            ->call('clearFilters')
            ->assertSee('Admins')
            ->assertSee('Clear')
            ->assertSee('John Admin')
            ->assertSee('john.admin@example.com');

        // Search update cycle
        $component->set('search', 'John')
            ->assertSee('Admins')
            ->assertSee('Clear')
            ->assertSee('John Admin')
            ->call('clearFilters')
            ->assertSee('Admins')
            ->assertSee('Clear')
            ->assertSee('John Admin');
    }

    public function test_rendered_html_does_not_contain_easy_list_ad_blocked_classes(): void
    {
        $this->actingAs($this->admin, 'filament');

        $component = Livewire::test(Index::class);
        $html = $component->html();

        // Must not contain any cosmetic filter class names matched by ad blockers
        $this->assertStringNotContainsString('ad-header', $html);
        $this->assertStringNotContainsString('ad-title', $html);
        $this->assertStringNotContainsString('ad-subtitle', $html);
        $this->assertStringNotContainsString('ad-btn', $html);
        $this->assertStringNotContainsString('ad-table', $html);
        $this->assertStringNotContainsString('ad-filters', $html);
        $this->assertStringNotContainsString('ad-kpis', $html);

        // Regex check: ensure no class attribute contains a token starting with "ad-"
        $this->assertDoesNotMatchRegularExpression('/\bclass="[^"]*\bad-[a-zA-Z0-9-]/', $html);

        // Must contain safe namespace prefixed classes
        $this->assertStringContainsString('hip-admin-header', $html);
        $this->assertStringContainsString('hip-admin-title', $html);
        $this->assertStringContainsString('hip-admin-subtitle', $html);
        $this->assertStringContainsString('hip-admin-btn', $html);
        $this->assertStringContainsString('hip-admin-table', $html);
        $this->assertStringContainsString('hip-admin-filters', $html);
        $this->assertStringContainsString('hip-admin-kpis', $html);
    }
}
