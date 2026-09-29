<?php

namespace Tests\Feature\Automation;

use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\Invoice;
use App\Models\SecondOpinion;
use App\Models\Transactions;
use App\Modules\Automation\Engine\AutomationContextBuilder;
use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\Events\LabTestOrdered;
use App\Modules\Automation\Events\MembershipRenewed;
use App\Modules\Automation\Events\PaymentReceived;
use App\Modules\Automation\Events\SecondOpinionBooked;
use App\Modules\Automation\Listeners\AppointmentBookedListener;
use App\Modules\Automation\Observers\DiagnosticTestBookingObserver;
use App\Modules\Automation\Observers\DoctorBookingObserver;
use App\Modules\Automation\Observers\SecondOpinionObserver;
use App\Modules\Automation\Services\MembershipRenewedDispatcher;
use App\Modules\Automation\Services\PaymentReceivedDispatcher;
use App\Modules\Automation\TriggerHandlers\HospitalAutomationTriggerService;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ExpressionEvaluator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\Support\WorkflowAutomationTestCase;

class AppointmentLabAutomationTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTables();
    }

    public function test_pending_pay_by_hospital_doctor_booking_exposes_normalized_facts(): void
    {
        $booking = $this->insertDoctorBookingRow(501, 2, [
            'status' => DoctorBooking::STATUS_PENDING,
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
            'total_amount' => 800,
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
        ]);

        $context = app(AutomationContextBuilder::class)->fromAppointment($booking);
        $evaluator = app(ExpressionEvaluator::class);
        $workflowContext = new WorkflowContext('appointmentBooked', $context);

        $this->assertTrue($context['payment']['is_pay_by_hospital']);
        $this->assertSame('unpaid', $context['payment']['status']);
        $this->assertSame(DoctorBooking::STATUS_PENDING, $booking->status);
        $this->assertSame(DoctorBooking::APPOINTMENT_STATUS_NEW, $context['appointment_status']);
        $this->assertTrue($evaluator->evaluate(
            'payment.is_pay_by_hospital == true && appointment.status == "pending"',
            $workflowContext
        ));
        $this->assertFalse($evaluator->evaluate(
            'payment.status == "completed"',
            $workflowContext
        ));
        $this->assertFalse($evaluator->evaluate(
            'appointment.status == "confirmed"',
            $workflowContext
        ));
    }

    public function test_doctor_pending_pay_by_hospital_listener_reaches_engine_and_sends_push(): void
    {
        $version = $this->publishDefinition(
            [
                'builderVersion' => '1',
                'nodes' => [
                    $this->node('t1', 'appointmentBooked'),
                    $this->node('c1', 'condition', [
                        'expression' => 'appointment.booking_type == "doctor" && payment.is_pay_by_hospital == true && appointment.status == "pending"',
                    ]),
                    $this->node('a1', 'sendPush', [
                        'title' => 'Request booked',
                        'body' => 'Hospital will confirm shortly.',
                    ]),
                    $this->node('e1', 'end'),
                ],
                'edges' => [
                    $this->edge('e1', 't1', 'c1'),
                    $this->edge('e2', 'c1', 'a1', 'true'),
                    $this->edge('e3', 'c1', 'e1', 'false'),
                    $this->edge('e4', 'a1', 'e1'),
                ],
            ],
            'appointmentBooked',
            2,
            2
        );

        $booking = $this->insertDoctorBookingRow(511, 2, [
            'status' => DoctorBooking::STATUS_PENDING,
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
        ]);

        $event = new AppointmentBooked($booking);
        $this->assertSame('pending', $event->eventStatus);

        (new AppointmentBookedListener(app(HospitalAutomationTriggerService::class)))->handle($event);

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $version->workflow_id)->count());
        $this->assertCount(1, $this->providerSends);
        $this->assertSame('push', $this->providerSends[0]['channel']);
    }

    public function test_doctor_confirmed_listener_matches_confirmed_pay_by_hospital_condition(): void
    {
        $version = $this->publishDefinition(
            [
                'builderVersion' => '1',
                'nodes' => [
                    $this->node('t1', 'appointmentBooked'),
                    $this->node('c1', 'condition', [
                        'expression' => 'appointment.booking_type == "doctor" && payment.is_pay_by_hospital == true && appointment.status == "confirmed"',
                    ]),
                    $this->node('a1', 'sendPush', [
                        'title' => 'Appointment confirmed',
                        'body' => 'Your appointment is confirmed.',
                    ]),
                    $this->node('e1', 'end'),
                ],
                'edges' => [
                    $this->edge('e1', 't1', 'c1'),
                    $this->edge('e2', 'c1', 'a1', 'true'),
                    $this->edge('e3', 'c1', 'e1', 'false'),
                    $this->edge('e4', 'a1', 'e1'),
                ],
            ],
            'appointmentBooked',
            2,
            2
        );

        $booking = $this->insertDoctorBookingRow(512, 2, [
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
        ]);

        (new AppointmentBookedListener(app(HospitalAutomationTriggerService::class)))
            ->handle(new AppointmentBooked($booking));

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $version->workflow_id)->count());
        $this->assertCount(1, $this->providerSends);
    }


    public function test_online_payment_booking_is_not_pay_by_hospital(): void
    {
        $booking = $this->insertDoctorBookingRow(502, 2, [
            'status' => DoctorBooking::STATUS_PENDING,
            'is_online_payment' => true,
            'payment_status' => 'pending',
        ]);

        $context = app(AutomationContextBuilder::class)->fromAppointment($booking);
        $evaluator = app(ExpressionEvaluator::class);

        $this->assertFalse($context['payment']['is_pay_by_hospital']);
        $this->assertFalse($evaluator->evaluate(
            'payment.is_pay_by_hospital == true',
            new WorkflowContext('appointmentBooked', $context)
        ));
    }

    public function test_pending_then_confirmed_run_as_separate_occurrences(): void
    {
        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendPush', [
                'title' => 'Booking update',
                'body' => 'Status {{appointment_status}}',
            ]),
            'appointmentBooked',
            2,
            2
        );

        $pending = $this->insertDoctorBookingRow(503, 2, [
            'status' => DoctorBooking::STATUS_PENDING,
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
        ]);

        $engine = app(AutomationEngine::class);
        $engine->handle('appointmentBooked', [
            'appointment' => $pending,
            'event_occurrence_id' => AppointmentBooked::occurrenceIdFor($pending),
        ]);

        DB::table('doctor_bookings')->where('id', 503)->update(['status' => DoctorBooking::STATUS_CONFIRMED]);
        $confirmed = DoctorBooking::query()->findOrFail(503);

        $engine->handle('appointmentBooked', [
            'appointment' => $confirmed,
            'event_occurrence_id' => AppointmentBooked::occurrenceIdFor($confirmed),
        ]);

        $this->assertSame(2, WorkflowExecution::query()->where('workflow_id', $version->workflow_id)->count());
        $this->assertNotSame(
            AppointmentBooked::occurrenceIdFor($pending),
            AppointmentBooked::occurrenceIdFor($confirmed)
        );
    }

    public function test_queue_retry_does_not_duplicate_appointment_booked_execution(): void
    {
        $version = $this->publishDefinition(
            $this->triggerEndGraph('appointmentBooked'),
            'appointmentBooked',
            2,
            2
        );

        $booking = $this->insertDoctorBookingRow(504, 2, [
            'status' => DoctorBooking::STATUS_PENDING,
            'is_online_payment' => false,
        ]);
        $payload = [
            'appointment' => $booking,
            'event_occurrence_id' => AppointmentBooked::occurrenceIdFor($booking),
        ];

        $engine = app(AutomationEngine::class);
        $engine->handle('appointmentBooked', $payload);
        $engine->handle('appointmentBooked', $payload);

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $version->workflow_id)->count());
    }

    public function test_pay_by_hospital_pending_condition_runs_push_and_online_does_not(): void
    {
        $version = $this->publishDefinition(
            [
                'builderVersion' => '1',
                'nodes' => [
                    $this->node('t1', 'appointmentBooked'),
                    $this->node('c1', 'condition', [
                        'expression' => 'payment.is_pay_by_hospital == true && appointment.status == "pending"',
                    ]),
                    $this->node('a1', 'sendPush', [
                        'title' => 'Request received',
                        'body' => 'Hospital will confirm shortly.',
                    ]),
                    $this->node('e1', 'end'),
                ],
                'edges' => [
                    $this->edge('e1', 't1', 'c1'),
                    $this->edge('e2', 'c1', 'a1', 'true'),
                    $this->edge('e3', 'c1', 'e1', 'false'),
                    $this->edge('e4', 'a1', 'e1'),
                ],
            ],
            'appointmentBooked',
            2,
            2
        );

        $payHospital = $this->insertDoctorBookingRow(505, 2, [
            'status' => DoctorBooking::STATUS_PENDING,
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
        ]);
        $online = $this->insertDoctorBookingRow(506, 2, [
            'status' => DoctorBooking::STATUS_PENDING,
            'is_online_payment' => true,
            'payment_status' => 'pending',
        ]);

        $engine = app(AutomationEngine::class);
        $engine->handle('appointmentBooked', [
            'appointment' => $payHospital,
            'event_occurrence_id' => AppointmentBooked::occurrenceIdFor($payHospital),
        ]);
        $engine->handle('appointmentBooked', [
            'appointment' => $online,
            'event_occurrence_id' => AppointmentBooked::occurrenceIdFor($online),
        ]);

        $this->assertSame(2, WorkflowExecution::query()->where('workflow_id', $version->workflow_id)->count());
        $this->assertCount(1, $this->providerSends);
        $this->assertSame('push', $this->providerSends[0]['channel']);
    }

    public function test_hospital_isolation_and_same_hospital_fan_out(): void
    {
        $hospitalTwo = $this->publishDefinition(
            $this->triggerEndGraph('appointmentBooked'),
            'appointmentBooked',
            2,
            2
        );
        $this->publishDefinition(
            $this->triggerEndGraph('appointmentBooked'),
            'appointmentBooked',
            9,
            2
        );
        $secondAtTwo = $this->publishDefinition(
            $this->triggerEndGraph('appointmentBooked'),
            'appointmentBooked',
            2,
            2
        );

        $booking = $this->insertDoctorBookingRow(507, 2, [
            'status' => DoctorBooking::STATUS_PENDING,
            'is_online_payment' => false,
        ]);

        app(AutomationEngine::class)->handle('appointmentBooked', [
            'appointment' => $booking,
            'event_occurrence_id' => AppointmentBooked::occurrenceIdFor($booking),
        ]);

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $hospitalTwo->workflow_id)->count());
        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $secondAtTwo->workflow_id)->count());
        $this->assertSame(2, WorkflowExecution::query()->count());
    }

    public function test_second_opinion_pay_by_hospital_and_confirmation_context(): void
    {
        Event::fake([SecondOpinionBooked::class]);

        $pending = $this->insertSecondOpinionRow(601, 2, [
            'status' => 'pending',
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
        ]);
        (new SecondOpinionObserver)->created($pending);
        Event::assertDispatched(SecondOpinionBooked::class);

        $pending->status = 'confirmed';
        $pending->syncChanges();
        (new SecondOpinionObserver)->updated($pending);
        Event::assertDispatchedTimes(SecondOpinionBooked::class, 2);

        $confirmed = $this->insertSecondOpinionRow(602, 2, [
            'status' => 'confirmed',
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
        ]);
        $context = app(AutomationContextBuilder::class)->fromSecondOpinion($confirmed);
        $this->assertTrue($context['payment']['is_pay_by_hospital']);
        $this->assertSame('confirmed', $context['appointment']['status']);
        $this->assertTrue(app(ExpressionEvaluator::class)->evaluate(
            'payment.is_pay_by_hospital == true && appointment.status == "confirmed"',
            new WorkflowContext('appointmentBooked', $context)
        ));
    }

    public function test_lab_test_ordered_pay_by_hospital_pending_and_retry(): void
    {
        Event::fake([LabTestOrdered::class]);

        $order = $this->insertDiagnosticRow(701, 2, [
            'status' => 'pending',
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
            'total_amount' => 400,
        ]);
        (new DiagnosticTestBookingObserver)->created($order);
        Event::assertDispatched(LabTestOrdered::class, function (LabTestOrdered $event) use ($order) {
            $payload = $event->payload();

            return is_array($payload)
                && ($payload['diagnostic_test_booking_id'] ?? null) == $order->id
                && (int) ($payload['hospital_id'] ?? 0) === 2
                && data_get($payload, 'order.status') === 'pending'
                && data_get($payload, 'payment.is_pay_by_hospital') === true
                && ($payload['event_occurrence_id'] ?? null) === LabTestOrdered::occurrenceIdFor($order);
        });

        Event::fake();

        $version = $this->publishDefinition(
            [
                'builderVersion' => '1',
                'nodes' => [
                    $this->node('t1', 'labTestOrdered'),
                    $this->node('c1', 'condition', [
                        'expression' => 'payment.is_pay_by_hospital == true && order.status == "pending"',
                    ]),
                    $this->node('a1', 'sendPush', [
                        'title' => 'Lab ordered',
                        'body' => 'Order {{lab_order_id}}',
                    ]),
                    $this->node('e1', 'end'),
                ],
                'edges' => [
                    $this->edge('e1', 't1', 'c1'),
                    $this->edge('e2', 'c1', 'a1', 'true'),
                    $this->edge('e3', 'c1', 'e1', 'false'),
                    $this->edge('e4', 'a1', 'e1'),
                ],
            ],
            'labTestOrdered',
            2,
            2
        );

        $payload = [
            'diagnostic_test_booking_id' => $order->id,
            'event_occurrence_id' => LabTestOrdered::occurrenceIdFor($order),
        ];
        $engine = app(AutomationEngine::class);
        $engine->handle('labTestOrdered', $payload);
        $engine->handle('labTestOrdered', $payload);

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $version->workflow_id)->count());
        $this->assertSame(
            WorkflowExecutionStatus::Completed->value,
            WorkflowExecution::query()->where('workflow_id', $version->workflow_id)->value('status')
        );
        $this->assertCount(1, $this->providerSends);
    }

    public function test_payment_received_still_handles_completed_monetary_payment(): void
    {
        Event::fake([PaymentReceived::class]);
        $booking = $this->insertDoctorBookingRow(508, 2, [
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'is_online_payment' => true,
            'payment_status' => 'paid',
        ]);
        $invoice = $this->insertInvoice(['doctor_booking_id' => $booking->id, 'status' => 'completed']);
        $transaction = $this->insertTransaction($invoice, [
            'status' => 'completed',
            'payment_method' => 'razorpay',
            'total_amount' => 535.30,
        ]);

        app(PaymentReceivedDispatcher::class)->dispatch($invoice, $transaction);

        Event::assertDispatchedTimes(PaymentReceived::class, 1);
    }

    public function test_family_package_activation_dispatches_membership_renewed_not_hardcoded_push(): void
    {
        Event::fake([MembershipRenewed::class]);

        if (! Schema::hasTable('user_family_subscriptions')) {
            Schema::create('user_family_subscriptions', function ($table) {
                $table->id();
                $table->string('hip_user_id')->nullable();
                $table->unsignedBigInteger('family_package_id')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        DB::table('user_family_subscriptions')->insert([
            'id' => 801,
            'hip_user_id' => 'member-pay-1',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subscription = \App\Models\UserFamilySubscription::query()->find(801);
        app(MembershipRenewedDispatcher::class)->dispatch($subscription);

        Event::assertDispatched(MembershipRenewed::class, function (MembershipRenewed $event) {
            $payload = $event->payload();

            return $event->triggerType() === 'membershipRenewed'
                && ($payload['subscription_id'] ?? null) == 801
                && ($payload['event_occurrence_id'] ?? null) === 'membership-renewed:subscription:801';
        });
    }

    public function test_lab_test_ordered_constructor_requires_array_context_not_model(): void
    {
        $order = $this->insertDiagnosticRow(702, 2, [
            'status' => 'pending',
            'is_online_payment' => false,
        ]);

        $event = new LabTestOrdered(app(AutomationContextBuilder::class)->labTestOrderedEventContext($order));
        $this->assertIsArray($event->payload());
        $this->assertSame($order->id, $event->payload()['diagnostic_test_booking_id']);

        $this->expectException(\TypeError::class);
        new LabTestOrdered($order);
    }

    public function test_lab_automation_dispatch_failure_does_not_remove_diagnostic_booking(): void
    {
        $order = $this->insertDiagnosticRow(703, 2, [
            'status' => 'pending',
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
        ]);

        Event::listen(LabTestOrdered::class, function (): void {
            throw new \RuntimeException('simulated automation failure');
        });

        (new DiagnosticTestBookingObserver)->created($order);

        $this->assertNotNull(DiagnosticTestBooking::query()->find(703));
        $this->assertSame('pending', DiagnosticTestBooking::query()->find(703)?->status);
    }

    public function test_doctor_and_second_opinion_workflows_do_not_cross_fire(): void
    {
        $doctorWorkflow = $this->publishDefinition(
            [
                'builderVersion' => '1',
                'nodes' => [
                    $this->node('t1', 'appointmentBooked'),
                    $this->node('c1', 'condition', [
                        'expression' => 'appointment.booking_type == "doctor" && payment.is_pay_by_hospital == true && appointment.status == "pending"',
                    ]),
                    $this->node('a1', 'sendPush', ['title' => 'Doctor pending', 'body' => 'Doctor']),
                    $this->node('e1', 'end'),
                ],
                'edges' => [
                    $this->edge('e1', 't1', 'c1'),
                    $this->edge('e2', 'c1', 'a1', 'true'),
                    $this->edge('e3', 'c1', 'e1', 'false'),
                    $this->edge('e4', 'a1', 'e1'),
                ],
            ],
            'appointmentBooked',
            2,
            2
        );
        $soWorkflow = $this->publishDefinition(
            [
                'builderVersion' => '1',
                'nodes' => [
                    $this->node('t1', 'appointmentBooked'),
                    $this->node('c1', 'condition', [
                        'expression' => 'appointment.booking_type == "second_opinion" && payment.is_pay_by_hospital == true && appointment.status == "pending"',
                    ]),
                    $this->node('a1', 'sendPush', ['title' => 'SO pending', 'body' => 'Second opinion']),
                    $this->node('e1', 'end'),
                ],
                'edges' => [
                    $this->edge('e1', 't1', 'c1'),
                    $this->edge('e2', 'c1', 'a1', 'true'),
                    $this->edge('e3', 'c1', 'e1', 'false'),
                    $this->edge('e4', 'a1', 'e1'),
                ],
            ],
            'appointmentBooked',
            2,
            2
        );

        $doctor = $this->insertDoctorBookingRow(510, 2, [
            'status' => DoctorBooking::STATUS_PENDING,
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
        ]);
        $so = $this->insertSecondOpinionRow(603, 2, [
            'status' => 'pending',
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
        ]);

        $engine = app(AutomationEngine::class);
        $engine->handle('appointmentBooked', [
            'appointment' => $doctor,
            'event_occurrence_id' => AppointmentBooked::occurrenceIdFor($doctor),
            'meta' => ['booking_type' => 'doctor'],
        ]);
        $engine->handle('appointmentBooked', [
            'second_opinion' => $so,
            'event_occurrence_id' => SecondOpinionBooked::occurrenceIdFor($so),
            'meta' => ['booking_type' => 'second_opinion'],
        ]);

        $this->assertSame(2, WorkflowExecution::query()->where('workflow_id', $doctorWorkflow->workflow_id)->count());
        $this->assertSame(2, WorkflowExecution::query()->where('workflow_id', $soWorkflow->workflow_id)->count());
        $this->assertCount(2, $this->providerSends);
        $titles = array_column($this->providerSends, 'subject');
        $this->assertContains('Doctor pending', $titles);
        $this->assertContains('SO pending', $titles);
        $this->assertSame(1, count(array_filter($titles, fn ($t) => $t === 'Doctor pending')));
        $this->assertSame(1, count(array_filter($titles, fn ($t) => $t === 'SO pending')));
    }

    public function test_legacy_second_opinion_and_diagnostic_direct_notifiers_are_gone(): void
    {
        $this->assertFalse(method_exists(\App\Services\Api\PaymentApiService::class, 'notifySecondOpinionBooking'));
        $this->assertFalse(method_exists(\App\Services\Api\PaymentApiService::class, 'notifyDiagnosticPackageBooking'));
        $this->assertFalse(method_exists(\App\Services\FamilyPackageService::class, 'notifySubscriptionActivated'));
    }

    public function test_observer_create_pending_and_confirm_are_intentional(): void
    {
        Event::fake([AppointmentBooked::class]);
        $observer = new DoctorBookingObserver;
        $observer->created($this->memoryBooking([
            'id' => 509,
            'status' => DoctorBooking::STATUS_PENDING,
        ]));
        $observer->updated($this->statusChange(
            DoctorBooking::STATUS_PENDING,
            DoctorBooking::STATUS_CONFIRMED,
            ['id' => 509]
        ));

        Event::assertDispatchedTimes(AppointmentBooked::class, 2);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function insertDoctorBookingRow(int $id, int $hospitalId, array $overrides = []): DoctorBooking
    {
        DB::table('doctor_bookings')->insert(array_merge([
            'id' => $id,
            'hospital_id' => $hospitalId,
            'branch_id' => $hospitalId,
            'doctor_id' => 'doc-pay-recv',
            'patient_id' => 'person-pay-1',
            'member_id' => 'member-pay-1',
            'status' => DoctorBooking::STATUS_PENDING,
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
            'total_amount' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return DoctorBooking::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function insertSecondOpinionRow(int $id, int $branchId, array $overrides = []): SecondOpinion
    {
        DB::table('second_opinions')->insert(array_merge([
            'id' => $id,
            'branch_id' => $branchId,
            'patient_id' => 'person-pay-1',
            'member_id' => 'member-pay-1',
            'doctor_id' => 'doc-pay-recv',
            'status' => 'pending',
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
            'total_amount' => 700,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return SecondOpinion::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function insertDiagnosticRow(int $id, int $branchId, array $overrides = []): DiagnosticTestBooking
    {
        DB::table('diagnostic_test_bookings')->insert(array_merge([
            'id' => $id,
            'branch_id' => $branchId,
            'patient_id' => 'person-pay-1',
            'member_id' => 'member-pay-1',
            'status' => 'pending',
            'is_online_payment' => false,
            'payment_status' => 'unpaid',
            'total_amount' => 400,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return DiagnosticTestBooking::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function insertInvoice(array $overrides = []): Invoice
    {
        $invoice = new Invoice(array_merge([
            'person_id' => 'person-pay-1',
            'amount' => 500,
            'discount_price' => 0,
            'service_charges' => 15,
            'payment_gateway_charges' => 0,
            'total_gst' => 0,
            'total_amount' => 535.30,
            'status' => 'completed',
            'payment_method' => 'razorpay',
        ], $overrides));
        Invoice::withoutEvents(fn () => $invoice->save());

        return $invoice->fresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function insertTransaction(Invoice $invoice, array $overrides = []): Transactions
    {
        $transaction = new Transactions(array_merge([
            'invoice_id' => $invoice->id,
            'transaction_amount' => 535.30,
            'total_amount' => 535.30,
            'status' => 'completed',
            'payment_method' => 'razorpay',
        ], $overrides));
        $transaction->save();

        return $transaction->fresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function memoryBooking(array $attributes = []): DoctorBooking
    {
        $booking = new DoctorBooking;
        $booking->forceFill(array_merge([
            'id' => 41,
            'hospital_id' => 2,
            'status' => DoctorBooking::STATUS_PENDING,
        ], $attributes));
        $booking->syncOriginal();

        return $booking;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function statusChange(string $from, string $to, array $attributes = []): DoctorBooking
    {
        $booking = $this->memoryBooking(array_merge($attributes, ['status' => $from]));
        $booking->status = $to;
        $booking->syncChanges();

        return $booking;
    }

    protected function ensureTables(): void
    {
        if (! DB::table('organizations')->where('id', 2)->exists()) {
            DB::table('organizations')->insert([
                'id' => 2,
                'org_name' => 'Org Two',
                'org_city' => 'City',
                'org_address' => 'Addr',
                'org_logo' => 'logo',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (['doctors' => function ($table) {
            $table->string('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        }, 'hospitals' => function ($table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('admin_contact')->nullable();
            $table->timestamps();
        }, 'persons' => function ($table) {
            $table->string('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('hip_user_id')->nullable();
            $table->timestamps();
        }] as $name => $callback) {
            if (! Schema::hasTable($name)) {
                Schema::create($name, $callback);
            }
        }

        if (! Schema::hasTable('doctor_bookings')) {
            Schema::create('doctor_bookings', function ($table) {
                $table->id();
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('doctor_id')->nullable();
                $table->string('patient_id')->nullable();
                $table->string('member_id')->nullable();
                $table->string('status')->nullable();
                $table->string('appointment_status')->nullable();
                $table->boolean('is_online_payment')->nullable();
                $table->string('payment_status')->nullable();
                $table->decimal('total_amount', 10, 2)->nullable();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('doctor_bookings', function ($table) {
                foreach ([
                    'member_id' => 'string',
                    'appointment_status' => 'string',
                    'is_online_payment' => 'boolean',
                    'payment_status' => 'string',
                    'total_amount' => 'decimal',
                    'invoice_id' => 'unsignedBigInteger',
                ] as $column => $type) {
                    if (! Schema::hasColumn('doctor_bookings', $column)) {
                        match ($type) {
                            'boolean' => $table->boolean($column)->nullable(),
                            'decimal' => $table->decimal($column, 10, 2)->nullable(),
                            'unsignedBigInteger' => $table->unsignedBigInteger($column)->nullable(),
                            default => $table->string($column)->nullable(),
                        };
                    }
                }
            });
        }

        if (! Schema::hasTable('second_opinions')) {
            Schema::create('second_opinions', function ($table) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('patient_id')->nullable();
                $table->string('member_id')->nullable();
                $table->string('doctor_id')->nullable();
                $table->string('status')->nullable();
                $table->boolean('is_online_payment')->nullable();
                $table->string('payment_status')->nullable();
                $table->decimal('total_amount', 10, 2)->nullable();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('second_opinions', function ($table) {
                foreach (['patient_id', 'member_id', 'doctor_id', 'status', 'payment_status'] as $column) {
                    if (! Schema::hasColumn('second_opinions', $column)) {
                        $table->string($column)->nullable();
                    }
                }
                if (! Schema::hasColumn('second_opinions', 'is_online_payment')) {
                    $table->boolean('is_online_payment')->nullable();
                }
                if (! Schema::hasColumn('second_opinions', 'total_amount')) {
                    $table->decimal('total_amount', 10, 2)->nullable();
                }
                if (! Schema::hasColumn('second_opinions', 'invoice_id')) {
                    $table->unsignedBigInteger('invoice_id')->nullable();
                }
            });
        }

        if (! Schema::hasTable('diagnostic_test_bookings')) {
            Schema::create('diagnostic_test_bookings', function ($table) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('patient_id')->nullable();
                $table->string('member_id')->nullable();
                $table->string('status')->nullable();
                $table->boolean('is_online_payment')->nullable();
                $table->string('payment_status')->nullable();
                $table->decimal('total_amount', 10, 2)->nullable();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->string('package_type')->nullable();
                $table->string('test_type')->nullable();
                $table->string('mobile_number')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('diagnostic_test_bookings', function ($table) {
                foreach (['patient_id', 'member_id', 'status', 'payment_status', 'package_type', 'test_type', 'mobile_number'] as $column) {
                    if (! Schema::hasColumn('diagnostic_test_bookings', $column)) {
                        $table->string($column)->nullable();
                    }
                }
                if (! Schema::hasColumn('diagnostic_test_bookings', 'is_online_payment')) {
                    $table->boolean('is_online_payment')->nullable();
                }
                if (! Schema::hasColumn('diagnostic_test_bookings', 'total_amount')) {
                    $table->decimal('total_amount', 10, 2)->nullable();
                }
                if (! Schema::hasColumn('diagnostic_test_bookings', 'invoice_id')) {
                    $table->unsignedBigInteger('invoice_id')->nullable();
                }
            });
        }

        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function ($table) {
                $table->id();
                $table->string('person_id')->nullable();
                $table->unsignedBigInteger('doctor_booking_id')->nullable();
                $table->unsignedBigInteger('second_opinion_id')->nullable();
                $table->unsignedBigInteger('diagnostic_test_booking_id')->nullable();
                $table->json('service_types')->nullable();
                $table->json('invoice_details')->nullable();
                $table->decimal('total_amount', 10, 2)->nullable();
                $table->decimal('amount', 10, 2)->nullable();
                $table->decimal('service_charges', 10, 2)->nullable();
                $table->decimal('payment_gateway_charges', 10, 2)->nullable();
                $table->decimal('discount_price', 10, 2)->nullable();
                $table->decimal('total_gst', 10, 2)->nullable();
                $table->string('status')->default('pending');
                $table->string('payment_method')->nullable();
                $table->uuid('created_by')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('transactions')) {
            Schema::create('transactions', function ($table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->decimal('transaction_amount', 10, 2)->nullable();
                $table->decimal('total_amount', 10, 2)->nullable();
                $table->string('status')->default('pending');
                $table->string('payment_method')->nullable();
                $table->uuid('created_by')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! DB::table('doctors')->where('id', 'doc-pay-recv')->exists()) {
            DB::table('doctors')->insert([
                'id' => 'doc-pay-recv',
                'name' => 'Dr Recv',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! DB::table('hospitals')->where('id', 2)->exists()) {
            DB::table('hospitals')->insert([
                'id' => 2,
                'organization_id' => 2,
                'name' => 'Hospital Two',
                'address' => '2 Main St',
                'admin_contact' => '111',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! DB::table('hospitals')->where('id', 9)->exists()) {
            DB::table('hospitals')->insert([
                'id' => 9,
                'organization_id' => 2,
                'name' => 'Hospital Nine',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! DB::table('persons')->where('id', 'person-pay-1')->exists()) {
            DB::table('persons')->insert([
                'id' => 'person-pay-1',
                'first_name' => 'Pat',
                'last_name' => 'Paid',
                'mobile' => '9999999999',
                'email' => 'pat@example.com',
                'hip_user_id' => 'member-pay-1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
