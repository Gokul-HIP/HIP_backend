<?php

namespace App\Modules\MedicineReminder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineNotificationLog extends Model
{
    protected $table = 'medicine_notification_logs';

    protected $fillable = [
        'schedule_id',
        'channel',
        'status',
        'response',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MedicineReminderSchedule::class, 'schedule_id');
    }
}
