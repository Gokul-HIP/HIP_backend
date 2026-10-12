<?php

namespace App\Http\Requests\Api\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StorePharmacyOrderCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Enter a comment.',
            'body.max' => 'The comment may not be longer than 1000 characters.',
        ];
    }
}
