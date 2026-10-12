<?php

namespace App\Http\Resources\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacySummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'opening_time' => $this->opening_time,
            'closing_time' => $this->closing_time,
            'logo' => $this->logo,
        ];
    }
}
