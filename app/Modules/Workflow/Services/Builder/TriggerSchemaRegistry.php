<?php

namespace App\Modules\Workflow\Services\Builder;

use App\Modules\HospitalAutomation\Support\TriggerCatalog;

class TriggerSchemaRegistry
{
    /** @return array{modules: array<string, array<int, string>>, triggers: array<int, array<string, mixed>>} */
    public function all(): array
    {
        $triggers = [];
        foreach (TriggerCatalog::types() as $type) {
            $triggers[] = $this->forType($type);
        }

        return [
            'modules' => TriggerCatalog::byModule(),
            'triggers' => $triggers,
        ];
    }

    /** @return array<string, mixed> */
    public function forType(string $type): array
    {
        $meta = TriggerCatalog::all()[$type] ?? [
            'module' => 'general',
            'label' => $type,
        ];

        return [
            'type' => $type,
            'label' => $meta['label'],
            'module' => $meta['module'],
            'description' => $this->descriptionFor($type),
            'fields' => $this->fieldsFor($type),
        ];
    }

    protected function descriptionFor(string $type): string
    {
        return match ($type) {
            'messageReceived' => 'Triggers the workflow whenever a new incoming message is received through a supported communication channel.',
            'patientRegistered' => 'Triggers the workflow whenever a patient is registered in the HIP system.',
            'appointmentBooked' => 'Triggers the workflow whenever a new appointment is successfully booked.',
            'appointmentCancelled' => 'Triggers the workflow whenever an existing appointment is cancelled.',
            'appointmentMissed' => 'Triggers the workflow when a scheduled appointment is marked as missed or no-show.',
            'prescriptionAdded' => 'Triggers the workflow whenever a new prescription is added to a patient record.',
            'medicineReminderDue' => 'Triggers the workflow when a scheduled medicine reminder becomes due.',
            'medicineRefillDue' => 'Triggers the workflow when a patient medicine refill is due or approaching.',
            'labTestOrdered' => 'Triggers the workflow whenever a new laboratory test is ordered.',
            'labReportReady' => 'Triggers the workflow when a laboratory report becomes available.',
            'birthday' => 'Triggers the workflow based on a patient birthday.',
            'anniversaryReached' => 'Triggers the workflow based on a configured anniversary date.',
            'membershipExpiry' => 'Triggers when hospital membership is approaching expiry, expires, or has expired.',
            'userPlanExpiry' => 'Triggers based on the expiry date of a user subscription or service plan.',
            'rewardPointsUpdated' => 'Triggers whenever a patient reward points balance is updated.',
            'rewardTierUpgraded' => 'Triggers whenever a patient is upgraded to a higher rewards tier.',
            'familyPackageTierUpdated' => 'Triggers whenever a family package plan tier is changed.',
            'invoiceGenerated' => 'Triggers whenever a new invoice is generated.',
            'paymentReceived' => 'Triggers whenever a payment is successfully received.',
            'webhookEvent' => 'Triggers when an external system sends data to a configured HIP webhook endpoint.',
            'apiEvent' => 'Triggers when an authorized external application sends a configured event through the HIP API.',
            'scheduledEvent' => 'Triggers automatically at a specified date, time, or recurring schedule.',
            default => 'Triggers the workflow when this hospital event occurs.',
        };
    }

