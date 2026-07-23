<?php

namespace App\Modules\Workflow\DTO;

final class ExecutionEdge
{
    public function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly string $target,
        public readonly ?string $sourceHandle = null,
        public readonly ?string $targetHandle = null,
        public readonly ?string $type = null,
    ) {}

    /**
     * @param  array<string, mixed>  $edge
     */
    public static function fromReactFlowEdge(array $edge): self
    {
        return new self(
            id: (string) ($edge['id'] ?? ''),
            source: (string) ($edge['source'] ?? ''),
            target: (string) ($edge['target'] ?? ''),
            sourceHandle: isset($edge['sourceHandle']) ? (string) $edge['sourceHandle'] : null,
            targetHandle: isset($edge['targetHandle']) ? (string) $edge['targetHandle'] : null,
            type: isset($edge['type']) ? (string) $edge['type'] : null,
        );
    }
}
