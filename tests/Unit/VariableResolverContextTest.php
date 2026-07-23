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
}
