<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\HomePageController;
use App\Models\HIPUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HomepageAdsResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
    }

    public function test_homepage_ads_are_scoped_filtered_sorted_and_shaped_for_the_response(): void
    {
        $now = now();
        $this->createAd(1, 7, 'high', 70, 'image', $now->copy()->addDay());
        $this->createAd(2, 7, 'high', 60, 'video', $now->copy()->addDay());
        $this->createAd(3, 7, 'medium', 100, 'image', $now->copy()->addDay());
        $this->createAd(4, 7, 'high', 100, 'image', $now->copy()->subDay());
        $this->createAd(5, 8, 'high', 100, 'image', $now->copy()->addDay());
        $this->createAd(6, 7, 'high', 100, 'image', $now->copy()->addDay(), 'draft');
        $this->createAd(7, 7, 'high', 100, 'audio', $now->copy()->addDay());
        $this->createAd(8, null, 'low', 99, 'image', $now->copy()->addDay());
        DB::table('ad_placements')->insert([
            ['ad_id' => 1, 'placement_type' => 'home_banner'],
            ['ad_id' => 1, 'placement_type' => 'overlay'],
        ]);

        $controller = app(HomePageController::class);
        $method = new \ReflectionMethod($controller, 'fetchHomepageAdsData');
        $ads = $method->invoke($controller, 7);

        $this->assertSame([1, 2, 3, 8], array_column($ads, 'id'));
        $this->assertSame(url('storage/ads/1.jpg'), $ads[0]['image_url']);
        $this->assertNull($ads[0]['video_url']);
        $this->assertNull($ads[1]['image_url']);
        $this->assertSame(url('storage/ads/2.mp4'), $ads[1]['video_url']);
        $this->assertSame('high', $ads[0]['priority_type']);
        $this->assertSame(70, $ads[0]['priority']);
        $this->assertSame('external', $ads[0]['redirect_type']);
        $this->assertSame('https://example.test/ad/1', $ads[0]['redirect_url']);
        $this->assertSame(['home_banner', 'overlay'], $ads[0]['placements']);
    }

    public function test_membership_plan_uses_the_user_profile_empty_payload(): void
    {
        $user = new HIPUser;
        $user->setAttribute('id', 'profile-user');
        $controller = app(HomePageController::class);
        $method = new \ReflectionMethod($controller, 'buildProfileMembershipPlan');

        $this->assertSame([
            'has_active_plan' => false,
            'status_label' => null,
            'plan_name' => null,
            'plan_badge' => null,
            'valid_until' => null,
            'valid_until_label' => 'VALID UNTIL',
            'subscription_id' => null,
            'package_id' => null,
            'end_date' => null,
        ], $method->invoke($controller, $user));
    }

    private function createAd(
        int $id,
        ?int $hospitalId,
        string $priorityType,
        int $priority,
        string $mediaType,
        $endDate,
        string $status = 'active'
    ): void {
        DB::table('ads')->insert([
            'id' => $id,
            'title' => 'Ad '.$id,
            'description' => 'Description '.$id,
            'media_type' => $mediaType,
            'media_url' => 'ads/'.$id.($mediaType === 'video' ? '.mp4' : '.jpg'),
            'hospital_id' => $hospitalId,
            'redirect_type' => 'external',
            'redirect_url' => 'https://example.test/ad/'.$id,
            'priority_type' => $priorityType,
            'priority' => $priority,
            'status' => $status,
            'start_date' => now()->subDay(),
            'end_date' => $endDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTables(): void
    {
        Schema::create('ads', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('media_type')->nullable();
            $table->string('media_url')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('redirect_type')->nullable();
            $table->string('redirect_url')->nullable();
            $table->string('priority_type')->nullable();
            $table->integer('priority')->default(0);
            $table->string('status')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->timestamps();
        });
        Schema::create('ad_placements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ad_id')->nullable();
            $table->string('placement_type')->nullable();
        });
        Schema::create('family_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
        Schema::create('user_family_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('hip_user_id');
            $table->unsignedBigInteger('family_package_id');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }
}
