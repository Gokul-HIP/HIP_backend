<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class ProcedureBooking extends Model
{
    use Userstamps;

    protected $fillable = [
        'name',
        'mobile_number',
        'speciality_master_id',
        'procedure_id',
        'hospital_id',
    ];

    public function procedure()
    {
        return $this->belongsTo(Procedure::class, 'procedure_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

}
