<?php

namespace Tests\Feature;

use App\Livewire\Admin\Ads\AdManagement;
use App\Livewire\Admin\Ads\CreateAd as AdminCreateAd;
use App\Livewire\Admin\Ads\EditAd as AdminEditAd;
use App\Livewire\HospitalAdmin\Ads\CreateAd as HospitalCreateAd;
use App\Livewire\HospitalAdmin\Ads\EditAd as HospitalEditAd;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAdManagementDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
        Storage::fake('public');
    }

    public function test_delete_removes_related_records_and_keeps_shared_media_until_unused(): void
    {
        Storage::disk('public')->put('ads/shared.png', 'image-data');
        foreach ([1, 2] as $id) {
            DB::table('ads')->insert([
                'id' => $id,
                'title' => 'Ad '.$id,
                'media_url' => 'ads/shared.png',
                'priority' => 50,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('ad_placements')->insert(['ad_id' => 1, 'placement_type' => 'home_banner']);
        DB::table('ad_target_areas')->insert(['ad_id' => 1, 'location_master_id' => null]);
        DB::table('ad_impressions')->insert(['ad_id' => 1]);
        DB::table('ad_clicks')->insert(['ad_id' => 1]);

        Livewire::test(AdManagement::class)
            ->assertSee('Delete Ad')
            ->call('deleteAd', 1)
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('ads', ['id' => 1]);
        $this->assertDatabaseMissing('ad_placements', ['ad_id' => 1]);
        $this->assertDatabaseMissing('ad_target_areas', ['ad_id' => 1]);
        $this->assertDatabaseMissing('ad_impressions', ['ad_id' => 1]);
        $this->assertDatabaseMissing('ad_clicks', ['ad_id' => 1]);
        $this->assertTrue(Storage::disk('public')->exists('ads/shared.png'));

        Livewire::test(AdManagement::class)->call('deleteAd', 2);

        $this->assertDatabaseMissing('ads', ['id' => 2]);
        $this->assertFalse(Storage::disk('public')->exists('ads/shared.png'));
    }

    public function test_all_ad_forms_render_with_one_root_and_without_audience_targeting(): void
    {
        DB::table('ads')->insert([
            'id' => 1,
            'title' => 'Render test ad',
            'priority' => 50,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(AdminCreateAd::class)
            ->assertSee('Create Ad')
            ->assertDontSee('Audience Targeting');
        Livewire::test(AdminEditAd::class, ['id' => 1])
            ->assertSee('Edit Ad')
            ->assertDontSee('Audience Targeting');
        Livewire::test(HospitalCreateAd::class)
            ->assertSee('Create Ad')
            ->assertDontSee('Audience Targeting');
        Livewire::test(HospitalEditAd::class, ['id' => 1])
            ->assertSee('Edit Ad')
            ->assertDontSee('Audience Targeting');
    }

    private function createTables(): void
    {
        Schema::create('hospitals', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('status')->nullable();
        });
        Schema::create('location_masters', function (Blueprint $table): void {
            $table->id();
            $table->string('city')->nullable();
            $table->string('area')->nullable();
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
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('priority_type')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
        });
        foreach (['ad_placements', 'ad_target_areas', 'ad_impressions', 'ad_clicks'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                $table->id();
                $table->unsignedBigInteger('ad_id')->nullable();
                if ($tableName === 'ad_placements') {
                    $table->string('placement_type')->nullable();
                }
                if ($tableName === 'ad_target_areas') {
                    $table->unsignedBigInteger('location_master_id')->nullable();
                }
                $table->timestamps();
            });
        }
    }
}
