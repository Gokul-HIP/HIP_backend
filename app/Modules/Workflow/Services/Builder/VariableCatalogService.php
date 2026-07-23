<?php

namespace App\Modules\Workflow\Services\Builder;

class VariableCatalogService
{
    /** @var array<string, array<int, array{key: string, label: string, example: string}>> */
    protected array $groups = [
        'patient' => [
            ['key' => 'PatientName', 'label' => 'Patient Name', 'example' => 'John Doe'],
            ['key' => 'PatientMobile', 'label' => 'Patient Mobile', 'example' => '+919876543210'],
        ],
        'doctor' => [
            ['key' => 'DoctorName', 'label' => 'Doctor Name', 'example' => 'Dr. Smith'],
        ],
        'hospital' => [
            ['key' => 'HospitalName', 'label' => 'Hospital Name', 'example' => 'City Hospital'],
        ],
        'appointment' => [
            ['key' => 'AppointmentDate', 'label' => 'Appointment Date', 'example' => '21 Jul 2026'],
            ['key' => 'AppointmentTime', 'label' => 'Appointment Time', 'example' => '10:30 AM'],
            ['key' => 'AppointmentId', 'label' => 'Appointment ID', 'example' => '1024'],
        ],
        'prescription' => [
            ['key' => 'PrescriptionId', 'label' => 'Prescription ID', 'example' => '501'],
        ],
        'medicine' => [
            ['key' => 'MedicineName', 'label' => 'Medicine Name', 'example' => 'Metformin'],
            ['key' => 'Dosage', 'label' => 'Dosage', 'example' => '500mg'],
            ['key' => 'Frequency', 'label' => 'Frequency', 'example' => 'Twice daily'],
        ],
        'invoice' => [
            ['key' => 'InvoiceId', 'label' => 'Invoice ID', 'example' => 'INV-9001'],
            ['key' => 'InvoiceAmount', 'label' => 'Invoice Amount', 'example' => '1500'],
            ['key' => 'PaymentStatus', 'label' => 'Payment Status', 'example' => 'pending'],
        ],
        'membership' => [
            ['key' => 'MembershipTier', 'label' => 'Membership Tier', 'example' => 'Gold'],
            ['key' => 'MembershipExpiry', 'label' => 'Membership Expiry', 'example' => '31 Dec 2026'],
            ['key' => 'RewardPoints', 'label' => 'Reward Points', 'example' => '250'],
            ['key' => 'CouponCode', 'label' => 'Coupon Code', 'example' => 'BDAY2026'],
        ],
        'lab' => [
            ['key' => 'LabTestName', 'label' => 'Lab Test Name', 'example' => 'CBC'],
            ['key' => 'LabReportId', 'label' => 'Lab Report ID', 'example' => 'LAB-88'],
        ],
        'system' => [
            ['key' => 'Date', 'label' => 'Date', 'example' => '21 Jul 2026'],
            ['key' => 'Time', 'label' => 'Time', 'example' => '04:30 PM'],
            ['key' => 'FeedbackUrl', 'label' => 'Feedback URL', 'example' => 'https://hip.app/feedback/1'],
        ],
        'flow' => [
            ['key' => 'AiSummary', 'label' => 'AI Summary', 'example' => 'Patient lab summary...'],
        ],
    ];

    /** @var array<string, array<int, string>> */
    protected array $triggerGroups = [
        'appointmentBooked' => ['patient', 'doctor', 'hospital', 'appointment', 'system'],
        'appointmentCancelled' => ['patient', 'doctor', 'hospital', 'appointment', 'system'],
        'appointmentMissed' => ['patient', 'doctor', 'hospital', 'appointment', 'system'],
        'appointmentRescheduled' => ['patient', 'doctor', 'hospital', 'appointment', 'system'],
        'appointmentCompleted' => ['patient', 'doctor', 'hospital', 'appointment', 'system', 'flow'],
        'labTestOrdered' => ['patient', 'hospital', 'lab', 'system'],
        'labReportReady' => ['patient', 'hospital', 'lab', 'system', 'flow'],
        'labCompleted' => ['patient', 'hospital', 'lab', 'system'],
        'prescriptionAdded' => ['patient', 'doctor', 'hospital', 'prescription', 'medicine', 'system'],
        'medicineReminderDue' => ['patient', 'hospital', 'medicine', 'system'],
        'medicineRefillDue' => ['patient', 'hospital', 'medicine', 'system'],
        'invoiceGenerated' => ['patient', 'hospital', 'invoice', 'system'],
        'paymentReceived' => ['patient', 'hospital', 'invoice', 'system'],
        'paymentPending' => ['patient', 'hospital', 'invoice', 'system'],
        'membershipExpiry' => ['patient', 'membership', 'system'],
        'membershipRenewed' => ['patient', 'membership', 'system'],
        'userPlanExpiry' => ['patient', 'membership', 'system'],
        'rewardPointsUpdated' => ['patient', 'membership', 'system'],
        'rewardTierUpgraded' => ['patient', 'membership', 'system'],
        'familyPackageTierUpdated' => ['patient', 'membership', 'system'],
        'birthday' => ['patient', 'hospital', 'membership', 'system'],
        'anniversaryReached' => ['patient', 'hospital', 'membership', 'system'],
        'patientRegistered' => ['patient', 'hospital', 'system'],
        'procedureCompleted' => ['patient', 'doctor', 'hospital', 'system', 'flow'],
        'messageReceived' => ['patient', 'system', 'flow'],
        'campaignTriggered' => ['patient', 'hospital', 'membership', 'system'],
        'webhookEvent' => ['patient', 'system', 'flow'],
        'apiEvent' => ['patient', 'system', 'flow'],
        'scheduledEvent' => ['patient', 'system', 'flow'],
    ];

    /**
     * @return array{trigger: ?string, groups: array<string, array<int, array{key: string, label: string, example: string}>>}
     */
    public function forTrigger(?string $trigger = null): array
    {
        if ($trigger === null || $trigger === '') {
            return [
                'trigger' => null,
                'groups' => $this->groups,
            ];
        }

        $allowed = $this->triggerGroups[$trigger] ?? array_keys($this->groups);
        $filtered = [];
        foreach ($allowed as $groupKey) {
            if (isset($this->groups[$groupKey])) {
                $filtered[$groupKey] = $this->groups[$groupKey];
            }
        }

        return [
            'trigger' => $trigger,
            'groups' => $filtered,
        ];
    }
}
