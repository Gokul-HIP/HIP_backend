<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use Illuminate\Support\Facades\Storage;
use App\Models\MasterLabtestCategory;

class LabTestMaster extends Model
{
    use Userstamps;
    protected $fillable = [
        'test_name',
        'test_category',
        'test_code',
        'test_description',
        'test_price',
        'test_discount',
        'test_image',
        'test_status',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::deleting(function ($record) {
            if ($record->test_image && Storage::disk('public')->exists($record->test_image)) {
                Storage::disk('public')->delete($record->test_image);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(MasterLabtestCategory::class, 'test_category');
    }

}
