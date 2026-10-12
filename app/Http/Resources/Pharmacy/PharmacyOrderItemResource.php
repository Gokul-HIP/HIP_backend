<?php

namespace App\Http\Resources\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacyOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'strength' => $this->strength,
            'dosage_form' => $this->dosage_form,
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'duration' => $this->duration,
            'when_to_take' => $this->when_to_take,
            'special_instruction' => $this->special_instruction,
            'qty' => (int) $this->qty,
            'unit_price' => (float) $this->unit_price,
            'line_total' => (float) $this->line_total,
        ];
    }
}
