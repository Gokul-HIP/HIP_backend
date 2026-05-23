<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Persons extends Model
{
    use HasUuids;
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'persons';
    protected $fillable = [
        'first_name', 
        'last_name', 
        'email', 
        'mobile', 
        'gender', 
        'dob', 
        'image', 
        'parent_id', 
        'hip_user_id', 
        'is_primary',
        'relationship',
    ];

    public function hipUser()
    {
        return $this->belongsTo(HIPUser::class, 'hip_user_id');
    }
}
