<?php

namespace App\Http\Requests\Api\Pharmacy;

use App\Enums\PharmacyOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPharmacyOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(PharmacyOrderStatus::class)],
            'person_id' => ['nullable', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'Status is invalid.',
            'person_id.uuid' => 'Patient is invalid.',
        ];
    }
}
