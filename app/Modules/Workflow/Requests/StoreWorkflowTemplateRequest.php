<?php

namespace App\Modules\Workflow\Requests;

use App\Modules\Workflow\Enums\WorkflowStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkflowTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:workflow_templates,slug'],
            'description' => ['nullable', 'string'],
            'module' => ['required', 'string', 'max:100'],
            'trigger_type' => ['nullable', 'string', 'max:100'],
            'trigger_label' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'definition' => ['required', 'array'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(array_column(WorkflowStatus::cases(), 'value'))],
        ];
    }
}
