<?php

namespace App\Modules\Workflow\Enums;

enum NodeType: string
{
    // Triggers
    case MedicineReminderDue = 'medicineReminderDue';
    case PrescriptionAdded = 'prescriptionAdded';
    case AppointmentBooked = 'appointmentBooked';
    case Birthday = 'birthday';
    case ScheduledEvent = 'scheduledEvent';

    // Legacy medicine reminder node (builder)
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
    case Webhook = 'webhook';
    case DatabaseUpdate = 'databaseUpdate';
    case CreateRecord = 'createRecord';
    case AiPrompt = 'aiPrompt';

    // Hospital automation triggers
    case AppointmentCancelled = 'appointmentCancelled';
    case AppointmentMissed = 'appointmentMissed';
    case AppointmentRescheduled = 'appointmentRescheduled';
    case AppointmentCompleted = 'appointmentCompleted';
    case LabTestOrdered = 'labTestOrdered';
    case LabReportReady = 'labReportReady';
    case LabCompleted = 'labCompleted';
    case MedicineRefillDue = 'medicineRefillDue';
    case InvoiceGenerated = 'invoiceGenerated';
    case PaymentReceived = 'paymentReceived';
    case PaymentPending = 'paymentPending';
    case MembershipExpiry = 'membershipExpiry';
    case MembershipRenewed = 'membershipRenewed';
    case RewardPointsUpdated = 'rewardPointsUpdated';
    case RewardTierUpgraded = 'rewardTierUpgraded';
    case AnniversaryReached = 'anniversaryReached';
    case PatientRegistered = 'patientRegistered';
    case ProcedureCompleted = 'procedureCompleted';
    case MessageReceived = 'messageReceived';
    case CampaignTriggered = 'campaignTriggered';
}
