<?php

namespace App\Modules\Workflow\Requests;

final class WorkflowConfigurationRules
{
    /** @return array<string, mixed> */
    public static function rules(bool $required = true): array
    {
        $configurationRule = $required ? ['required', 'array'] : ['sometimes', 'required', 'array'];

        return [
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'name' => $required ? ['required', 'string', 'max:255'] : ['sometimes', 'required', 'string', 'max:255'],
            'configuration' => $configurationRule,
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
            'created_by' => ['nullable', 'uuid'],
        ];
    }
}
