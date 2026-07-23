<?php

namespace App\Modules\Workflow\Services\Runtime;

use App\Modules\Workflow\Contracts\NodeExecutorInterface;
use InvalidArgumentException;

class NodeExecutorRegistry
{
    /** @var array<string, NodeExecutorInterface> */
    protected array $executors = [];

    public function register(NodeExecutorInterface $executor): void
    {
        $this->executors[$executor->type()] = $executor;
    }

    public function get(string $nodeType): NodeExecutorInterface
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
     * @return array<string, NodeExecutorInterface>
     */
    public function all(): array
    {
        return $this->executors;
    }
}
