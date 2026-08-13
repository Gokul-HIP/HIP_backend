<?php

namespace App\Modules\Workflow\Requests;

use App\Modules\Workflow\Enums\WorkflowStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkflowTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'organization_id' => ['sometimes', 'nullable', 'integer', 'exists:organizations,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('workflow_templates', 'slug')->ignore($id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'module' => ['sometimes', 'required', 'string', 'max:100'],
            'trigger_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'trigger_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'definition' => ['sometimes', 'required', 'array'],
            'thumbnail' => ['sometimes', 'nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'required', Rule::in(array_column(WorkflowStatus::cases(), 'value'))],
        ];
    }
}
