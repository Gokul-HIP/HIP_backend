<?php

namespace App\Modules\Workflow\Enums;

enum NodeType: string
{
    // Triggers (canonical IDs — keep in sync with TriggerCatalog)
    case MedicineReminderDue = 'medicineReminderDue';
    case PrescriptionAdded = 'prescriptionAdded';
    case AppointmentBooked = 'appointmentBooked';
    case AppointmentCancelled = 'appointmentCancelled';
    case AppointmentMissed = 'appointmentMissed';
    case AppointmentRescheduled = 'appointmentRescheduled';
    case AppointmentCompleted = 'appointmentCompleted';
    case AppointmentReminder = 'appointmentReminder';
    case LabTestOrdered = 'labTestOrdered';
    case LabReportReady = 'labReportReady';
    case LabCompleted = 'labCompleted';
    case MedicineRefillDue = 'medicineRefillDue';
    case InvoiceGenerated = 'invoiceGenerated';
    case PaymentReceived = 'paymentReceived';
    case PaymentPending = 'paymentPending';
    case MembershipExpiry = 'membershipExpiry';
    case MembershipRenewed = 'membershipRenewed';
    case UserPlanExpiry = 'userPlanExpiry';
    case RewardPointsUpdated = 'rewardPointsUpdated';
    case RewardTierUpgraded = 'rewardTierUpgraded';
    case FamilyPackageTierUpdated = 'familyPackageTierUpdated';
    case Birthday = 'birthday';
    case AnniversaryReached = 'anniversaryReached';
    case PatientRegistered = 'patientRegistered';
    case ProcedureCompleted = 'procedureCompleted';
    case MessageReceived = 'messageReceived';
    case WebhookEvent = 'webhookEvent';
    case ApiEvent = 'apiEvent';
    case ScheduledEvent = 'scheduledEvent';
    case CampaignTriggered = 'campaignTriggered';

    // Legacy medicine reminder node (builder) — aliased to medicineReminderDue via NodeTypeNormalizer
    case MedicineReminder = 'medicineReminder';

    // Flow
    case Condition = 'condition';
    case Delay = 'delay';
    case End = 'end';

    // Actions
    case SendWhatsApp = 'sendWhatsApp';
    case SendEmail = 'sendEmail';
    case SendPush = 'sendPush';
    case SendSms = 'sendSMS';
    case SendTemplate = 'sendTemplate';
    case SendAiChat = 'sendAiChat';
    case SendAiVoice = 'sendAiVoice';
    case Webhook = 'webhook';
    case DatabaseUpdate = 'databaseUpdate';
    case CreateRecord = 'createRecord';
    case DbDelete = 'dbDelete';
    case AiPrompt = 'aiPrompt';
}
