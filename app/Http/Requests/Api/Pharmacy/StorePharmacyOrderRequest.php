<?php

namespace App\Http\Requests\Api\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StorePharmacyOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
            'pharmacy_id' => ['nullable', 'integer', 'exists:pharmacies,id'],
            'patient_id' => ['nullable', 'uuid'],
            'note' => ['nullable', 'string', 'max:1000'],
            'delivery_name' => ['nullable', 'string', 'max:255'],
            'delivery_phone' => ['nullable', 'string', 'max:30'],
            'delivery_address' => ['nullable', 'string', 'max:2000'],
            'prescription' => ['required', 'file', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach (['status', 'payment_status', 'subtotal', 'discount', 'delivery_fee', 'tax', 'total', 'invoice_id'] as $field) {
                if ($this->exists($field)) {
                    $validator->errors()->add($field, 'This field cannot be set by the client.');
                }
            }

            $file = $this->file('prescription');

            if (! $file) {
                return;
            }

            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            $path = $file->getRealPath();
            $mime = '';

            if (is_string($path) && is_file($path)) {
                $detected = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
                $mime = is_string($detected) ? $detected : '';
            }

            if (! in_array($mime, $allowed, true)) {
                $validator->errors()->add('prescription', 'The prescription must be a JPG, PNG, WEBP, or PDF file.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'hospital_id.required' => 'Select a hospital.',
            'hospital_id.exists' => 'Hospital was not found.',
            'pharmacy_id.exists' => 'Pharmacy was not found.',
            'patient_id.uuid' => 'Patient is invalid.',
            'note.max' => 'The note may not be longer than 1000 characters.',
            'delivery_name.max' => 'The delivery name may not be longer than 255 characters.',
            'delivery_phone.max' => 'The delivery phone may not be longer than 30 characters.',
            'delivery_address.max' => 'The delivery address may not be longer than 2000 characters.',
            'prescription.required' => 'Upload the prescription.',
            'prescription.file' => 'Upload the prescription as a file.',
            'prescription.max' => 'The prescription may not be larger than 10 MB.',
        ];
    }
}
