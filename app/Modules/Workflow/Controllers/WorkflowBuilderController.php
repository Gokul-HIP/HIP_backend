<?php

namespace App\Modules\Workflow\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use App\Modules\Workflow\Requests\StoreWorkflowRequest;
use App\Modules\Workflow\Requests\UpdateWorkflowRequest;
use App\Modules\Workflow\Resources\WorkflowDetailResource;
use App\Modules\Workflow\Resources\WorkflowExecutionResource;
use App\Modules\Workflow\Resources\WorkflowMessageTemplateResource;
use App\Modules\Workflow\Resources\WorkflowResource;
use App\Modules\Workflow\Services\Builder\TriggerSchemaRegistry;
use App\Modules\Workflow\Services\Builder\VariableCatalogService;
use App\Modules\Workflow\Services\Builder\WorkflowBuilderService;
use App\Modules\Workflow\Services\Builder\WorkflowPublishService;
use App\Modules\Workflow\Services\Builder\WorkflowTemplateQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class WorkflowBuilderController extends Controller
{
    public function __construct(
        protected WorkflowBuilderService $builderService,
        protected WorkflowPublishService $publishService,
        protected TriggerSchemaRegistry $triggerSchemaRegistry,
        protected VariableCatalogService $variableCatalogService,
        protected WorkflowTemplateQueryService $templateQueryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $workflows = $this->builderService->list(
            $request->only(['organization_id', 'status', 'trigger_type', 'search']),
            (int) $request->get('per_page', 15)
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflows fetched successfully',
            'data' => WorkflowResource::collection($workflows)->response()->getData(true),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $workflow = $this->builderService->findOrFail($id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status_code' => 404, 'message' => $e->getMessage()], 404);
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow fetched successfully',
            'data' => new WorkflowDetailResource($workflow),
        ]);
    }

    public function store(StoreWorkflowRequest $request): JsonResponse
    {
        $validated = $request->validated();
        if ($request->has('configuration')) {
            $validated['configuration'] = $request->input('configuration');
        }

        $workflow = $this->builderService->create(
            $validated,
            $request->user()?->id ? (string) $request->user()->id : null
        );

        return response()->json([
            'status_code' => 201,
            'message' => 'Workflow created successfully',
            'data' => new WorkflowDetailResource($workflow),
        ], 201);
    }

    public function update(UpdateWorkflowRequest $request, int $id): JsonResponse
    {
        try {
            $workflow = $this->builderService->findOrFail($id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status_code' => 404, 'message' => $e->getMessage()], 404);
        }

        $validated = $request->validated();
        if ($request->has('configuration')) {
            $validated['configuration'] = $request->input('configuration');
        }

        $workflow = $this->builderService->update(
            $workflow,
            $validated,
            $request->user()?->id ? (string) $request->user()->id : null
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow saved successfully',
            'data' => new WorkflowDetailResource($workflow),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $workflow = $this->builderService->findOrFail($id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status_code' => 404, 'message' => $e->getMessage()], 404);
        }

        $this->builderService->delete($workflow);

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow deleted successfully',
        ]);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        try {
            $workflow = $this->builderService->findOrFail($id);
            $result = $this->publishService->publish(
                $workflow,
                $request->user()?->id ? (string) $request->user()->id : null,
                $request->input('version_notes')
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['status_code' => 422, 'message' => $e->getMessage()], 422);
        }

        $version = $result['version'];

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow published successfully',
            'data' => [
                'workflow_id' => $workflow->id,
                'version_id' => $version->id,
                'version_number' => $version->version_number,
                'published_at' => $version->published_at?->toIso8601String(),
                'validation' => $result['validation'],
            ],
        ]);
    }

    public function triggers(): JsonResponse
    {
        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow triggers fetched successfully',
            'data' => $this->triggerSchemaRegistry->all(),
        ]);
    }

    public function variables(Request $request): JsonResponse
    {
        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow variables fetched successfully',
            'data' => $this->variableCatalogService->forTrigger($request->query('trigger')),
        ]);
    }

    public function templates(Request $request): JsonResponse
    {
        $filters = $request->only(['organization_id', 'channel', 'category']);
        if ($request->filled('preview_context')) {
            $filters['preview_context'] = $request->input('preview_context');
        }

        $templates = $this->templateQueryService->list(
            $filters,
            (int) $request->get('per_page', 20)
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow templates fetched successfully',
            'data' => WorkflowMessageTemplateResource::collection($templates)->response()->getData(true),
        ]);
    }

    public function previewTemplate(Request $request, int $id): JsonResponse
    {
        $template = WorkflowMessageTemplate::query()->find($id);

        if (! $template) {
            return response()->json(['status_code' => 404, 'message' => 'Template not found'], 404);
        }

        $preview = $this->templateQueryService->preview(
            $template,
            is_array($request->input('context')) ? $request->input('context') : []
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Template preview generated',
            'data' => [
                'id' => $template->id,
                'preview' => $preview,
            ],
        ]);
    }

    public function executions(Request $request): JsonResponse
    {
        $executions = WorkflowExecution::query()
            ->with(['workflow:id,name', 'version:id,version_number'])
            ->when($request->workflow_id, fn ($q) => $q->where('workflow_id', $request->workflow_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->execution_id, fn ($q) => $q->where('id', $request->execution_id))
            ->when($request->date_from, fn ($q) => $q->whereDate('started_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('started_at', '<=', $request->date_to))
            ->latest('id')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow executions fetched successfully',
            'data' => WorkflowExecutionResource::collection($executions)->response()->getData(true),
        ]);
    }
}
