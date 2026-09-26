<?php

namespace Tests\Unit;

use App\Models\UserDevice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserDevicePushDeliveryScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('user_devices');
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('device_type')->nullable();
            $table->string('device_id')->nullable();
            $table->text('fcm_token');
            $table->timestamps();
        });
    }

    public function test_for_push_delivery_excludes_automation_test_devices(): void
    {
        UserDevice::query()->create([
            'user_id' => 'user-1',
            'device_id' => 'BP4A.251205.006',
            'device_type' => 'android',
            'fcm_token' => str_repeat('a', 140).':real',
        ]);
        UserDevice::query()->create([
            'user_id' => 'user-1',
            'device_id' => 'automation-test-device-1',
            'device_type' => 'automation_test',
            'fcm_token' => str_repeat('b', 64),
        ]);

        $ids = UserDevice::query()
            ->forPushDelivery()
            ->where('user_id', 'user-1')
            ->pluck('device_id')
            ->all();

        $this->assertSame(['BP4A.251205.006'], $ids);
    }
}
