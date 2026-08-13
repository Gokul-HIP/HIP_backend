<?php

namespace App\Modules\HospitalAutomation\Support;

final class TriggerCatalog
{
    /** @return array<string, array{module: string, label: string}> */
    public static function all(): array
    {
        return [
            // Module 1 — Appointment
            'appointmentBooked' => ['module' => 'appointment', 'label' => 'Appointment Booked'],
            'appointmentCancelled' => ['module' => 'appointment', 'label' => 'Appointment Cancelled'],
            'appointmentMissed' => ['module' => 'appointment', 'label' => 'Appointment Missed'],
            'appointmentRescheduled' => ['module' => 'appointment', 'label' => 'Appointment Rescheduled'],
            'appointmentCompleted' => ['module' => 'appointment', 'label' => 'Appointment Completed'],
            'appointmentReminder' => ['module' => 'appointment', 'label' => 'Appointment Reminder'],

            // Module 2 — Lab
            'labTestOrdered' => ['module' => 'lab', 'label' => 'Lab Test Ordered'],
            'labReportReady' => ['module' => 'lab', 'label' => 'Lab Report Ready'],
            'labCompleted' => ['module' => 'lab', 'label' => 'Lab Completed'],

            // Module 3 — Pharmacy
            'prescriptionAdded' => ['module' => 'pharmacy', 'label' => 'Prescription Added'],
            'medicineReminderDue' => ['module' => 'pharmacy', 'label' => 'Medicine Reminder Due'],
            'medicineRefillDue' => ['module' => 'pharmacy', 'label' => 'Pharmacy Refill Due'],

            // Module 4 — Billing
            'invoiceGenerated' => ['module' => 'billing', 'label' => 'Invoice Generated'],
            'paymentReceived' => ['module' => 'billing', 'label' => 'Payment Received'],
            'paymentPending' => ['module' => 'billing', 'label' => 'Payment Pending'],

            // Module 5 — Membership
            'membershipExpiry' => ['module' => 'membership', 'label' => 'Hospital Membership Expiry'],
            'membershipRenewed' => ['module' => 'membership', 'label' => 'Membership Renewed'],
            'userPlanExpiry' => ['module' => 'membership', 'label' => 'User Plan Expiry'],
            'rewardPointsUpdated' => ['module' => 'membership', 'label' => 'Reward Points Updated'],
            'rewardTierUpgraded' => ['module' => 'membership', 'label' => 'Rewards Tier Upgraded'],
            'familyPackageTierUpdated' => ['module' => 'membership', 'label' => 'Family Package Plan Tier Updated'],

            // Module 6 — Patient Engagement
            'birthday' => ['module' => 'engagement', 'label' => 'Birthday'],
            'anniversaryReached' => ['module' => 'engagement', 'label' => 'Anniversary'],
            'patientRegistered' => ['module' => 'engagement', 'label' => 'Patient Registered'],

            // Module 7 — Feedback
            'procedureCompleted' => ['module' => 'feedback', 'label' => 'Procedure Completed'],

            // Module 8 — Chat
            'messageReceived' => ['module' => 'chat', 'label' => 'On Message Received'],

            // Module 9 — Integration
            'webhookEvent' => ['module' => 'integration', 'label' => 'Webhook Event'],
            'apiEvent' => ['module' => 'integration', 'label' => 'API Event'],
            'scheduledEvent' => ['module' => 'integration', 'label' => 'Scheduled Event'],

            // Module 10 — Campaign
            'campaignTriggered' => ['module' => 'campaign', 'label' => 'Campaign Triggered'],
        ];
    }

    /** @return array<int, string> */
    public static function types(): array
    {
        return array_keys(self::all());
    }

    /** @return array<string, array<int, string>> */
    public static function byModule(): array
    {
        $grouped = [];
        foreach (self::all() as $type => $meta) {
            $grouped[$meta['module']][] = $type;
        }

        return $grouped;
    }

    public static function moduleFor(string $triggerType): ?string
    {
        return self::all()[$triggerType]['module'] ?? null;
    }

    public static function labelFor(string $triggerType): ?string
    {
        return self::all()[$triggerType]['label'] ?? null;
    }
}
