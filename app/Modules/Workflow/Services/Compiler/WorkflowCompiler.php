<?php

namespace App\Modules\Workflow\Services\Compiler;

use App\Modules\Workflow\Contracts\WorkflowCompilerInterface;
use App\Modules\Workflow\DTO\CompiledWorkflow;
use App\Modules\Workflow\DTO\ExecutionEdge;
use App\Modules\Workflow\DTO\ExecutionGraph;
use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\Support\NodeTypeNormalizer;
use InvalidArgumentException;

class WorkflowCompiler implements WorkflowCompilerInterface
{
    /**
     * @param  array<string, mixed>  $definition
     */
    public function compile(array $definition): CompiledWorkflow
    {
        $nodesRaw = $definition['nodes'] ?? null;
        $edgesRaw = $definition['edges'] ?? null;

        if (! is_array($nodesRaw) || ! is_array($edgesRaw)) {
            throw new InvalidArgumentException('Workflow definition must contain nodes and edges arrays.');
        }

        if ($nodesRaw === []) {
            throw new InvalidArgumentException('Workflow definition must contain at least one node.');
        }

        [$nodesRaw, $edgesRaw] = $this->stripFrontendOnlyNodes($nodesRaw, $edgesRaw);

        if ($nodesRaw === []) {
            throw new InvalidArgumentException('Workflow definition must contain at least one executable node.');
        }

        $nodes = [];
        foreach ($nodesRaw as $node) {
            if (! is_array($node)) {
                continue;
            }

            $executionNode = ExecutionNode::fromReactFlowNode($node);
            $executionNode = new ExecutionNode(
                id: $executionNode->id,
                type: $executionNode->type,
                nodeType: NodeTypeNormalizer::normalize($executionNode->nodeType),
                data: $executionNode->data,
                position: $executionNode->position,
            );

            if ($executionNode->id === '') {
                throw new InvalidArgumentException('Every workflow node must have an id.');
            }

            $nodes[$executionNode->id] = $executionNode;
        }

        $edges = [];
        foreach ($edgesRaw as $edge) {
            if (! is_array($edge)) {
                continue;
            }

            $executionEdge = ExecutionEdge::fromReactFlowEdge($edge);

            if ($executionEdge->source === '' || $executionEdge->target === '') {
                throw new InvalidArgumentException('Every workflow edge must have source and target.');
            }

            if (! isset($nodes[$executionEdge->source]) || ! isset($nodes[$executionEdge->target])) {
                throw new InvalidArgumentException("Edge references unknown node: {$executionEdge->id}");
            }

            $edges[] = $executionEdge;
        }

        $startNodeId = $this->resolveStartNodeId($nodes, $edges);
        $endNodeIds = $this->resolveEndNodeIds($nodes);
        $branchNodeIds = $this->resolveBranchNodeIds($nodes, $edges);
        $hasLoops = $this->detectLoops($nodes, $edges);

        $graph = new ExecutionGraph(
            nodes: $nodes,
            edges: $edges,
            metadata: [
                'node_count' => count($nodes),
                'edge_count' => count($edges),
            ],
            startNodeId: $startNodeId,
            endNodeIds: $endNodeIds,
            branchNodeIds: $branchNodeIds,
            hasLoops: $hasLoops,
        );

        return new CompiledWorkflow(
            definition: $definition,
            graph: $graph,
            builderVersion: (string) ($definition['builderVersion'] ?? '1'),
            reactFlowVersion: (string) ($definition['reactFlowVersion'] ?? 'unknown'),
        );
    }

    /**
     * @param  array<string, ExecutionNode>  $nodes
     * @param  array<int, ExecutionEdge>  $edges
     */
    protected function resolveStartNodeId(array $nodes, array $edges): string
    {
        $targets = array_map(fn (ExecutionEdge $edge) => $edge->target, $edges);

        foreach ($nodes as $node) {
            if (NodeTypeNormalizer::isTrigger($node->nodeType) && ! in_array($node->id, $targets, true)) {
                return $node->id;
            }
        }

        foreach ($nodes as $node) {
            if (NodeTypeNormalizer::isTrigger($node->nodeType)) {
                return $node->id;
            }
        }

        foreach ($nodes as $nodeId => $node) {
            if (! in_array($nodeId, $targets, true)) {
                return $nodeId;
            }
        }

        return array_key_first($nodes) ?? '';
    }

    /**
     * @param  array<string, ExecutionNode>  $nodes
     * @return array<int, string>
     */
    protected function resolveEndNodeIds(array $nodes): array
    {
        $endIds = [];

        foreach ($nodes as $node) {
            if (NodeTypeNormalizer::isEnd($node->nodeType)) {
                $endIds[] = $node->id;
            }
        }

        return $endIds;
    }

