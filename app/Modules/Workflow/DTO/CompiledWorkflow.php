<?php

namespace App\Modules\Workflow\DTO;

final class CompiledWorkflow
{
    /**
     * @param  array<string, mixed>  $definition
     */
    public function __construct(
        public readonly array $definition,
        public readonly ExecutionGraph $graph,
        public readonly string $builderVersion,
        public readonly string $reactFlowVersion,
    ) {}
}
