<?php

namespace App\Http\Requests\Api\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class ListPharmaciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'hospital_id.required' => 'Select a hospital.',
            'hospital_id.integer' => 'Hospital is invalid.',
            'hospital_id.exists' => 'Hospital was not found.',
        ];
    }
}
