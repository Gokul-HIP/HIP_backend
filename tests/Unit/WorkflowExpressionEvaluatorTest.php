<?php

namespace Tests\Unit;

use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Exceptions\InvalidExpressionException;
use App\Modules\Workflow\Services\Runtime\ExpressionEvaluator;
use Tests\TestCase;

class WorkflowExpressionEvaluatorTest extends TestCase
{
    private ExpressionEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new ExpressionEvaluator;
    }

    public function test_equality_dotted_paths_and_booleans(): void
    {
        $context = $this->context([
            '_facts' => [
                'appointment' => ['exists' => false],
            ],
        ]);

        $this->assertTrue($this->evaluator->evaluate('appointment.exists == false', $context));
        $this->assertFalse($this->evaluator->evaluate('appointment.exists == true', $context));
    }

    public function test_numeric_and_or_and_parentheses(): void
    {
        $context = $this->context([
            '_facts' => [
                'patient' => ['age' => 70, 'relationship' => 'self', 'gender' => 'female'],
            ],
        ]);

        $this->assertTrue($this->evaluator->evaluate('patient.age >= 60', $context));
        $this->assertFalse($this->evaluator->evaluate('patient.age < 18', $context));
        $this->assertTrue($this->evaluator->evaluate('patient.age < 18 || patient.relationship == "self"', $context));
        $this->assertTrue($this->evaluator->evaluate('(patient.age >= 60) && (patient.gender == "female")', $context));
        $this->assertFalse($this->evaluator->evaluate('patient.age >= 60 && patient.gender == "male"', $context));
    }

    public function test_inequality_and_quoted_strings(): void
    {
        $context = $this->context([
            '_facts' => [
                'appointment' => ['department' => 'Cardiology'],
            ],
        ]);

        $this->assertTrue($this->evaluator->evaluate('appointment.department != "Dentistry"', $context));
        $this->assertFalse($this->evaluator->evaluate('appointment.department == "Dentistry"', $context));
    }

    public function test_malformed_expression_throws(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->evaluator->evaluate('patient.age >=', $this->context([]));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function context(array $payload): WorkflowContext
    {
        return new WorkflowContext('appointmentBooked', $payload);
    }
}
