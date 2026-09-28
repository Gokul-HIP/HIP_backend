<?php

namespace Tests\Feature\Automation;

use App\Models\DoctorBooking;
use App\Models\Invoice;
use App\Modules\Automation\Events\PaymentPending;
use App\Modules\Automation\Listeners\DispatchHospitalAutomationWorkflow;
use App\Modules\Automation\Services\PendingPaymentDetector;
use App\Modules\Automation\Support\InvoiceAutomationScope;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\WorkflowExecution;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\Support\WorkflowAutomationTestCase;

class PaymentPendingAutomationTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:00:00', config('app.timezone')));
        $this->ensureTables();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_detector_dispatches_payment_pending_for_eligible_invoice(): void
    {
        Event::fake([PaymentPending::class]);
        $invoice = $this->createPendingInvoice();

        $result = $this->detector()->detectAndDispatch();

        $this->assertSame(1, $result['dispatched']);
        $this->assertNotNull($invoice->fresh()->last_reminder_sent_at);
        Event::assertDispatchedTimes(PaymentPending::class, 1);
        Event::assertDispatched(PaymentPending::class, function (PaymentPending $event) use ($invoice) {
            $payload = $event->payload();

            return $event->invoice->id === $invoice->id
                && $event->triggerType() === 'paymentPending'
                && (int) $payload['hospital_id'] === 2
                && (int) $payload['organization_id'] === 2
                && (int) $payload['invoice_id'] === $invoice->id
                && (int) $payload['appointment_id'] === 40
                && $payload['event_occurrence_id'] === PaymentPending::occurrenceIdFor($invoice, now());
        });
    }

    public function test_invoice_reminded_within_30_minutes_is_not_dispatched_again(): void
    {
        Event::fake([PaymentPending::class]);
        $this->createPendingInvoice([
            'last_reminder_sent_at' => now()->subMinutes(10),
        ]);

        $result = $this->detector()->detectAndDispatch();

        $this->assertSame(0, $result['dispatched']);
        Event::assertNotDispatched(PaymentPending::class);
    }

    public function test_completed_invoice_is_not_dispatched(): void
    {
        Event::fake([PaymentPending::class]);
        $this->createPendingInvoice(['status' => 'completed']);

        $this->detector()->detectAndDispatch();

        Event::assertNotDispatched(PaymentPending::class);
    }

    public function test_second_run_after_30_minutes_dispatches_a_new_occurrence(): void
    {
        Event::fake([PaymentPending::class]);
        $invoice = $this->createPendingInvoice();

        $this->detector()->detectAndDispatch();
        $firstOccurrence = PaymentPending::occurrenceIdFor($invoice, now());

        Carbon::setTestNow(now()->addMinutes(31));
        $this->detector()->detectAndDispatch();
        $secondOccurrence = PaymentPending::occurrenceIdFor($invoice, now());

        $this->assertNotSame($firstOccurrence, $secondOccurrence);
        Event::assertDispatchedTimes(PaymentPending::class, 2);
    }

    public function test_pending_payment_detector_starts_hospital_scoped_workflow_and_action(): void
    {
        $paymentApi = \Mockery::mock(\App\Services\Api\PaymentApiService::class);
        $paymentApi->shouldNotReceive('sendInvoiceNotification');
        $this->app->instance(\App\Services\Api\PaymentApiService::class, $paymentApi);

        $invoice = $this->createPendingInvoice();
        $version = $this->publishDefinition(
            [
                'builderVersion' => '1',
                'nodes' => [
                    $this->node('t1', 'paymentPending'),
                    $this->node('c1', 'condition', [
                        'expression' => 'invoice.status == "pending"',
                    ]),
                    $this->node('a1', 'sendPush', [
                        'title' => 'Payment Reminder',
                        'body' => 'Invoice {{invoice_id}} of {{invoice_amount}} is {{invoice_status}}',
                    ]),
                    $this->node('true_end', 'end'),
                    $this->node('false_end', 'end'),
                ],
                'edges' => [
                    $this->edge('e1', 't1', 'c1'),
                    $this->edge('e2', 'c1', 'a1', 'true'),
                    $this->edge('e3', 'a1', 'true_end'),
                    $this->edge('e4', 'c1', 'false_end', 'false'),
                ],
            ],
            'paymentPending',
            hospitalId: 2,
            organizationId: 2,
        );
        $this->publishDefinition(
            $this->linearGraph('paymentPending', 'sendPush', [
                'title' => 'Wrong hospital',
                'body' => 'should not run',
            ]),
            'paymentPending',
            hospitalId: 9,
            organizationId: 2,
        );

        $this->artisan('hospital-automation:dispatch-pending-payments')->assertSuccessful();

        $execution = WorkflowExecution::query()
            ->where('trigger_type', 'paymentPending')
            ->latest('id')
            ->first();

        $this->assertNotNull($execution);
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->status);
        $this->assertSame('paymentPending', $execution->trigger_type);
        $this->assertSame($version->workflow_id, $execution->workflow_id);
        $this->assertSame($invoice->id, $execution->context['invoice_id'] ?? null);
        $this->assertSame(2, (int) ($execution->context['hospital_id'] ?? 0));
        $this->assertSame(2, (int) ($execution->context['organization_id'] ?? 0));
        $this->assertSame(40, (int) ($execution->context['appointment_id'] ?? 0));
        $this->assertIsArray($execution->context['invoice'] ?? null);
        $this->assertSame($invoice->id, $execution->context['invoice']['id'] ?? null);
        $this->assertSame('pending', $execution->context['invoice']['status'] ?? null);
        $this->assertSame('pending', $execution->context['invoice']['payment_status'] ?? null);
        $this->assertArrayHasKey('status', $execution->context['invoice']);
        $this->assertArrayHasKey('payment_status', $execution->context['invoice']);
        $this->assertEquals(1500, (float) ($execution->context['invoice']['total_amount'] ?? 0));
        $this->assertEquals(500, (float) ($execution->context['invoice']['amount'] ?? 0));
        $this->assertEquals(1500, (float) ($execution->context['invoice_amount'] ?? 0));
        $this->assertSame('pending', $execution->context['invoice_status'] ?? null);
        $this->assertTrue(
            app(\App\Modules\Workflow\Services\Runtime\ExpressionEvaluator::class)->evaluate(
                'invoice.status == "pending"',
                new \App\Modules\Workflow\DTO\WorkflowContext('paymentPending', $execution->context)
            )
        );
        $this->assertSame(
            PaymentPending::occurrenceIdFor($invoice, Carbon::parse('2026-09-28 10:00:00', config('app.timezone'))),
            $execution->context['event_occurrence_id'] ?? null
        );
        $this->assertSame(1, WorkflowExecution::query()->where('trigger_type', 'paymentPending')->count());
        $this->assertTrue(collect($this->providerSends)->contains(
            fn (array $send) => $send['channel'] === 'push'
                && str_contains((string) $send['message'], (string) $invoice->id)
                && str_contains((string) $send['message'], '1500')
        ));
    }

    public function test_same_occurrence_does_not_create_a_second_execution(): void
    {
        $invoice = $this->createPendingInvoice();
        $version = $this->publishDefinition(
            $this->triggerEndGraph('paymentPending'),
            'paymentPending',
            hospitalId: 2,
            organizationId: 2,
        );

        $event = new PaymentPending($invoice, 'payment-pending:invoice:'.$invoice->id.':window:test');
        $listener = app(DispatchHospitalAutomationWorkflow::class);
        $listener->handle($event);
        $listener->handle($event);

        $this->assertSame(1, WorkflowExecution::query()
            ->where('workflow_id', $version->workflow_id)
            ->where('trigger_type', 'paymentPending')
            ->count());
    }

    public function test_legacy_reminders_command_is_still_registered_for_rollback(): void
    {
        $this->assertContains('reminders:pending-payments', array_keys(\Illuminate\Support\Facades\Artisan::all()));
        $this->assertContains('hospital-automation:dispatch-pending-payments', array_keys(\Illuminate\Support\Facades\Artisan::all()));
    }

    public function test_scope_from_direct_doctor_booking(): void
    {
        Event::fake([PaymentPending::class]);
        $invoice = $this->createPendingInvoice();

        $this->assertSame(2, InvoiceAutomationScope::hospitalId($invoice));
        $this->assertSame(2, InvoiceAutomationScope::organizationId($invoice));

        $this->detector()->detectAndDispatch();
        Event::assertDispatched(PaymentPending::class, fn (PaymentPending $event) => (int) $event->payload()['hospital_id'] === 2
            && (int) $event->payload()['organization_id'] === 2);
    }

    public function test_scope_from_second_opinion_branch(): void
    {
        Event::fake([PaymentPending::class]);
        $this->insertSecondOpinion(20, 2);
        $invoice = $this->createBarePendingInvoice([
            'second_opinion_id' => 20,
        ]);

        $this->assertSame(2, InvoiceAutomationScope::hospitalId($invoice));
        $this->assertSame(2, InvoiceAutomationScope::organizationId($invoice));
        $this->detector()->detectAndDispatch();
        Event::assertDispatchedTimes(PaymentPending::class, 1);
    }

    public function test_scope_from_diagnostic_booking_branch(): void
    {
        Event::fake([PaymentPending::class]);
        $this->insertDiagnosticBooking(42, 2);
        $invoice = $this->createBarePendingInvoice([
            'diagnostic_test_booking_id' => 42,
        ]);

        $this->assertSame(2, InvoiceAutomationScope::hospitalId($invoice));
        $this->assertSame(2, InvoiceAutomationScope::organizationId($invoice));
        $this->detector()->detectAndDispatch();
        Event::assertDispatchedTimes(PaymentPending::class, 1);
    }

    public function test_scope_from_creator_hospital(): void
    {
        Event::fake([PaymentPending::class]);
        $creatorId = $this->insertCreator('creator-with-hospital', hospitalId: 2, organizationId: 2);
        $invoice = $this->createBarePendingInvoice([
            'created_by' => $creatorId,
            'invoice_details' => ['lab_test' => [['name' => 'Biopsy', 'amount' => '2000']]],
        ]);

        $this->assertSame(2, InvoiceAutomationScope::hospitalId($invoice));
        $this->assertSame(2, InvoiceAutomationScope::organizationId($invoice));
        $this->detector()->detectAndDispatch();
        Event::assertDispatchedTimes(PaymentPending::class, 1);
    }

    public function test_scope_from_legacy_invoice_details_when_relation_fks_are_null(): void
    {
        Event::fake([PaymentPending::class]);
        $memberId = $this->insertCreator('member-no-hospital', hospitalId: null, organizationId: 2);
        $invoice = $this->createBarePendingInvoice([
            'created_by' => $memberId,
            'invoice_details' => [[
                'service' => 'Doctor Consultation',
                'doctor_booking_id' => 86,
                'doctor_id' => 'doc-missing',
                'branch_id' => 2,
            ]],
        ]);

        $this->assertNull($invoice->doctor_booking_id);
        $this->assertSame(2, InvoiceAutomationScope::hospitalId($invoice));
        $this->assertSame(2, InvoiceAutomationScope::organizationId($invoice));
        $this->detector()->detectAndDispatch();
        Event::assertDispatched(PaymentPending::class, function (PaymentPending $event) use ($invoice) {
            $payload = $event->payload();

            return $event->invoice->id === $invoice->id
                && (int) $payload['hospital_id'] === 2
                && (int) $payload['organization_id'] === 2;
        });
    }

    public function test_scope_from_invoice_details_doctor_booking_when_booking_still_exists(): void
    {
        Event::fake([PaymentPending::class]);
        $this->insertDoctorBooking(86, hospitalId: 2);
        $invoice = $this->createBarePendingInvoice([
            'invoice_details' => [[
                'service' => 'Doctor Consultation',
                'doctor_booking_id' => 86,
                'branch_id' => 2,
            ]],
        ]);

        $this->assertSame(2, InvoiceAutomationScope::hospitalId($invoice));
        $this->detector()->detectAndDispatch();
        Event::assertDispatchedTimes(PaymentPending::class, 1);
    }

    public function test_unscopeable_invoice_remains_skipped(): void
    {
        Event::fake([PaymentPending::class]);
        $memberId = $this->insertCreator('member-unscopeable', hospitalId: null, organizationId: 2);
        $invoice = $this->createBarePendingInvoice([
            'created_by' => $memberId,
            'invoice_details' => [
                'family_package' => [['package_id' => 1, 'package_name' => 'Bronze Package']],
            ],
        ]);

        $this->assertNull(InvoiceAutomationScope::hospitalId($invoice));
        $result = $this->detector()->detectAndDispatch();
        $this->assertSame(0, $result['dispatched']);
        $this->assertSame(1, $result['skipped']);
        $this->assertNull($invoice->fresh()->last_reminder_sent_at);
        Event::assertNotDispatched(PaymentPending::class);
    }

    protected function detector(): PendingPaymentDetector
    {
        return $this->app->make(PendingPaymentDetector::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createBarePendingInvoice(array $attributes = []): Invoice
    {
        $createdBy = $attributes['created_by'] ?? null;
        $invoice = new Invoice(array_merge([
            'person_id' => 'person-pay-1',
            'amount' => 500,
            'total_amount' => 1500,
            'status' => 'pending',
            'last_reminder_sent_at' => null,
        ], $attributes));
        Invoice::withoutEvents(fn () => $invoice->save());

        if ($createdBy !== null) {
            DB::table('invoices')->where('id', $invoice->id)->update(['created_by' => $createdBy]);
        }

        return $invoice->fresh();
    }

    protected function insertDoctorBooking(int $id, int $hospitalId): void
    {
        if (! DB::table('doctors')->where('id', 'doc-pay-1')->exists()) {
            DB::table('doctors')->insert([
                'id' => 'doc-pay-1',
                'name' => 'Dr Pay',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('doctor_bookings')->where('id', $id)->exists()) {
            return;
        }

        DB::table('doctor_bookings')->insert([
            'id' => $id,
            'hospital_id' => $hospitalId,
            'branch_id' => $hospitalId,
            'doctor_id' => 'doc-pay-1',
            'patient_id' => 'person-pay-1',
            'booking_date' => '2026-09-28',
            'required_time_slots' => json_encode(['10:00 AM']),
            'status' => DoctorBooking::STATUS_CONFIRMED,
            'member_id' => 'member-pay-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertSecondOpinion(int $id, int $branchId): void
    {
        if (DB::table('second_opinions')->where('id', $id)->exists()) {
            return;
        }

        DB::table('second_opinions')->insert([
            'id' => $id,
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertDiagnosticBooking(int $id, int $branchId): void
    {
        if (DB::table('diagnostic_test_bookings')->where('id', $id)->exists()) {
            return;
        }

        DB::table('diagnostic_test_bookings')->insert([
            'id' => $id,
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertCreator(string $id, ?int $hospitalId, int $organizationId): string
    {
        if (! DB::table('healthinpocket_users')->where('id', $id)->exists()) {
            DB::table('healthinpocket_users')->insert([
                'id' => $id,
                'email' => $id.'@example.com',
                'password' => 'secret',
                'hospital_id' => $hospitalId,
                'organization_id' => $organizationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $id;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createPendingInvoice(array $attributes = []): Invoice
    {

        $personId = 'person-pay-1';
        if (! DB::table('persons')->where('id', $personId)->exists()) {
            DB::table('persons')->insert([
                'id' => $personId,
                'first_name' => 'Pat',
                'last_name' => 'Pending',
                'mobile' => '9999999999',
                'email' => 'pat@example.com',
                'hip_user_id' => 'member-pay-1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $bookingExists = DB::table('doctor_bookings')->where('id', 40)->exists();
        if (! $bookingExists) {
            if (! DB::table('doctors')->where('id', 'doc-pay-1')->exists()) {
                DB::table('doctors')->insert([
                    'id' => 'doc-pay-1',
                    'name' => 'Dr Pay',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('doctor_bookings')->insert([
                'id' => 40,
                'hospital_id' => 2,
                'branch_id' => 2,
                'doctor_id' => 'doc-pay-1',
                'patient_id' => $personId,
                'booking_date' => '2026-09-28',
                'required_time_slots' => json_encode(['10:00 AM']),
                'status' => DoctorBooking::STATUS_CONFIRMED,
                'member_id' => 'member-pay-1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $invoice = new Invoice(array_merge([
            'person_id' => $personId,
            'doctor_booking_id' => 40,
            'amount' => 500,
            'total_amount' => 1500,
            'status' => 'pending',
            'last_reminder_sent_at' => null,
        ], $attributes));
        Invoice::withoutEvents(fn () => $invoice->save());

        return $invoice->fresh();
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

        if (! Schema::hasTable('doctors')) {
            Schema::create('doctors', function ($table) {
                $table->string('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hospitals')) {
            Schema::create('hospitals', function ($table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('persons')) {
            Schema::create('persons', function ($table) {
                $table->string('id')->primary();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('mobile')->nullable();
                $table->string('email')->nullable();
                $table->string('hip_user_id')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('persons', 'hip_user_id')) {
            Schema::table('persons', function ($table) {
                $table->string('hip_user_id')->nullable();
            });
        }

        if (! Schema::hasTable('doctor_bookings')) {
            Schema::create('doctor_bookings', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('doctor_id')->nullable();
                $table->string('patient_id')->nullable();
                $table->date('booking_date')->nullable();
                $table->json('required_time_slots')->nullable();
                $table->string('status')->nullable();
                $table->string('appointment_status')->nullable();
                $table->string('member_id')->nullable();
                $table->string('mobile_number')->nullable();
                $table->uuid('created_by')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('doctor_bookings', 'branch_id')) {
            Schema::table('doctor_bookings', function ($table) {
                $table->unsignedBigInteger('branch_id')->nullable();
            });
        }

        if (! Schema::hasTable('second_opinions')) {
            Schema::create('second_opinions', function ($table) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('diagnostic_test_bookings')) {
            Schema::create('diagnostic_test_bookings', function ($table) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('healthinpocket_users')) {
            Schema::create('healthinpocket_users', function ($table) {
                $table->string('id')->primary();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function ($table) {
                $table->id();
                $table->string('primary_person_id')->nullable();
                $table->string('person_id')->nullable();
                $table->unsignedBigInteger('doctor_booking_id')->nullable();
                $table->unsignedBigInteger('second_opinion_id')->nullable();
                $table->unsignedBigInteger('diagnostic_test_booking_id')->nullable();
                $table->json('invoice_details')->nullable();
                $table->decimal('total_amount', 10, 2)->nullable();
                $table->decimal('amount', 10, 2)->nullable();
                $table->string('status')->default('pending');
                $table->boolean('is_notified')->default(false);
                $table->timestamp('last_reminder_sent_at')->nullable();
                $table->uuid('created_by')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('invoices', 'invoice_details')) {
            Schema::table('invoices', function ($table) {
                $table->json('invoice_details')->nullable();
            });
        }

        if (! DB::table('hospitals')->where('id', 2)->exists()) {
            DB::table('hospitals')->insert([
                'id' => 2,
                'organization_id' => 2,
                'name' => 'Hospital Two',
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
                'last_name' => 'Pending',
                'mobile' => '9999999999',
                'email' => 'pat@example.com',
                'hip_user_id' => 'member-pay-1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
