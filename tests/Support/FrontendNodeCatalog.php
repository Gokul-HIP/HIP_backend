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
        'httpRequest' => 'webhook',
        'updateAppointment' => 'databaseUpdate',
        'updatePrescription' => 'databaseUpdate',
        'updateMembership' => 'databaseUpdate',
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
        'appointmentBooked' => \App\Modules\Workflow\NodeProcessors\AppointmentBookedTriggerNodeProcessor::class,
        'prescriptionAdded' => \App\Modules\Workflow\NodeProcessors\PrescriptionAddedTriggerNodeProcessor::class,
        'medicineReminder' => \App\Modules\Workflow\NodeProcessors\MedicineReminderDueTriggerNodeProcessor::class,
        'birthday' => \App\Modules\Workflow\NodeProcessors\BirthdayTriggerNodeProcessor::class,
        'scheduledEvent' => \App\Modules\Workflow\NodeProcessors\ScheduledEventTriggerNodeProcessor::class,
        'condition' => \App\Modules\Workflow\NodeProcessors\ConditionNodeProcessor::class,
        'wait' => \App\Modules\Workflow\NodeProcessors\DelayNodeProcessor::class,
        'sendWhatsApp' => \App\Modules\Workflow\NodeProcessors\SendWhatsAppNodeProcessor::class,
        'sendSms' => \App\Modules\Workflow\NodeProcessors\SendSMSNodeProcessor::class,
        'sendEmail' => \App\Modules\Workflow\NodeProcessors\SendEmailNodeProcessor::class,
        'sendPush' => \App\Modules\Workflow\NodeProcessors\SendPushNodeProcessor::class,
        'sendAiChat' => \App\Modules\Workflow\NodeProcessors\SendAiChatNodeProcessor::class,
        'sendAiVoice' => \App\Modules\Workflow\NodeProcessors\SendAiVoiceNodeProcessor::class,
        'sendTemplate' => \App\Modules\Workflow\NodeProcessors\SendTemplateNodeProcessor::class,
        'dbCreate' => \App\Modules\Workflow\NodeProcessors\CreateRecordNodeProcessor::class,
        'dbUpdate' => \App\Modules\Workflow\NodeProcessors\UpdateRecordNodeProcessor::class,
        'dbDelete' => \App\Modules\Workflow\NodeProcessors\DbDeleteNodeProcessor::class,
        'updateAppointment' => \App\Modules\Workflow\NodeProcessors\UpdateRecordNodeProcessor::class,
        'updatePrescription' => \App\Modules\Workflow\NodeProcessors\UpdateRecordNodeProcessor::class,
        'updateMembership' => \App\Modules\Workflow\NodeProcessors\UpdateRecordNodeProcessor::class,
        'dbQuery' => \App\Modules\Workflow\NodeProcessors\DbQueryNodeProcessor::class,
        'httpRequest' => \App\Modules\Workflow\NodeProcessors\WebhookNodeProcessor::class,
        'ai' => \App\Modules\Workflow\NodeProcessors\AiPromptNodeProcessor::class,
        'end' => \App\Modules\Workflow\NodeProcessors\EndNodeProcessor::class,
    ];

    /**
     * FE IDs with no Laravel executor (must not claim registry support).
     * sendIvr is a future IVR integration — not implemented, not faked.
     *
     * @var list<string>
     */
    public const NOT_IMPLEMENTED_NO_EXECUTOR = [
        'start',
        'sendIvr',
    ];

    /**
     * Contract not_implemented but may still have TriggerNodeProcessor.
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
        if ($frontendId === 'start') {
            return null;
        }

        if (array_key_exists($frontendId, self::ALIASES)) {
            return self::ALIASES[$frontendId];
        }

        return $frontendId;
    }
}