    /**
     * @param  array<string, ExecutionNode>  $nodes
     * @param  array<int, ExecutionEdge>  $edges
     * @return array<int, string>
     */
    protected function resolveBranchNodeIds(array $nodes, array $edges): array
    {
        $branchIds = [];

        foreach ($nodes as $node) {
            if (NodeTypeNormalizer::normalize($node->nodeType) === 'condition') {
                $branchIds[] = $node->id;
                continue;
            }

            $outgoing = array_filter($edges, fn (ExecutionEdge $edge) => $edge->source === $node->id);

            if (count($outgoing) > 1) {
                $branchIds[] = $node->id;
            }
        }

        return array_values(array_unique($branchIds));
    }

    /**
     * @param  array<string, ExecutionNode>  $nodes
     * @param  array<int, ExecutionEdge>  $edges
     */
    protected function detectLoops(array $nodes, array $edges): bool
    {
        $adjacency = [];

        foreach ($edges as $edge) {
            $adjacency[$edge->source][] = $edge->target;
        }

        $visited = [];
        $stack = [];

        $visit = function (string $nodeId) use (&$visit, &$visited, &$stack, $adjacency): bool {
            $visited[$nodeId] = true;
            $stack[$nodeId] = true;

            foreach ($adjacency[$nodeId] ?? [] as $neighbor) {
                if (! isset($visited[$neighbor])) {
                    if ($visit($neighbor)) {
                        return true;
                    }
                } elseif ($stack[$neighbor] ?? false) {
                    return true;
                }
            }

            unset($stack[$nodeId]);

            return false;
        };

        foreach (array_keys($nodes) as $nodeId) {
            if (! isset($visited[$nodeId]) && $visit($nodeId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Drop canvas-only `start` nodes and rewire edges around them.
     *
     * @param  array<int, mixed>  $nodesRaw
     * @param  array<int, mixed>  $edgesRaw
     * @return array{0: array<int, mixed>, 1: array<int, mixed>}
     */
    protected function stripFrontendOnlyNodes(array $nodesRaw, array $edgesRaw): array
    {
        $frontendOnlyIds = [];

        foreach ($nodesRaw as $node) {
            if (! is_array($node)) {
                continue;
            }

            $data = is_array($node['data'] ?? null) ? $node['data'] : [];
            $rawType = (string) ($data['nodeType'] ?? $node['type'] ?? '');
            $id = (string) ($node['id'] ?? '');

            if ($id !== '' && NodeTypeNormalizer::isFrontendOnly($rawType)) {
                $frontendOnlyIds[$id] = true;
            }
        }

        if ($frontendOnlyIds === []) {
            return [$nodesRaw, $edgesRaw];
        }

        $outgoing = [];
        $incoming = [];

        foreach ($edgesRaw as $edge) {
            if (! is_array($edge)) {
                continue;
            }

            $source = (string) ($edge['source'] ?? '');
            $target = (string) ($edge['target'] ?? '');
            $sourceIsStart = isset($frontendOnlyIds[$source]);
            $targetIsStart = isset($frontendOnlyIds[$target]);

            if ($sourceIsStart && ! $targetIsStart && $target !== '') {
                $outgoing[$source][] = $edge;
            }

            if ($targetIsStart && ! $sourceIsStart && $source !== '') {
                $incoming[$target][] = $edge;
            }
        }

        $keptEdges = [];
        foreach ($edgesRaw as $edge) {
            if (! is_array($edge)) {
                continue;
            }

            $source = (string) ($edge['source'] ?? '');
            $target = (string) ($edge['target'] ?? '');

            if (isset($frontendOnlyIds[$source]) || isset($frontendOnlyIds[$target])) {
                continue;
            }

            $keptEdges[] = $edge;
        }

        foreach ($frontendOnlyIds as $startId => $_) {
            foreach ($incoming[$startId] ?? [] as $inEdge) {
                foreach ($outgoing[$startId] ?? [] as $outEdge) {
                    $rewired = $inEdge;
                    $rewired['id'] = (string) ($inEdge['id'] ?? 'e').'_'.(string) ($outEdge['id'] ?? 'e');
                    $rewired['source'] = $inEdge['source'];
                    $rewired['target'] = $outEdge['target'];
                    if (array_key_exists('sourceHandle', $inEdge)) {
                        $rewired['sourceHandle'] = $inEdge['sourceHandle'];
                    }
                    if (array_key_exists('targetHandle', $outEdge)) {
                        $rewired['targetHandle'] = $outEdge['targetHandle'];
                    }
                    $keptEdges[] = $rewired;
                }
            }
        }

        $keptNodes = [];
        foreach ($nodesRaw as $node) {
            if (! is_array($node)) {
                continue;
            }

            $id = (string) ($node['id'] ?? '');
            if (isset($frontendOnlyIds[$id])) {
                continue;
            }

            $keptNodes[] = $node;
        }

        return [array_values($keptNodes), array_values($keptEdges)];
    }
}
