<?php

namespace App\Modules\MedicineReminder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MedicineReminder\Jobs\SendMedicineReminderJob;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\MedicineReminder\Requests\StoreMedicineWorkflowRequest;
use App\Modules\MedicineReminder\Requests\UpdateMedicineWorkflowRequest;
use App\Modules\MedicineReminder\Resources\MedicineReminderScheduleResource;
use App\Modules\MedicineReminder\Resources\MedicineWorkflowResource;
use App\Modules\MedicineReminder\Services\MedicineReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MedicineReminderController extends Controller
{
    public function __construct(
        protected MedicineReminderService $medicineReminderService
    ) {}

    public function indexWorkflows(Request $request): JsonResponse
    {
        $workflows = $this->medicineReminderService->listWorkflows(
            $request->integer('organization_id') ?: null,
            $request->integer('per_page', 15)
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Medicine workflows fetched successfully',
            'data' => MedicineWorkflowResource::collection($workflows)->response()->getData(true),
        ]);
    }

    public function showWorkflow(int $id): JsonResponse
    {
        $workflow = $this->medicineReminderService->findWorkflowOrFail($id);

        return response()->json([
            'status_code' => 200,
            'message' => 'Medicine workflow fetched successfully',
            'data' => [
                'id' => $workflow->id,
                'organization_id' => $workflow->organization_id,
                'name' => $workflow->name,
                'status' => $workflow->status,
                'configuration' => $workflow->configuration,
                'created_by' => $workflow->created_by,
                'created_at' => $workflow->created_at?->toIso8601String(),
                'updated_at' => $workflow->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function storeWorkflow(StoreMedicineWorkflowRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // TEMP: verify frontend payload reaches backend untouched
        Log::info('Workflow Payload', $validated);
        Log::info('Configuration', [
            'configuration' => $validated['configuration'] ?? null,
        ]);

        // Ensure we persist the exact React Flow configuration sent by the frontend.
        // Laravel's validated() may drop parts not explicitly covered by validation rules.
        if ($request->has('configuration')) {
            $validated['configuration'] = $request->input('configuration');
        }

        $workflow = $this->medicineReminderService->createWorkflow($validated);

        return response()->json([
            'status_code' => 201,
            'message' => 'Medicine workflow created successfully',
            'data' => new MedicineWorkflowResource($workflow),
        ], 201);
    }

    public function updateWorkflow(UpdateMedicineWorkflowRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        // TEMP: verify frontend payload reaches backend untouched
        Log::info('Workflow Payload (update)', $validated);
        Log::info('Configuration (update)', [
            'configuration' => $validated['configuration'] ?? null,
        ]);

        if ($request->has('configuration')) {
            $validated['configuration'] = $request->input('configuration');
        }

        $workflow = $this->medicineReminderService->findWorkflowOrFail($id);
        $workflow = $this->medicineReminderService->updateWorkflow($workflow, $validated);

        return response()->json([
            'status_code' => 200,
            'message' => 'Medicine workflow updated successfully',
            'data' => new MedicineWorkflowResource($workflow),
        ]);
    }

    public function destroyWorkflow(int $id): JsonResponse
    {
        $workflow = $this->medicineReminderService->findWorkflowOrFail($id);
        $this->medicineReminderService->deleteWorkflow($workflow);

        return response()->json([
            'status_code' => 200,
            'message' => 'Medicine workflow deleted successfully',
        ]);
    }

    public function indexReminders(Request $request): JsonResponse
    {
        $schedules = $this->medicineReminderService->listSchedules(
            $request->only(['status', 'patient_id', 'prescription_id', 'workflow_id']),
            $request->integer('per_page', 15)
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Medicine reminders fetched successfully',
            'data' => MedicineReminderScheduleResource::collection($schedules)->response()->getData(true),
        ]);
    }

    public function testReminder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'schedule_id' => ['required', 'integer', 'exists:medicine_reminder_schedules,id'],
        ]);

        $schedule = MedicineReminderSchedule::query()->findOrFail($data['schedule_id']);

        $schedule->update([
            'status' => 'pending',
            'next_retry_at' => null,
        ]);

        SendMedicineReminderJob::dispatch($schedule->id);

        return response()->json([
            'status_code' => 200,
            'message' => 'Test reminder job queued successfully',
            'data' => [
                'schedule_id' => $schedule->id,
            ],
        ]);
    }
}
