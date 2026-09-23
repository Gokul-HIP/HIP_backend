<?php

namespace Tests\Unit;

use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Exceptions\InvalidDelayConfiguration;
use App\Modules\Workflow\Services\Runtime\DelayScheduler;
use Tests\TestCase;

class DelaySchedulerWaitContractTest extends TestCase
{
    public function test_duration_wait_uses_amount_and_unit(): void
    {
        $seconds = app(DelayScheduler::class)->resolveDelaySeconds([
            'waitType' => 'duration',
            'amount' => 2,
            'unit' => 'hours',
        ]);

        $this->assertSame(7200, $seconds);
    }

    public function test_legacy_type_value_still_works(): void
    {
        $seconds = app(DelayScheduler::class)->resolveDelaySeconds([
            'type' => 'minutes',
            'value' => 5,
        ]);

        $this->assertSame(300, $seconds);
    }

    public function test_relative_date_before_field_computes_seconds(): void
    {
        $target = now()->addDays(10)->startOfDay();

        $seconds = app(DelayScheduler::class)->resolveDelaySeconds(
            [
                'waitType' => 'relative_date',
                'relativeDateField' => 'followup.date',
                'relativeOffsetDirection' => 'before',
                'relativeOffsetAmount' => 4,
                'relativeOffsetUnit' => 'days',
            ],
            new WorkflowContext('appointmentCompleted', [
                '_facts' => [
                    'followup' => ['exists' => true, 'date' => $target->toDateTimeString()],
                ],
            ])
        );

        $expected = (int) now()->diffInSeconds($target->copy()->subDays(4), false);
        $this->assertEqualsWithDelta(max(0, $expected), $seconds, 2);
    }

    public function test_relative_date_in_the_past_resumes_immediately(): void
    {
        $seconds = app(DelayScheduler::class)->resolveDelaySeconds(
            [
                'waitType' => 'relative_date',
                'relativeDateField' => 'followup.date',
                'relativeOffsetDirection' => 'on',
                'relativeOffsetAmount' => 0,
                'relativeOffsetUnit' => 'days',
            ],
            new WorkflowContext('appointmentCompleted', [
                'followup' => ['date' => now()->subDay()->toDateTimeString()],
            ])
        );

        $this->assertSame(0, $seconds);
    }

    public function test_invalid_relative_date_field_does_not_become_default_duration(): void
    {
        $this->expectException(InvalidDelayConfiguration::class);

        app(DelayScheduler::class)->resolveDelaySeconds(
            [
                'waitType' => 'relative_date',
                'relativeDateField' => 'followup.date',
                'relativeOffsetDirection' => 'before',
                'relativeOffsetAmount' => 4,
                'relativeOffsetUnit' => 'days',
            ],
            new WorkflowContext('appointmentCompleted', [])
        );
    }
}
