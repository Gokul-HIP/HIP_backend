<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Hospital;
use App\Models\Pharmacy;
use App\Models\Diagnostic;
use App\Models\DiagnosticLabTest;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Procedure;
use App\Models\Speciality;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Organization extends Model
{
    use HasUuids;
    use Userstamps;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'name',
        'city',
        'address',
        'logo',
        'status',
        'created_by',
        'updated_by',
    ];

    public function hospitals(){

        return $this->hasMany(Hospital::class,'organization_id');

    }

    public function pharmacys(){

        return $this->hasMany(Pharmacy::class,'organization_id');

    }

    public function diagnostics(){

        return $this->hasMany(Diagnostic::class,'organization_id');

    }

    public function labTests()
    {
        return $this->hasMany(DiagnosticLabTest::class, 'organization_id');
    }

    public function procedures()
    {
        return $this->hasMany(Procedure::class, 'organization_id');
    }

    public function specialities()
    {
        return $this->hasMany(Speciality::class, 'organization_id');
    }

    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'organization_id');
    }

}
