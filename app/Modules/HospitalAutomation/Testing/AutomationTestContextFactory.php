<?php

namespace App\Modules\HospitalAutomation\Testing;

use App\Models\DoctorBooking;
use App\Models\Persons;

/**
 * Builds automation context that can ONLY address the configured test account.
 * Never loads a real DoctorBooking / Persons model (those would leak patient contacts).
 */
class AutomationTestContextFactory
{
    public const SOURCE = 'automation_test';

    /**
     * @param  list<string>  $channels
     * @return array<string, mixed>
     */
    public function build(
        string $trigger,
        AutomationTestAccount $account,
        array $channels,
        ?int $hospitalId,
        ?int $organizationId,
    ): array {
        $appointmentId = 'automation-test-'.uniqid('', true);

        $patient = [
            'id' => 'automation-test-patient',
            'first_name' => 'Automation',
            'last_name' => 'Test',
            'mobile' => $account->phone,
            'email' => $account->email,
        ];

        $context = [
            'appointment_id' => $appointmentId,
            'patient_id' => 'automation-test-patient',
            'patient' => $patient,
            'patient_mobile' => $account->phone,
            'patient_email' => $account->email,
            'member_id' => $account->userId,
            'member_mobile' => $account->phone,
            'member_email' => $account->email,
            'hospital_id' => $hospitalId,
            'organization_id' => $organizationId,
            'doctor_id' => 'automation-test-doctor',
            'doctor' => ['id' => 'automation-test-doctor', 'name' => 'Automation Test Doctor'],
            'hospital' => [
                'id' => $hospitalId,
                'name' => 'Automation Test Hospital',
                'organization_id' => $organizationId,
            ],
            'appointment_date' => now()->format('Y-m-d'),
            'appointment_time' => '10:00',
            'appointment_status' => 'booked',
            'meta' => [
                'source' => self::SOURCE,
                'automation_test' => true,
                'automation_test_account_id' => $account->id,
                'automation_test_account_label' => $account->label,
                'automation_test_channels' => $channels,
                'automation_test_trigger' => $trigger,
            ],
        ];

        $this->assertSafe($context, $account);

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function assertSafe(array $context, AutomationTestAccount $account): void
    {
        if (($context['appointment'] ?? null) instanceof DoctorBooking) {
            throw new AutomationTestException(
                'Refusing automation test: context must not include a real DoctorBooking (patient leak risk).'
            );
        }

        if (($context['patient'] ?? null) instanceof Persons) {
            throw new AutomationTestException(
                'Refusing automation test: context must not include a real Persons model (patient leak risk).'
            );
        }

        if (($context['meta']['source'] ?? null) !== self::SOURCE) {
            throw new AutomationTestException('Refusing automation test: meta.source must be automation_test.');
        }

        if (filled($account->phone) && ($context['patient_mobile'] ?? null) !== $account->phone) {
            throw new AutomationTestException('Refusing automation test: patient_mobile is not the configured test phone.');
        }

        if (filled($account->email) && ($context['patient_email'] ?? null) !== $account->email) {
            throw new AutomationTestException('Refusing automation test: patient_email is not the configured test email.');
        }

        if (filled($account->userId) && ($context['member_id'] ?? null) !== $account->userId) {
            throw new AutomationTestException('Refusing automation test: member_id is not the configured test user id.');
        }
    }
}
