<?php

namespace App\Modules\Workflow\DTO;

use App\Modules\Workflow\Support\NodeTypeNormalizer;

final class ExecutionNode
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $position
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $nodeType,
        public readonly array $data = [],
        public readonly array $position = [],
    ) {}

    /**
     * @param  array<string, mixed>  $node
     */
    public static function fromReactFlowNode(array $node): self
    {
        $data = is_array($node['data'] ?? null) ? $node['data'] : [];
        $rawNodeType = (string) ($data['nodeType'] ?? $node['type'] ?? '');

        return new self(
            id: (string) ($node['id'] ?? ''),
            type: (string) ($node['type'] ?? 'workflow'),
            nodeType: NodeTypeNormalizer::normalize($rawNodeType),
            data: $data,
            position: is_array($node['position'] ?? null) ? $node['position'] : [],
        );
    }
}
