<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Diagnostic;
use App\Models\Organization;
use App\Models\DiagnosticLabTest;
use App\Models\Hospital;

class DiagnosticPackage extends Model
{
    use Userstamps;
    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'discount',
        'weight',
        'image',
        'status',
        'lab_tests',
        'diagnostic_id',
        'organization_id',
    ];

    protected $casts = [
        'lab_tests' => 'array',
    ];

    public function diagnostic()
    {
        return $this->belongsTo(Diagnostic::class, 'diagnostic_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function getLabTestsListAttribute()
    {
        $ids = is_array($this->lab_tests) ? $this->lab_tests : json_decode($this->lab_tests ?? '[]', true);

        return DiagnosticLabTest::whereIn('id', $ids)->select('id', 'test_name')->get();
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }
}
