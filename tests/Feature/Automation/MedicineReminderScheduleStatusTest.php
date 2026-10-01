<?php

namespace Tests\Feature\Automation;

use App\Models\Hospital;
use App\Modules\MedicineReminder\Enums\ScheduleStatus;
use App\Modules\MedicineReminder\Models\MedicineReminderSchedule;
use App\Modules\MedicineReminder\Services\MedicineReminderExecutionService;
use App\Modules\MedicineReminder\Services\MedicineReminderScheduler;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\WorkflowExecution;
use Carbon\Carbon;

class MedicineReminderScheduleStatusTest extends PrescriptionMedicineReminderAutomationTest
{
    public function test_pending_processing_sent_after_successful_notification(): void
    {
        [$schedule] = $this->seedDueReminder();

        $this->assertSame(ScheduleStatus::Pending->value, $schedule->status);

        app(MedicineReminderExecutionService::class)->execute($schedule->id);

        $schedule = $schedule->fresh();
        $this->assertSame(ScheduleStatus::Sent->value, $schedule->status);
        $this->assertSame(1, WorkflowExecution::query()->count());
        $this->assertSame(WorkflowExecutionStatus::Completed->value, WorkflowExecution::query()->value('status'));
        $this->assertCount(1, $this->providerSends);
        $this->assertSame(1, MedicineReminderSchedule::query()->count());
    }

    public function test_pending_processing_failed_after_notification_failure(): void
    {
        [$schedule] = $this->seedDueReminder();
        $this->bindNotificationMocks(success: false, failChannel: 'push');

        app(MedicineReminderExecutionService::class)->execute($schedule->id);

        $schedule = $schedule->fresh();
        $this->assertSame(ScheduleStatus::Failed->value, $schedule->status);
        $this->assertSame(1, (int) $schedule->retry_count);
        $this->assertSame(WorkflowExecutionStatus::Failed->value, WorkflowExecution::query()->value('status'));
        $this->assertSame(1, MedicineReminderSchedule::query()->count());
    }

    public function test_repeated_dispatch_does_not_send_the_same_schedule_twice(): void
    {
        [$schedule] = $this->seedDueReminder();

        $scheduler = app(MedicineReminderScheduler::class);
        $this->assertSame(1, $scheduler->dispatchDueReminders());
        $this->assertSame(0, $scheduler->dispatchDueReminders());

        app(MedicineReminderExecutionService::class)->execute($schedule->id);
        app(MedicineReminderExecutionService::class)->execute($schedule->id);

        $this->assertSame(ScheduleStatus::Sent->value, $schedule->fresh()->status);
        $this->assertSame(1, WorkflowExecution::query()->count());
        $this->assertCount(1, $this->providerSends);
        $this->assertSame(1, MedicineReminderSchedule::query()->count());
    }

    public function test_workflow_success_finalizes_the_original_schedule_row(): void
    {
        [$schedule] = $this->seedDueReminder();
        $originalId = $schedule->id;

        app(MedicineReminderExecutionService::class)->execute($originalId);

        $this->assertSame($originalId, $schedule->fresh()->id);
        $this->assertSame(ScheduleStatus::Sent->value, $schedule->fresh()->status);
        $this->assertSame(1, MedicineReminderSchedule::query()->whereKey($originalId)->count());
        $this->assertSame(
            WorkflowExecutionStatus::Completed->value,
            WorkflowExecution::query()->value('status')
        );
    }

    public function test_workflow_failure_finalizes_the_original_schedule_row_as_failed(): void
    {
        [$schedule] = $this->seedDueReminder();
        $originalId = $schedule->id;
        $this->bindNotificationMocks(success: false, failChannel: 'push');

        app(MedicineReminderExecutionService::class)->execute($originalId);

        $this->assertSame($originalId, $schedule->fresh()->id);
        $this->assertSame(ScheduleStatus::Failed->value, $schedule->fresh()->status);
        $this->assertSame(1, MedicineReminderSchedule::query()->whereKey($originalId)->count());
    }

    public function test_no_published_workflow_marks_schedule_failed(): void
    {
        $prescription = $this->insertPrescription([
            'patient_id' => 'patient-1',
            'medications' => [['name' => 'X', 'frequency' => 'Once Daily', 'duration' => '1 Day']],
        ]);
        $schedule = MedicineReminderSchedule::query()->create([
            'workflow_id' => null,
            'patient_id' => 'patient-1',
            'prescription_id' => $prescription->id,
            'prescription_item_id' => 0,
            'medicine_id' => 7,
            'scheduled_at' => Carbon::now()->subMinute(),
            'status' => ScheduleStatus::Pending->value,
            'message_template' => null,
        ]);

        app(MedicineReminderExecutionService::class)->execute($schedule->id);

        $this->assertSame(ScheduleStatus::Failed->value, $schedule->fresh()->status);
        $this->assertSame(0, WorkflowExecution::query()->count());
    }

    /**
     * @return array{0: MedicineReminderSchedule}
     */
    protected function seedDueReminder(): array
    {
        $hospital = Hospital::query()->create(['name' => 'H5', 'organization_id' => 1]);
        $this->publishDefinition(
            $this->linearGraph('medicineReminderDue', 'sendPush', [
                'title' => 'Take medicine',
                'body' => '{{medicine_name}} at {{when_to_take}}',
                'recipient' => 'patient',
            ]),
            'medicineReminderDue',
            (int) $hospital->id,
            1
        );

        $prescription = $this->insertPrescription([
            'hospital_id' => $hospital->id,
            'patient_id' => 'patient-1',
            'member_id' => 'member-1',
            'medications' => [[
                'prescription_item_id' => 0,
                'medicine_id' => 55,
                'name' => 'Paracetamol',
                'when_to_take' => 'Afternoon',
            ]],
        ]);

        $schedule = MedicineReminderSchedule::query()->create([
            'workflow_id' => null,
            'patient_id' => 'patient-1',
            'prescription_id' => $prescription->id,
            'prescription_item_id' => 0,
            'medicine_id' => 55,
            'scheduled_at' => Carbon::now()->subMinute(),
            'status' => ScheduleStatus::Pending->value,
            'retry_count' => 0,
            'message_template' => null,
        ]);

        return [$schedule];
    }
}
