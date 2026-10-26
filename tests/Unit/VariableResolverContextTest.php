<?php

namespace Tests\Unit;

use App\Modules\Workflow\Services\Runtime\VariableResolver;
use Tests\TestCase;

class VariableResolverContextTest extends TestCase
{
    public function test_resolves_from_array_entities_after_json_cast(): void
    {
        $resolver = new VariableResolver;

        $context = [
            'patient' => [
                'first_name' => 'ADITHI',
                'last_name' => '',
                'mobile' => '9876543210',
            ],
            'doctor' => [
                'name' => 'Elnora Grady',
            ],
            'hospital' => [
                'name' => 'Nano',
            ],
            'appointment' => [
                'id' => 42,
                'booking_date' => '2026-07-22',
                'doctor' => ['name' => 'Elnora Grady'],
                'patient' => ['first_name' => 'ADITHI'],
                'hospital' => ['name' => 'Nano'],
            ],
        ];

        $variables = $resolver->buildVariables($context);

        $this->assertSame('ADITHI', $variables['patient_name']);
        $this->assertSame('Elnora Grady', $variables['doctor_name']);
        $this->assertSame('Nano', $variables['hospital_name']);

        $resolved = $resolver->resolve(
            'Hi {{PatientName}}, your appointment with Dr. {{doctor_name}} at {{HospitalName}}.',
            $context
        );

        $this->assertSame(
            'Hi ADITHI, your appointment with Dr. Elnora Grady at Nano.',
            $resolved
        );
    }

    public function test_falls_back_to_nested_appointment_relations(): void
    {
        $resolver = new VariableResolver;

        $context = [
            'appointment' => [
                'doctor' => ['name' => 'Elnora Grady'],
                'patient' => ['first_name' => 'ADITHI', 'last_name' => 'R'],
                'hospital' => ['name' => 'Nano'],
            ],
        ];

        $variables = $resolver->buildVariables($context);

        $this->assertSame('ADITHI R', $variables['patient_name']);
        $this->assertSame('Elnora Grady', $variables['doctor_name']);
        $this->assertSame('Nano', $variables['hospital_name']);
    }

    public function test_resolves_from_object_entities(): void
    {
        $resolver = new VariableResolver;

        $context = [
            'patient' => (object) ['first_name' => 'ADITHI', 'last_name' => null, 'mobile' => '9'],
            'doctor' => (object) ['name' => 'Elnora Grady'],
            'hospital' => (object) ['name' => 'Nano'],
        ];

        $variables = $resolver->buildVariables($context);

        $this->assertSame('ADITHI', $variables['patient_name']);
        $this->assertSame('Elnora Grady', $variables['doctor_name']);
        $this->assertSame('Nano', $variables['hospital_name']);
    }

    public function test_reward_points_paths_resolve_zero_and_negative_balances(): void
    {
        $resolver = new VariableResolver;
        $context = [
            'patient' => [
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'mobile' => '9876543210',
                'email' => 'ada@example.com',
            ],
            'patient_mobile' => '9876543210',
            'rewards' => [
                'previous_balance' => 0,
                'current_balance' => 5,
            'points_changed' => -4,
            'coins_earned' => 8,
            'change_type' => 'redeemed',
            'source' => 'appointment_redemption',
        ],
        ];

        $message = $resolver->resolve(
            '{{patient_name}} {{PatientName}} {{rewards.previous_balance}} {{rewards.current_balance}} {{rewards.points_changed}} {{rewards.coins_earned}} {{rewards.change_type}} {{rewards.source}}',
            $context
        );

        $this->assertSame('Ada Lovelace Ada Lovelace 0 5 -4 8 redeemed appointment_redemption', $message);
        $this->assertSame(
            '{{rewards.coins_earned}}',
            $resolver->resolve('{{rewards.coins_earned}}', [
                'rewards' => ['coins_earned' => null, 'points_changed' => -4],
            ])
        );
        $this->assertSame(
            '{{rewards.not_a_field}}',
            $resolver->resolve('{{rewards.not_a_field}}', $context)
        );
        $this->assertSame(
            'Patient',
            $resolver->resolve('{{patient_name}}', [
                'patient' => ['first_name' => null, 'last_name' => null],
                'rewards' => $context['rewards'],
            ])
        );
    }

    public function test_payment_amount_root_variable_preserves_decimal_and_unresolved_placeholders(): void
    {
        $resolver = new VariableResolver;

        $this->assertSame('Rs 545.40', $resolver->resolve('Rs {{payment_amount}}', ['payment_amount' => '545.40']));
        $this->assertSame('Payment {{payment_amount}}', $resolver->resolve('Payment {{payment_amount}}', []));
        $this->assertSame('Payment {{payment_amount}}', $resolver->resolve('Payment {{payment_amount}}', ['payment_amount' => null]));
    }
}
