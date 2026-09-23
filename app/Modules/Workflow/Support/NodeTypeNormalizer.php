<?php

namespace App\Modules\Workflow\Support;

use App\Modules\Automation\Support\TriggerCatalog;
use App\Modules\Workflow\Enums\NodeType;

/**
 * Single translation layer between React Flow nodeType IDs and canonical Laravel IDs.
 *
 * Frontend catalog may use friendlier names (e.g. onChatMessage). normalize() maps
 * those to backend canonical values (e.g. messageReceived) used by TriggerCatalog,
 * publish validation, compilation, and execution.
 */
final class NodeTypeNormalizer
{
    /**
     * Frontend / legacy IDs → canonical backend NodeType / TriggerCatalog keys.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        // --- Frontend catalog trigger mismatches ---
        'onChatMessage' => 'messageReceived',
        'labReportNotification' => 'labReportReady',
        'pharmacyRefillDue' => 'medicineRefillDue',
        'rewardUpdated' => 'rewardPointsUpdated',
        'rewardsTierUpgraded' => 'rewardTierUpgraded',
        'anniversary' => 'anniversaryReached',
        // FE "Medicine Reminder Due" uses medicineReminder; event bus uses medicineReminderDue
        'medicineReminder' => 'medicineReminderDue',

        // --- kebab / snake / Pascal variants ---
        'medicine-reminder' => 'medicineReminderDue',
        'medicine_reminder' => 'medicineReminderDue',
        'MedicineReminder' => 'medicineReminderDue',
        'medicine-reminder-due' => 'medicineReminderDue',
        'medicine_reminder_due' => 'medicineReminderDue',
        'prescription-added' => 'prescriptionAdded',
        'prescription_added' => 'prescriptionAdded',
        'appointment-booked' => 'appointmentBooked',
        'appointment_booked' => 'appointmentBooked',
        'appointment-cancelled' => 'appointmentCancelled',
        'appointment_cancelled' => 'appointmentCancelled',
        'appointment-missed' => 'appointmentMissed',
        'appointment_missed' => 'appointmentMissed',
        'appointment-rescheduled' => 'appointmentRescheduled',
        'appointment_rescheduled' => 'appointmentRescheduled',
        'appointment-completed' => 'appointmentCompleted',
        'appointment_completed' => 'appointmentCompleted',
        'appointment-reminder' => 'appointmentReminder',
        'appointment_reminder' => 'appointmentReminder',
        'lab-test-ordered' => 'labTestOrdered',
        'lab_test_ordered' => 'labTestOrdered',
        'lab-report-ready' => 'labReportReady',
        'lab_report_ready' => 'labReportReady',
        'lab-report-notification' => 'labReportReady',
        'lab_report_notification' => 'labReportReady',
        'medicine-refill-due' => 'medicineRefillDue',
        'medicine_refill_due' => 'medicineRefillDue',
        'pharmacy-refill-due' => 'medicineRefillDue',
        'pharmacy_refill_due' => 'medicineRefillDue',
        'invoice-generated' => 'invoiceGenerated',
        'invoice_generated' => 'invoiceGenerated',
        'payment-received' => 'paymentReceived',
        'payment_received' => 'paymentReceived',
        'payment-pending' => 'paymentPending',
        'payment_pending' => 'paymentPending',
        'membership-expiry' => 'membershipExpiry',
        'membership_expiry' => 'membershipExpiry',
        'user-plan-expiry' => 'userPlanExpiry',
        'user_plan_expiry' => 'userPlanExpiry',
        'reward-points-updated' => 'rewardPointsUpdated',
        'reward_points_updated' => 'rewardPointsUpdated',
        'reward-updated' => 'rewardPointsUpdated',
        'reward_updated' => 'rewardPointsUpdated',
        'reward-tier-upgraded' => 'rewardTierUpgraded',
        'reward_tier_upgraded' => 'rewardTierUpgraded',
        'rewards-tier-upgraded' => 'rewardTierUpgraded',
        'rewards_tier_upgraded' => 'rewardTierUpgraded',
        'family-package-tier-updated' => 'familyPackageTierUpdated',
        'family_package_tier_updated' => 'familyPackageTierUpdated',
        'anniversary-reached' => 'anniversaryReached',
        'anniversary_reached' => 'anniversaryReached',
        'patient-registered' => 'patientRegistered',
        'patient_registered' => 'patientRegistered',
        'message-received' => 'messageReceived',
        'message_received' => 'messageReceived',
        'on-chat-message' => 'messageReceived',
        'on_chat_message' => 'messageReceived',
        'campaign-triggered' => 'campaignTriggered',
        'campaign_triggered' => 'campaignTriggered',
        'webhook-event' => 'webhookEvent',
        'webhook_event' => 'webhookEvent',
        'api-event' => 'apiEvent',
        'api_event' => 'apiEvent',
        'scheduled-event' => 'scheduledEvent',
        'scheduled_event' => 'scheduledEvent',
        'procedure-completed' => 'procedureCompleted',
        'procedure_completed' => 'procedureCompleted',

        // --- Frontend catalog action / flow mismatches ---
        // FE catalog IDs must resolve to existing Laravel executor types (no duplicate executors).
        'wait' => 'delay',
        'sendSms' => 'sendSMS',
        'dbCreate' => 'createRecord',
        'dbUpdate' => 'databaseUpdate',
        'ai' => 'aiPrompt',
        'updateRecord' => 'databaseUpdate',
        'httpRequest' => 'webhook',
        'http-request' => 'webhook',
        'http_request' => 'webhook',
        'updateAppointment' => 'databaseUpdate',
        'update-appointment' => 'databaseUpdate',
        'update_appointment' => 'databaseUpdate',
        'updatePrescription' => 'databaseUpdate',
        'update-prescription' => 'databaseUpdate',
        'update_prescription' => 'databaseUpdate',
        'updateMembership' => 'databaseUpdate',
        'update-membership' => 'databaseUpdate',
        'update_membership' => 'databaseUpdate',

        // --- Actions / flow (kebab / snake variants) ---
        'send-whatsapp' => 'sendWhatsApp',
        'send_whatsapp' => 'sendWhatsApp',
        'send-email' => 'sendEmail',
        'send_email' => 'sendEmail',
        'send-push' => 'sendPush',
        'send_push' => 'sendPush',
        'send-sms' => 'sendSMS',
        'send_sms' => 'sendSMS',
        'database-update' => 'databaseUpdate',
        'database_update' => 'databaseUpdate',
        'create-record' => 'createRecord',
        'create_record' => 'createRecord',
        'ai-prompt' => 'aiPrompt',
        'ai_prompt' => 'aiPrompt',
    ];

    public static function normalize(string $nodeType): string
    {
        $trimmed = trim($nodeType);

        return self::ALIASES[$trimmed] ?? $trimmed;
    }

    /**
     * Trigger detection uses TriggerCatalog (+ end-of-flow is separate).
     * Always normalize first — never compare raw frontend IDs here.
     */
    public static function isTrigger(string $nodeType): bool
    {
        $canonical = self::normalize($nodeType);

        if (array_key_exists($canonical, TriggerCatalog::all())) {
            return true;
        }

        // Legacy builder node kept for already-published graphs that still
        // store the pre-alias id without going through normalize (defensive).
        return $canonical === NodeType::MedicineReminder->value;
    }

    public static function isEnd(string $nodeType): bool
    {
        return self::normalize($nodeType) === NodeType::End->value;
    }

    /**
     * Canvas-only types that must never require a runtime executor.
     */
    public static function isFrontendOnly(string $nodeType): bool
    {
        $trimmed = trim($nodeType);

        return $trimmed === 'start' || self::normalize($trimmed) === 'start';
    }
}
