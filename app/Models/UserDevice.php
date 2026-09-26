<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HIPUser;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id', 
        'fcm_token', 
        'device_type',
        'device_id'
    ];

    public function user()
    {
        return $this->belongsTo(HIPUser::class);
    }

    /**
     * Devices that should receive real member/patient push.
     * `automation:test` can leave automation-test-device-* rows on the configured user.
     */
    public function scopeForPushDelivery($query)
    {
        return $query
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->where(function ($nested) {
                $nested->whereNull('device_type')
                    ->orWhere('device_type', '!=', 'automation_test');
            })
            ->where(function ($nested) {
                $nested->whereNull('device_id')
                    ->orWhere('device_id', 'not like', 'automation-test-device%');
            });
    }
}
