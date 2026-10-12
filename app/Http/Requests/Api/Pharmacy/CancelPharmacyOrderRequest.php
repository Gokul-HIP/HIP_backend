<?php

namespace App\Http\Requests\Api\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class CancelPharmacyOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.max' => 'The reason may not be longer than 1000 characters.',
        ];
    }
}
