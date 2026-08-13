<?php

namespace App\Modules\Workflow\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Workflow\Models\WorkflowTemplate;
use App\Modules\Workflow\Requests\StoreWorkflowTemplateRequest;
use App\Modules\Workflow\Requests\UpdateWorkflowTemplateRequest;
use App\Modules\Workflow\Resources\WorkflowTemplateCollection;
use App\Modules\Workflow\Resources\WorkflowTemplateResource;
use App\Modules\Workflow\Services\Builder\WorkflowTemplateService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class WorkflowTemplateController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected WorkflowTemplateService $templateService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkflowTemplate::class);

        $templates = $this->templateService->list(
            $request->only(['organization_id', 'module', 'status', 'category', 'trigger_type', 'search']),
            (int) $request->get('per_page', 15)
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow templates fetched successfully',
            'data' => (new WorkflowTemplateCollection($templates))->resolve(),
        ]);
    }

    /**
     * Frontend catalog — active templates only (read-only).
     */
    public function catalog(Request $request): JsonResponse
    {
        $filters = $request->only(['organization_id', 'module', 'category', 'trigger_type', 'search']);
        $filters['status'] = 'active';

        $templates = $this->templateService->list(
            $filters,
            (int) $request->get('per_page', 50)
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Active workflow templates fetched successfully',
            'data' => (new WorkflowTemplateCollection($templates))->resolve(),
        ]);
    }

    /**
     * Frontend show — active templates only.
     */
    public function showActive(int $id): JsonResponse
    {
        try {
            $template = $this->templateService->findOrFail($id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status_code' => 404, 'message' => $e->getMessage()], 404);
        }

        $status = $template->status instanceof \BackedEnum
            ? $template->status->value
            : (string) $template->status;

        if ($status !== 'active') {
            return response()->json(['status_code' => 404, 'message' => 'Workflow template not found.'], 404);
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow template fetched successfully',
            'data' => new WorkflowTemplateResource($template),
        ]);
    }

    public function store(StoreWorkflowTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', WorkflowTemplate::class);

        $template = $this->templateService->create(
            $request->validated(),
            $request->user()?->id ? (string) $request->user()->id : null
        );

        return response()->json([
            'status_code' => 201,
            'message' => 'Workflow template created successfully',
            'data' => new WorkflowTemplateResource($template),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $template = $this->templateService->findOrFail($id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status_code' => 404, 'message' => $e->getMessage()], 404);
        }

        $this->authorize('view', $template);

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow template fetched successfully',
            'data' => new WorkflowTemplateResource($template),
        ]);
    }

    public function update(UpdateWorkflowTemplateRequest $request, int $id): JsonResponse
    {
        try {
            $template = $this->templateService->findOrFail($id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status_code' => 404, 'message' => $e->getMessage()], 404);
        }

        $this->authorize('update', $template);

        $template = $this->templateService->update(
            $template,
            $request->validated(),
            $request->user()?->id ? (string) $request->user()->id : null
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow template updated successfully',
            'data' => new WorkflowTemplateResource($template),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $template = $this->templateService->findOrFail($id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status_code' => 404, 'message' => $e->getMessage()], 404);
        }

        $this->authorize('delete', $template);
        $this->templateService->delete($template);

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow template deleted successfully',
        ]);
    }

    public function duplicate(Request $request, int $id): JsonResponse
    {
        try {
            $template = $this->templateService->findOrFail($id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status_code' => 404, 'message' => $e->getMessage()], 404);
        }

        $this->authorize('duplicate', $template);

        $copy = $this->templateService->duplicate(
            $template,
            $request->user()?->id ? (string) $request->user()->id : null
        );

        return response()->json([
            'status_code' => 201,
            'message' => 'Workflow template duplicated successfully',
            'data' => new WorkflowTemplateResource($copy),
        ], 201);
    }

    public function preview(int $id): JsonResponse
    {
        try {
            $template = $this->templateService->findOrFail($id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status_code' => 404, 'message' => $e->getMessage()], 404);
        }

        $this->authorize('preview', $template);

        return response()->json([
            'status_code' => 200,
            'message' => 'Workflow template preview fetched successfully',
            'data' => $this->templateService->preview($template),
        ]);
    }
}
