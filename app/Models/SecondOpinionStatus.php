<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class SecondOpinionStatus extends Model
{
    use Userstamps;

    protected $fillable = [
        'second_opinion_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes',
        'notes_by',
    ];

    public function secondOpinion()
    {
        return $this->belongsTo(SecondOpinion::class, 'second_opinion_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(HIPUser::class, 'changed_by');
    }

    public function notesBy()
    {
        return $this->belongsTo(HIPUser::class, 'notes_by');
    }
}
