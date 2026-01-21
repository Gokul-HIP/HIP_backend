<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use Illuminate\Support\Facades\Storage;

class SpecialitiesMaster extends Model
{
    use Userstamps;

    protected $fillable = [
        'name',
        'code',
        'description',
        'display_image',
        'status',
        'created_by',
        'updated_by',
    ];

    public function procedureMasters()
    {
        return $this->hasMany(ProcedureMaster::class, 'speciality_master_id');
    }

    protected static function booted()
    {
        static::deleting(function ($record) {
            if ($record->display_image && Storage::disk('public')->exists($record->display_image)) {
                Storage::disk('public')->delete($record->display_image);
            }
        });
    }

}
