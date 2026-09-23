<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;
use App\Modules\Workflow\Services\Runtime\VariableResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookNodeProcessor extends AbstractNodeProcessor
{
    public function __construct(
        ActionDispatcher $actionDispatcher,
        protected VariableResolver $variableResolver,
    ) {
        parent::__construct($actionDispatcher);
    }

    public function type(): string
    {
        return 'webhook';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $data = is_array($node->data) ? $node->data : [];
        $payload = array_merge(
            is_array($execution->context) ? $execution->context : [],
            is_array($context->payload) ? $context->payload : [],
            ['variables' => $context->variables]
        );

        $url = trim((string) ($data['url'] ?? data_get($data, 'data.url') ?? ''));
        if ($url !== '') {
            $url = trim($this->variableResolver->resolve($url, $payload));
        }

        if ($url === '') {
            return NodeExecutionResult::failed('Webhook URL is required.');
        }

        if (! $this->isAllowedUrl($url)) {
            return NodeExecutionResult::failed('Webhook URL must be an http or https URL.');
        }

        $method = strtoupper(trim((string) ($data['method'] ?? 'POST')));
        if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return NodeExecutionResult::failed('Webhook method is not supported.');
        }

        $timeout = (int) ($data['timeout'] ?? $data['timeoutSeconds'] ?? 30);
        if ($timeout < 1 || $timeout > 120) {
            $timeout = 30;
        }

        $headers = is_array($data['headers'] ?? null) ? $data['headers'] : [];
        $stringHeaders = [];
        foreach ($headers as $name => $value) {
            if (! is_string($name) || $name === '') {
                continue;
            }
            $stringHeaders[$name] = is_scalar($value) ? (string) $value : '';
        }

        $query = is_array($data['params'] ?? $data['query'] ?? null)
            ? ($data['params'] ?? $data['query'])
            : [];

        $body = $data['body'] ?? null;
        if ($body === null && $method !== 'GET') {
            $body = [
                'execution_id' => $execution->id,
                'workflow_id' => $execution->workflow_id,
                'context' => $execution->context,
                'variables' => $context->variables,
            ];
        }

        try {
            $pending = Http::timeout($timeout)->withHeaders($stringHeaders);

            if ($query !== [] && is_array($query)) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator.http_build_query($query);
            }

            $response = match ($method) {
                'GET' => $pending->get($url),
                'PUT' => $pending->put($url, is_array($body) ? $body : []),
                'PATCH' => $pending->patch($url, is_array($body) ? $body : []),
                'DELETE' => $pending->delete($url, is_array($body) ? $body : []),
                default => $pending->post($url, is_array($body) ? $body : []),
            };

            $context->setVariable('http_status', $response->status());

            if (! $response->successful()) {
                return NodeExecutionResult::failed('Webhook returned HTTP '.$response->status());
            }
        } catch (\Throwable $e) {
            Log::warning('Webhook execution failed', ['error' => $e->getMessage()]);

            return NodeExecutionResult::failed($e->getMessage());
        }

        return NodeExecutionResult::continue();
    }

    protected function isAllowedUrl(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], true) && is_string($host) && $host !== '';
    }
}
