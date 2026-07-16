<?php

namespace App\Modules\MedicineReminder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicineWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['sometimes', 'nullable', 'integer', 'exists:organizations,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'configuration' => ['sometimes', 'required', 'array'],
            'configuration.builderVersion' => ['required_with:configuration', 'string', 'max:50'],
            'configuration.reactFlowVersion' => ['required_with:configuration', 'string', 'max:50'],
            'configuration.viewport' => ['required_with:configuration', 'array'],
            'configuration.viewport.x' => ['required_with:configuration.viewport', 'numeric'],
            'configuration.viewport.y' => ['required_with:configuration.viewport', 'numeric'],
            'configuration.viewport.zoom' => ['required_with:configuration.viewport', 'numeric', 'gt:0'],
            'configuration.nodes' => ['required_with:configuration', 'array'],
            'configuration.nodes.*.id' => ['required', 'string', 'max:255'],
            'configuration.nodes.*.type' => ['required', 'string', 'max:255'],
            'configuration.nodes.*.position' => ['required', 'array'],
            'configuration.nodes.*.position.x' => ['required', 'numeric'],
            'configuration.nodes.*.position.y' => ['required', 'numeric'],
            'configuration.nodes.*.data' => ['required', 'array'],
            'configuration.edges' => ['required_with:configuration', 'array'],
            'configuration.edges.*.id' => ['required', 'string', 'max:255'],
            'configuration.edges.*.source' => ['required', 'string', 'max:255'],
            'configuration.edges.*.target' => ['required', 'string', 'max:255'],
            'configuration.edges.*.type' => ['nullable', 'string', 'max:255'],
            'configuration.metadata' => ['sometimes', 'array'],
        ];
    }
}
