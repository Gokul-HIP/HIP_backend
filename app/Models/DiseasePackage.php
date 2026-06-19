<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\DiagnosticLabTest;
use App\Models\Diagnostic;
use App\Models\Organization;
use App\Models\SpecialitiesMaster;

class DiseasePackage extends Model
{
    use Userstamps;

    protected $fillable = [
        'name',
        'disease_id',
        'code',
        'description',
        'price',
        'discount',
        'weight',
        'image',
        'status',
        'lab_tests',
        'is_home_service',
        'diagnostic_id',
        'organization_id',
    ];

    protected $casts = [
        'lab_tests' => 'array',
        'is_home_service' => 'boolean',
    ];

    public function diagnostic()
    {
        return $this->belongsTo(Diagnostic::class, 'diagnostic_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function disease()
    {
        return $this->belongsTo(SpecialitiesMaster::class, 'disease_id');
    }

    public function speciality()
    {
        return $this->belongsTo(SpecialitiesMaster::class, 'disease_id');
    }

    public function getLabTestsListAttribute()
    {
        $ids = is_array($this->lab_tests) ? $this->lab_tests : json_decode($this->lab_tests ?? '[]', true);

        return DiagnosticLabTest::whereIn('id', $ids)->select('id', 'test_name')->get();
    }
}
