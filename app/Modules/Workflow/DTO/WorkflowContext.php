<?php

namespace App\Modules\Workflow\DTO;

final class WorkflowContext
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $variables
     */
    public function __construct(
        public readonly string $triggerType,
        public readonly array $payload = [],
        public array $variables = [],
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->payload, $key, $default);
    }

    public function setVariable(string $key, mixed $value): void
    {
        $this->variables[$key] = $value;
    }
}
