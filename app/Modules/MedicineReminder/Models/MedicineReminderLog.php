<?php

namespace App\Modules\MedicineReminder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineReminderLog extends Model
{
    protected $table = 'medicine_reminder_logs';

    protected $fillable = [
        'schedule_id',
        'status',
        'message',
        'payload',
        'execution_time',
    ];

    protected $casts = [
        'payload' => 'array',
        'execution_time' => 'integer',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MedicineReminderSchedule::class, 'schedule_id');
    }
}
