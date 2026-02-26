<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class ProcedureMaster extends Model
{
    use Userstamps;
    protected $fillable = [
        'name',
        'duration',
        'speciality_master_id',
        'cost',
        'description',
        'status',
        'created_by',
        'updated_by',
        'image',
        'recovery_time',
        'success_rate',
        'hospitalization_days',
        'discount',
    ];

    public function specialityMaster()
    {
        return $this->belongsTo(SpecialitiesMaster::class, 'speciality_master_id');
    }

    public function procedure()
    {
        return $this->belongsTo(Procedure::class, 'procedure_id');
    }

}
