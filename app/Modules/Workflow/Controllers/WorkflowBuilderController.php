<?php

namespace App\Modules\Workflow\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Organization;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use App\Modules\Workflow\Requests\StoreWorkflowMessageTemplateRequest;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
            $request->only(['organization_id', 'hospital_id', 'status', 'trigger_type', 'search']),
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

    public function duplicate(Request $request, int $id): JsonResponse
    {
        try {
            $workflow = $this->builderService->findOrFail($id);
            $copy = $this->builderService->duplicate(
                $workflow,
                $request->user()?->id ? (string) $request->user()->id : null
            );
        } catch (InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Workflow not found.' ? 404 : 422;

            return response()->json(['status_code' => $status, 'message' => $e->getMessage()], $status);
        }

        return response()->json([
            'status_code' => 201,
            'message' => 'Workflow duplicated successfully',
            'data' => new WorkflowDetailResource($copy),
        ], 201);
    }

    public function hospitals(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->get('per_page', 50)));
        $search = trim((string) $request->get('search', ''));

        $hospitals = Hospital::query()
            ->orderBy('name')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('address', 'like', $like);

                    if (Schema::hasColumn('hospitals', 'city')) {
                        $inner->orWhere('city', 'like', $like);
                    }
                });
            })
            ->paginate($perPage);

        $hospitals->getCollection()->transform(function ($hospital) {
            return [
                'id' => $hospital->id,
                'name' => $hospital->name,
                'city' => $hospital->city,
                'code' => $hospital->id,
                'organization_id' => $hospital->organization_id,
            ];
        });

        return response()->json([
            'status_code' => 200,
            'message' => 'Hospitals fetched successfully',
            'data' => $hospitals,
        ]);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'hospital_id' => ['sometimes', 'nullable', 'integer', 'exists:hospitals,id'],
            'version_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        try {
            $workflow = $this->builderService->findOrFail($id);

            if (array_key_exists('hospital_id', $validated)) {
                $this->builderService->update($workflow, [
                    'hospital_id' => $validated['hospital_id'],
                ], $request->user()?->id ? (string) $request->user()->id : null);
                $workflow = $this->builderService->findOrFail($id);
            }

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

    /**
     * Create a channel message template (FE "+ Add to Template").
     */
    public function storeTemplate(StoreWorkflowMessageTemplateRequest $request): JsonResponse
    {
        $template = $this->templateQueryService->create(
            $request->validated(),
            $this->resolveTemplateOrganizationId($request)
        );

        return response()->json([
            'status_code' => 201,
            'message' => 'Workflow template created successfully',
            'data' => new WorkflowMessageTemplateResource($template),
        ], 201);
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
                'body' => $template->body,
                'message' => $template->body,
                'subject' => is_array($template->variables) ? ($template->variables['subject'] ?? null) : null,
                'title' => is_array($template->variables) ? ($template->variables['title'] ?? null) : null,
            ],
        ]);
    }

    /**
     * Tenant ownership for message templates — from the authenticated user only.
     * Client-supplied organization_id / hospital_id are rejected by the FormRequest.
     *
     * Only returns an organization_id that exists in `organizations` to avoid FK 500s
     * when the auth user has a stale/missing organization reference.
     */
    protected function resolveTemplateOrganizationId(Request $request): ?int
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        $candidates = [];

        $rawOrg = data_get($user, 'organization_id');
        if ($rawOrg !== null && $rawOrg !== '' && is_numeric($rawOrg)) {
            $candidates[] = (int) $rawOrg;
        }

        $rawHospital = data_get($user, 'hospital_id');
        if ($rawHospital !== null && $rawHospital !== '' && is_numeric($rawHospital)) {
            $fromHospital = Hospital::query()->whereKey((int) $rawHospital)->value('organization_id');
            if ($fromHospital !== null && $fromHospital !== '') {
                $candidates[] = (int) $fromHospital;
            }
        }

        foreach (array_unique($candidates) as $id) {
            if ($id > 0 && Organization::query()->whereKey($id)->exists()) {
                return $id;
            }
        }

        if ($candidates !== []) {
            Log::warning('workflow message template: ignoring non-existent user organization_id', [
                'user_id' => data_get($user, 'id'),
                'candidates' => $candidates,
            ]);
        }

        return null;
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
