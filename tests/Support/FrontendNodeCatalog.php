<?php

namespace Tests\Support;

/**
 * Catalog of frontend Flow Builder node IDs and expected backend mapping.
 * Keep in sync with docs/automation/backend-node-contracts.json + NodeTypeNormalizer.
 */
final class FrontendNodeCatalog
{
    /**
     * FE ID → canonical backend ID (null = FE-only / no canonical executor type).
     *
     * @var array<string, string|null>
     */
    public const ALIASES = [
        'onChatMessage' => 'messageReceived',
        'labReportNotification' => 'labReportReady',
        'pharmacyRefillDue' => 'medicineRefillDue',
        'rewardUpdated' => 'rewardPointsUpdated',
        'rewardsTierUpgraded' => 'rewardTierUpgraded',
        'anniversary' => 'anniversaryReached',
        'medicineReminder' => 'medicineReminderDue',
        'wait' => 'delay',
        'sendSms' => 'sendSMS',
        'dbCreate' => 'createRecord',
        'dbUpdate' => 'databaseUpdate',
        'ai' => 'aiPrompt',
    ];

    /**
     * @var list<string>
     */
    public const ALL_FRONTEND_IDS = [
        'start',
        'onChatMessage',
        'patientRegistered',
        'appointmentBooked',
        'appointmentRescheduled',
        'appointmentCompleted',
        'appointmentCancelled',
        'appointmentMissed',
        'prescriptionAdded',
        'medicineReminder',
        'labTestOrdered',
        'labReportNotification',
        'pharmacyRefillDue',
        'appointmentReminder',
        'birthday',
        'anniversary',
        'membershipExpiry',
        'userPlanExpiry',
        'rewardUpdated',
        'rewardsTierUpgraded',
        'familyPackageTierUpdated',
        'invoiceGenerated',
        'paymentReceived',
        'webhookEvent',
        'apiEvent',
        'scheduledEvent',
        'condition',
        'wait',
        'sendWhatsApp',
        'sendSms',
        'sendEmail',
        'sendPush',
        'sendAiChat',
        'sendAiVoice',
        'sendIvr',
        'sendTemplate',
        'dbCreate',
        'dbUpdate',
        'dbDelete',
        'updateAppointment',
        'updatePrescription',
        'updateMembership',
        'dbQuery',
        'httpRequest',
        'ai',
        'end',
    ];

    /**
     * Nodes with dedicated non-passthrough executors that are contract-implemented.
     *
     * @var array<string, class-string>
     */
    public const IMPLEMENTED_EXECUTORS = [
        'appointmentBooked' => \App\Modules\Workflow\Executors\Triggers\AppointmentBookedTriggerExecutor::class,
        'prescriptionAdded' => \App\Modules\Workflow\Executors\Triggers\PrescriptionAddedTriggerExecutor::class,
        'medicineReminder' => \App\Modules\Workflow\Executors\Triggers\MedicineReminderDueTriggerExecutor::class,
        'birthday' => \App\Modules\Workflow\Executors\Triggers\BirthdayTriggerExecutor::class,
        'scheduledEvent' => \App\Modules\Workflow\Executors\Triggers\ScheduledEventTriggerExecutor::class,
        'condition' => \App\Modules\Workflow\Executors\Flow\ConditionExecutor::class,
        'wait' => \App\Modules\Workflow\Executors\Flow\DelayExecutor::class,
        'sendWhatsApp' => \App\Modules\Workflow\Executors\Actions\SendWhatsAppExecutor::class,
        'sendSms' => \App\Modules\Workflow\Executors\Actions\SendSMSExecutor::class,
        'sendEmail' => \App\Modules\Workflow\Executors\Actions\SendEmailExecutor::class,
        'sendPush' => \App\Modules\Workflow\Executors\Actions\SendPushExecutor::class,
        'sendTemplate' => \App\Modules\Workflow\Executors\Actions\SendTemplateExecutor::class,
        'dbCreate' => \App\Modules\Workflow\Executors\Actions\CreateRecordExecutor::class,
        'dbUpdate' => \App\Modules\Workflow\Executors\Actions\UpdateRecordExecutor::class,
        'dbDelete' => \App\Modules\Workflow\Executors\Actions\DbDeleteExecutor::class,
        'ai' => \App\Modules\HospitalAutomation\Executors\AiPromptExecutor::class,
        'end' => \App\Modules\Workflow\Executors\Flow\EndExecutor::class,
    ];

    /**
     * FE IDs with no Laravel executor (must not claim registry support).
     *
     * @var list<string>
     */
    public const NOT_IMPLEMENTED_NO_EXECUTOR = [
        'start',
        'sendAiChat',
        'sendAiVoice',
        'sendIvr',
        'updateAppointment',
        'updatePrescription',
        'updateMembership',
        'dbQuery',
        'httpRequest',
    ];

    /**
     * Contract not_implemented but may still have PassthroughTriggerExecutor.
     *
     * @var list<string>
     */
    public const NOT_IMPLEMENTED_DOMAIN = [
        'appointmentReminder',
        'userPlanExpiry',
        'familyPackageTierUpdated',
        'webhookEvent',
        'apiEvent',
    ];

    public static function canonical(string $frontendId): ?string
    {
        if (array_key_exists($frontendId, self::ALIASES)) {
            return self::ALIASES[$frontendId];
        }

        if (in_array($frontendId, self::NOT_IMPLEMENTED_NO_EXECUTOR, true) && $frontendId === 'start') {
            return null;
        }

        if (in_array($frontendId, ['sendAiChat', 'sendAiVoice', 'sendIvr', 'updateAppointment', 'updatePrescription', 'updateMembership', 'dbQuery', 'httpRequest'], true)) {
            return $frontendId; // identity, but no executor
        }

        return $frontendId;
    }
}
