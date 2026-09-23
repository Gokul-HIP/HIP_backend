<?php

namespace App\Modules\Automation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Automation\TriggerHandlers\HospitalAutomationTriggerService;
use App\Modules\Automation\Support\TriggerCatalog;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HospitalAutomationController extends Controller
{
    public function catalog(): JsonResponse
    {
        return response()->json([
            'modules' => TriggerCatalog::byModule(),
            'triggers' => TriggerCatalog::all(),
        ]);
    }

    public function workflows(Request $request): JsonResponse
    {
        $workflows = Workflow::query()
            ->with('currentVersion:id,workflow_id,version_number,published_at')
            ->when($request->trigger_type, fn ($q) => $q->where('trigger_type', $request->trigger_type))
            ->when($request->organization_id, fn ($q) => $q->where('organization_id', $request->organization_id))
            ->latest('id')
            ->paginate((int) $request->get('per_page', 15));

        return response()->json($workflows);
    }

    public function executions(Request $request): JsonResponse
    {
        $executions = WorkflowExecution::query()
            ->with(['workflow:id,name', 'version:id,version_number'])
            ->when($request->workflow_id, fn ($q) => $q->where('workflow_id', $request->workflow_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->patient_id, fn ($q) => $q->where('patient_id', $request->patient_id))
            ->latest('id')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json($executions);
    }

    public function templates(Request $request): JsonResponse
    {
        $templates = WorkflowMessageTemplate::query()
            ->when($request->channel, fn ($q) => $q->where('channel', $request->channel))
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->where('is_active', true)
            ->latest('id')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json($templates);
    }

    public function trigger(Request $request, HospitalAutomationTriggerService $triggerService): JsonResponse
    {
        $validated = $request->validate([
            'trigger_type' => 'required|string',
            'payload' => 'nullable|array',
        ]);

        $triggerService->dispatch($validated['trigger_type'], $validated['payload'] ?? []);

        return response()->json([
            'message' => 'Automation trigger dispatched through WorkflowExecutor.',
            'trigger_type' => $validated['trigger_type'],
        ]);
    }
}
