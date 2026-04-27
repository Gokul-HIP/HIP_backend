<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Concerns\HasUuids;

class HIPCard extends Model
{
    // use HasUuids;
    // public $incrementing = false;
    // protected $keyType = 'string';

    protected $fillable = [
        'patient_id',
        'hip_card_id',
        'first_name',
        'last_name',
        'phone_number',
        'gender',
        'paid_amount',
        'hip_points',
        'hip_points_used',
        'nfc_login_time',
    ];
}
