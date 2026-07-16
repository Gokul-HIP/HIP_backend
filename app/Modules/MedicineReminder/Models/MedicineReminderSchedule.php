<?php

namespace App\Modules\MedicineReminder\Models;

use App\Models\Persons;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineReminderSchedule extends Model
{
    protected $table = 'medicine_reminder_schedules';

    protected $fillable = [
        'workflow_id',
        'patient_id',
        'prescription_id',
        'prescription_item_id',
        'medicine_id',
        'scheduled_at',
        'status',
        'retry_count',
        'next_retry_at',
        'channels',
        'message_template',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'channels' => 'array',
        'retry_count' => 'integer',
        'prescription_item_id' => 'integer',
        'medicine_id' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(MedicineWorkflow::class, 'workflow_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Persons::class, 'patient_id');
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(MedicineReminderLog::class, 'schedule_id');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(MedicineNotificationLog::class, 'schedule_id');
    }
}
