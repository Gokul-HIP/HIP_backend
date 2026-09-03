<?php

namespace App\Modules\Workflow\DTO;

final class NodeExecutionResult
{
    /**
     * @param  array<string>  $nextNodeIds  Empty = follow all outgoing edges; non-empty = explicit branch targets
     * @param  array<string, mixed>  $output
     */
    public function __construct(
        public readonly string $status,
        public readonly array $nextNodeIds = [],
        public readonly ?int $delaySeconds = null,
        public readonly array $output = [],
        public readonly ?string $message = null,
    ) {}

    /**
     * @param  array<string, mixed>  $output
     */
    public static function continue(array $output = []): self
    {
        return new self(status: 'continue', output: $output);
    }

    public static function complete(): self
    {
        return new self(status: 'completed');
    }

    public static function wait(int $delaySeconds): self
    {
        return new self(status: 'waiting', delaySeconds: $delaySeconds);
    }

    /**
     * @param  array<string>  $nextNodeIds
     */
    public static function branch(array $nextNodeIds): self
    {
        return new self(status: 'branch', nextNodeIds: $nextNodeIds);
    }

    public static function failed(string $message): self
    {
        return new self(status: 'failed', message: $message);
    }
}
