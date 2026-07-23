<?php

namespace App\Modules\Workflow\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'organization_id' => ['sometimes', 'nullable', 'integer', 'exists:organizations,id'],
        ], WorkflowConfigurationRules::rules(required: false));
    }
}
