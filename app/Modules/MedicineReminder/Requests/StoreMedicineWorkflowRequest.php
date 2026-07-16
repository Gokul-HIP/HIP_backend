<?php

namespace App\Modules\MedicineReminder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicineWorkflowRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'configuration' => ['required', 'array'],
            'configuration.builderVersion' => ['required', 'string', 'max:50'],
            'configuration.reactFlowVersion' => ['required', 'string', 'max:50'],
            'configuration.viewport' => ['required', 'array'],
            'configuration.viewport.x' => ['required', 'numeric'],
            'configuration.viewport.y' => ['required', 'numeric'],
            'configuration.viewport.zoom' => ['required', 'numeric', 'gt:0'],
            'configuration.nodes' => ['required', 'array'],
            'configuration.nodes.*.id' => ['required', 'string', 'max:255'],
            'configuration.nodes.*.type' => ['required', 'string', 'max:255'],
            'configuration.nodes.*.position' => ['required', 'array'],
            'configuration.nodes.*.position.x' => ['required', 'numeric'],
            'configuration.nodes.*.position.y' => ['required', 'numeric'],
            'configuration.nodes.*.data' => ['required', 'array'],
            'configuration.edges' => ['required', 'array'],
            'configuration.edges.*.id' => ['required', 'string', 'max:255'],
            'configuration.edges.*.source' => ['required', 'string', 'max:255'],
            'configuration.edges.*.target' => ['required', 'string', 'max:255'],
            'configuration.edges.*.type' => ['nullable', 'string', 'max:255'],
            'configuration.metadata' => ['sometimes', 'array'],
            'created_by' => ['nullable', 'uuid'],
        ];
    }
}
