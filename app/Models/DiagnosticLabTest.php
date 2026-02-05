<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Diagnostic;
use App\Models\Organization;
use App\Models\MasterLabtestCategory;

class DiagnosticLabTest extends Model
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
        'diagnostic_id',
        'organization_id',
        'created_by',
        'updated_by',
    ];

    public function diagnostic()
    {
        return $this->belongsTo(Diagnostic::class, 'diagnostic_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function category()
    {
        return $this->belongsTo(MasterLabtestCategory::class, 'test_category');
    }
    
}
