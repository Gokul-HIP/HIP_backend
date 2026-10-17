<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\SpecialitiesMaster;

class Speciality extends Model
{
    use Userstamps;
    protected $fillable = [
        'speciality_name',
        'speciality_code',
        'speciality_description',
        'speciality_logo',
        'department_category',
        'status',
        'hospital_id',
        'organization_id',
        'speciality_master_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [

        'qualifications' => 'array',
        'publications' => 'array',
        'achievements' => 'array',
        'speciality' => 'array',
        'assigned_procedure' => 'array',
        'assigned_speciality' => 'array',

    ];

    /**
     * Hospital department rows the disease chatbot may offer.
     * Callers must still drop masters whose normalizedDiseaseEntries() is empty.
     */
    public function scopeEnabledForChatbot(Builder $query, int $hospitalId): Builder
    {
        return $query
            ->where('hospital_id', $hospitalId)
            ->whereNotNull('speciality_master_id')
            ->where('status', 'active')
            ->whereHas('specialityMaster', function (Builder $master) {
                $master->where('status', 'active')
                    ->whereNotNull('diseases');
            });
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function specialityMaster()
    {
        return $this->belongsTo(SpecialitiesMaster::class, 'speciality_master_id');
    }
    
}
