<?php

namespace App\Modules\Workflow;

use App\Modules\Workflow\NodeProcessors\Contracts\NodeProcessor;
use InvalidArgumentException;

class NodeProcessorRegistry
{
    /** @var array<string, NodeProcessor> */
    protected array $executors = [];

    public function register(NodeProcessor $executor): void
    {
        $this->executors[$executor->type()] = $executor;
    }

    public function get(string $nodeType): NodeProcessor
    {
        $normalized = \App\Modules\Workflow\Support\NodeTypeNormalizer::normalize($nodeType);

        if (! isset($this->executors[$normalized])) {
            throw new InvalidArgumentException("No executor registered for node type: {$nodeType}");
        }

        return $this->executors[$normalized];
    }

    public function has(string $nodeType): bool
    {
        $normalized = \App\Modules\Workflow\Support\NodeTypeNormalizer::normalize($nodeType);

        return isset($this->executors[$normalized]);
    }

    /**
     * @return array<string, NodeProcessor>
     */
    public function all(): array
    {
        return $this->executors;
    }
}
