<?php

namespace App\Modules\MedicineReminder\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\MedicineReminder\Models\MedicineReminderSchedule */
class MedicineReminderScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'patient_id' => $this->patient_id,
            'prescription_id' => $this->prescription_id,
            'prescription_item_id' => $this->prescription_item_id,
            'medicine_id' => $this->medicine_id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'status' => $this->status,
            'retry_count' => $this->retry_count,
            'next_retry_at' => $this->next_retry_at?->toIso8601String(),
            'channels' => $this->channels,
            'message_template' => $this->message_template,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
