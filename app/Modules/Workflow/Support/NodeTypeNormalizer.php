<?php

namespace App\Modules\Workflow\Support;

use App\Modules\Workflow\Enums\NodeType;

final class NodeTypeNormalizer
{
    private const ALIASES = [
        'medicine-reminder' => 'medicineReminder',
        'medicine_reminder' => 'medicineReminder',
        'MedicineReminder' => 'medicineReminder',
        'medicineReminderDue' => 'medicineReminderDue',
        'prescription-added' => 'prescriptionAdded',
        'prescription_added' => 'prescriptionAdded',
        'appointment-booked' => 'appointmentBooked',
        'appointment_booked' => 'appointmentBooked',
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
        'appointment-cancelled' => 'appointmentCancelled',
        'appointment-missed' => 'appointmentMissed',
        'appointment-rescheduled' => 'appointmentRescheduled',
        'appointment-completed' => 'appointmentCompleted',
        'lab-test-ordered' => 'labTestOrdered',
        'lab-report-ready' => 'labReportReady',
        'medicine-refill-due' => 'medicineRefillDue',
        'invoice-generated' => 'invoiceGenerated',
        'payment-pending' => 'paymentPending',
        'membership-expiry' => 'membershipExpiry',
        'message-received' => 'messageReceived',
        'campaign-triggered' => 'campaignTriggered',
    ];

    public static function normalize(string $nodeType): string
    {
        $trimmed = trim($nodeType);

        return self::ALIASES[$trimmed] ?? $trimmed;
    }

    public static function isTrigger(string $nodeType): bool
    {
        return in_array(self::normalize($nodeType), [
            NodeType::MedicineReminderDue->value,
            NodeType::PrescriptionAdded->value,
            NodeType::AppointmentBooked->value,
            NodeType::AppointmentCancelled->value,
            NodeType::AppointmentMissed->value,
            NodeType::AppointmentRescheduled->value,
            NodeType::AppointmentCompleted->value,
            NodeType::LabTestOrdered->value,
            NodeType::LabReportReady->value,
            NodeType::LabCompleted->value,
            NodeType::MedicineRefillDue->value,
            NodeType::InvoiceGenerated->value,
            NodeType::PaymentPending->value,
            NodeType::MembershipExpiry->value,
            NodeType::MembershipRenewed->value,
            NodeType::RewardPointsUpdated->value,
            NodeType::RewardTierUpgraded->value,
            NodeType::Birthday->value,
            NodeType::AnniversaryReached->value,
            NodeType::PatientRegistered->value,
            NodeType::ProcedureCompleted->value,
            NodeType::MessageReceived->value,
            NodeType::CampaignTriggered->value,
            NodeType::ScheduledEvent->value,
            NodeType::MedicineReminder->value,
        ], true);
    }

    public static function isEnd(string $nodeType): bool
    {
        return self::normalize($nodeType) === NodeType::End->value;
    }
}
