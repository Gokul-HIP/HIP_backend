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
        return response()->json([
            'status_code' => 410,
            'message' => 'Legacy medicine_workflows API is disabled. Create Medicine Reminder workflows via POST /api/workflows.',
        ], 410);
    }

    public function updateWorkflow(UpdateMedicineWorkflowRequest $request, int $id): JsonResponse
    {
        return response()->json([
            'status_code' => 410,
            'message' => 'Legacy medicine_workflows API is disabled. Update Medicine Reminder workflows via PUT /api/workflows/{id}.',
        ], 410);
    }

    public function destroyWorkflow(int $id): JsonResponse
    {
        return response()->json([
            'status_code' => 410,
            'message' => 'Legacy medicine_workflows API is disabled. Delete Medicine Reminder workflows via DELETE /api/workflows/{id}.',
        ], 410);
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
