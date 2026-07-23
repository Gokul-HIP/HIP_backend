<?php

namespace App\Modules\Workflow\DTO;

final class ExecutionGraph
{
    /**
     * @param  array<string, ExecutionNode>  $nodes
     * @param  array<int, ExecutionEdge>  $edges
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly array $nodes,
        public readonly array $edges,
        public readonly array $metadata = [],
        public readonly ?string $startNodeId = null,
        public readonly array $endNodeIds = [],
        public readonly array $branchNodeIds = [],
        public readonly bool $hasLoops = false,
    ) {}

    public function node(string $id): ?ExecutionNode
    {
        return $this->nodes[$id] ?? null;
    }

    /**
     * @return array<int, ExecutionEdge>
     */
    public function outgoingEdges(string $nodeId): array
    {
        return array_values(array_filter(
            $this->edges,
            fn (ExecutionEdge $edge) => $edge->source === $nodeId
        ));
    }

    /**
     * @return array<int, ExecutionEdge>
     */
    public function incomingEdges(string $nodeId): array
    {
        return array_values(array_filter(
            $this->edges,
            fn (ExecutionEdge $edge) => $edge->target === $nodeId
        ));
    }
}