    /** @return array<int, array<string, mixed>> */
    protected function fieldsFor(string $type): array
    {
        $triggerName = [
            'name' => 'triggerName',
            'label' => 'Trigger Name',
            'type' => 'text',
            'required' => false,
            'placeholder' => 'Enter trigger name',
        ];

        return match ($type) {
            'messageReceived' => [
                $triggerName,
                ['name' => 'channel', 'label' => 'Channel', 'type' => 'multi-select', 'options' => ['Any', 'WhatsApp', 'Web Chat', 'App Chat']],
                ['name' => 'messageType', 'label' => 'Message Type', 'type' => 'multi-select', 'options' => ['Any', 'Text', 'Image', 'Document', 'Audio', 'Button Reply']],
                ['name' => 'messageMatch', 'label' => 'Message Match', 'type' => 'select', 'options' => ['Any Message', 'Contains', 'Exact Match', 'Starts With']],
                ['name' => 'tags', 'label' => 'Tags', 'type' => 'tags', 'placeholder' => 'appointment, doctor, help'],
            ],
            'patientRegistered' => [
                $triggerName,
                ['name' => 'registrationSource', 'label' => 'Registration Source', 'type' => 'multi-select', 'options' => ['Any', 'App', 'Website', 'WhatsApp', 'Hospital', 'API']],
                ['name' => 'patientType', 'label' => 'Patient Type', 'type' => 'select', 'options' => ['Any', 'New', 'Existing']],
            ],
            'appointmentBooked', 'appointmentCancelled', 'prescriptionAdded', 'labTestOrdered', 'invoiceGenerated' => array_merge([
                $triggerName,
                ['name' => 'source', 'label' => 'Source', 'type' => 'multi-select', 'options' => ['Any', 'Mobile', 'Website', 'Admin']],
            ], $type === 'appointmentCancelled' ? [
                ['name' => 'cancelledBy', 'label' => 'Cancelled By', 'type' => 'multi-select', 'options' => ['Any', 'Patient', 'Doctor', 'Admin']],
            ] : []),
            'appointmentMissed' => [$triggerName],
            'medicineReminderDue' => [
                $triggerName,
                ['name' => 'reminderTiming', 'label' => 'Reminder Timing', 'type' => 'select', 'options' => ['At Due Time', 'Before Due Time']],
                ['name' => 'minutesBefore', 'label' => 'Minutes Before', 'type' => 'number', 'placeholder' => 'Enter minutes before reminder'],
            ],
            'medicineRefillDue' => [
                $triggerName,
                ['name' => 'daysBeforeRefill', 'label' => 'Days Before Refill', 'type' => 'number', 'placeholder' => 'Enter days before refill date'],
            ],
            'labReportReady' => [$triggerName],
            'birthday', 'anniversaryReached' => [
                $triggerName,
                ...($type === 'anniversaryReached' ? [
                    ['name' => 'anniversaryType', 'label' => 'Anniversary Type', 'type' => 'select', 'options' => ['Wedding', 'Registration', 'Membership', 'Custom']],
                ] : []),
                ['name' => 'triggerTiming', 'label' => 'Trigger Timing', 'type' => 'select', 'options' => $type === 'birthday' ? ['On Birthday', 'Before Birthday'] : ['On Date', 'Before Date']],
                ['name' => 'daysBefore', 'label' => 'Days Before', 'type' => 'number'],
                ['name' => 'executionTime', 'label' => 'Execution Time', 'type' => 'time'],
            ],
            'membershipExpiry', 'userPlanExpiry' => [
                $triggerName,
                ['name' => 'triggerTiming', 'label' => 'Trigger Timing', 'type' => 'select', 'options' => ['Before Expiry', 'On Expiry', 'After Expiry']],
                ['name' => 'numberOfDays', 'label' => 'Number of Days', 'type' => 'number'],
                ['name' => 'executionTime', 'label' => 'Execution Time', 'type' => 'time'],
            ],
            'rewardPointsUpdated' => [
                $triggerName,
                ['name' => 'updateType', 'label' => 'Update Type', 'type' => 'multi-select', 'options' => ['Any', 'Points Added', 'Points Redeemed', 'Points Adjusted']],
            ],
            'rewardTierUpgraded' => [$triggerName],
            'familyPackageTierUpdated' => [
                $triggerName,
                ['name' => 'updateType', 'label' => 'Update Type', 'type' => 'select', 'options' => ['Any', 'Upgraded', 'Downgraded']],
            ],
            'paymentReceived' => [
                $triggerName,
                ['name' => 'paymentMode', 'label' => 'Payment Mode', 'type' => 'multi-select', 'options' => ['Any', 'Online', 'Cash', 'Card', 'UPI', 'Bank Transfer']],
                ['name' => 'source', 'label' => 'Source', 'type' => 'multi-select', 'options' => ['Any', 'Mobile', 'Website', 'Admin']],
            ],
            'webhookEvent' => [
                $triggerName,
                ['name' => 'webhookName', 'label' => 'Webhook Name', 'type' => 'text'],
                ['name' => 'webhookUrl', 'label' => 'Webhook URL', 'type' => 'readonly', 'description' => 'Automatically generated by HIP'],
                ['name' => 'httpMethod', 'label' => 'HTTP Method', 'type' => 'select', 'options' => ['POST']],
                ['name' => 'secretKey', 'label' => 'Secret Key', 'type' => 'text'],
                ['name' => 'payloadVariables', 'label' => 'Payload Variables', 'type' => 'key-value'],
            ],
            'apiEvent' => [
                $triggerName,
                ['name' => 'eventName', 'label' => 'Event Name', 'type' => 'text'],
                ['name' => 'apiKey', 'label' => 'API Key', 'type' => 'text'],
                ['name' => 'payloadVariables', 'label' => 'Payload Variables', 'type' => 'key-value'],
            ],
            'scheduledEvent' => [
                $triggerName,
                ['name' => 'scheduleType', 'label' => 'Schedule Type', 'type' => 'select', 'options' => ['Once', 'Recurring']],
                ['name' => 'startDate', 'label' => 'Start Date', 'type' => 'date'],
                ['name' => 'executionTime', 'label' => 'Execution Time', 'type' => 'time'],
                ['name' => 'repeatFrequency', 'label' => 'Repeat Frequency', 'type' => 'select', 'options' => ['Daily', 'Weekly', 'Monthly', 'Yearly', 'Custom']],
                ['name' => 'endCondition', 'label' => 'End Condition', 'type' => 'select', 'options' => ['Never', 'On Date', 'After Number of Runs']],
            ],
            default => [$triggerName],
        };
    }
}
