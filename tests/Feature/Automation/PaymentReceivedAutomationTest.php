<?php

namespace Tests\Feature\Automation;

use App\Models\DoctorBooking;
use App\Models\Invoice;
use App\Models\Transactions;
use App\Modules\Automation\Events\PaymentReceived;
use App\Modules\Automation\Listeners\DispatchHospitalAutomationWorkflow;
use App\Modules\Automation\Services\PaymentReceivedDispatcher;
use App\Modules\Automation\Support\InvoiceAutomationScope;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ExpressionEvaluator;
use App\Modules\Workflow\Services\Runtime\VariableResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\Support\WorkflowAutomationTestCase;

class PaymentReceivedAutomationTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTables();
    }

    public function test_razorpay_style_completed_transaction_dispatches_payment_received(): void
    {
        Event::fake([PaymentReceived::class]);
        [$invoice, $transaction] = $this->createPaidInvoiceAndTransaction('doctor', [
            'payment_method' => 'razorpay',
            'total_amount' => 535.30,
        ]);

        app(PaymentReceivedDispatcher::class)->dispatch($invoice, $transaction, [
            'gateway_payment_id' => 'pay_test_123',
            'currency' => 'INR',
        ]);

        Event::assertDispatched(PaymentReceived::class, function (PaymentReceived $event) use ($invoice, $transaction) {
            $payload = $event->payload();

            return $event->triggerType() === 'paymentReceived'
                && $event->invoice->id === $invoice->id
                && $event->transaction->id === $transaction->id
                && $payload['event_occurrence_id'] === PaymentReceived::occurrenceIdFor($transaction)
                && $payload['event_occurrence_id'] === 'payment-received:transaction:'.$transaction->id
                && (int) $payload['hospital_id'] === 2
                && (int) $payload['organization_id'] === 2
                && $payload['gateway_payment_id'] === 'pay_test_123';
        });
    }

    public function test_hospital_bill_completed_transaction_dispatches_payment_received(): void
    {
        Event::fake([PaymentReceived::class]);
        [$invoice, $transaction] = $this->createPaidInvoiceAndTransaction('package', [
            'payment_method' => 'cash',
            'total_amount' => 990,
        ]);

        app(PaymentReceivedDispatcher::class)->dispatch($invoice, $transaction);

        Event::assertDispatchedTimes(PaymentReceived::class, 1);
        $this->assertSame(2, InvoiceAutomationScope::hospitalId($invoice->fresh()));
    }

    public function test_second_opinion_completed_transaction_dispatches_payment_received(): void
    {
        Event::fake([PaymentReceived::class]);
        [$invoice, $transaction] = $this->createPaidInvoiceAndTransaction('second_opinion', [
            'payment_method' => 'upi',
            'total_amount' => 700,
        ]);

        app(PaymentReceivedDispatcher::class)->dispatch($invoice, $transaction);

        Event::assertDispatchedTimes(PaymentReceived::class, 1);
    }

    public function test_diagnostic_completed_transaction_dispatches_payment_received(): void
    {
        Event::fake([PaymentReceived::class]);
        [$invoice, $transaction] = $this->createPaidInvoiceAndTransaction('diagnostic', [
            'payment_method' => 'card',
            'total_amount' => 1200,
        ]);

        app(PaymentReceivedDispatcher::class)->dispatch($invoice, $transaction);

        Event::assertDispatchedTimes(PaymentReceived::class, 1);
    }

    public function test_failed_pending_cancelled_and_refunded_transactions_do_not_dispatch(): void
    {
        Event::fake([PaymentReceived::class]);
        [$invoice] = $this->createPaidInvoiceAndTransaction('doctor', ['status' => 'completed']);

        foreach (['failed', 'pending', 'cancelled', 'refunded'] as $status) {
            $tx = $this->createTransaction($invoice, ['status' => $status, 'total_amount' => 100]);
            app(PaymentReceivedDispatcher::class)->dispatch($invoice, $tx);
        }

        Event::assertNotDispatched(PaymentReceived::class);
    }

    public function test_zero_amount_and_free_transactions_do_not_dispatch(): void
    {
        Event::fake([PaymentReceived::class]);
        [$invoice] = $this->createPaidInvoiceAndTransaction('doctor');

        $zero = $this->createTransaction($invoice, [
            'status' => 'completed',
            'total_amount' => 0,
            'payment_method' => 'razorpay',
        ]);
        $free = $this->createTransaction($invoice, [
            'status' => 'completed',
            'total_amount' => 100,
            'payment_method' => 'free',
        ]);

        app(PaymentReceivedDispatcher::class)->dispatch($invoice, $zero);
        app(PaymentReceivedDispatcher::class)->dispatch($invoice, $free);

        Event::assertNotDispatched(PaymentReceived::class);
    }

    public function test_payment_received_starts_hospital_scoped_workflow_with_payment_context(): void
    {
        [$invoice, $transaction] = $this->createPaidInvoiceAndTransaction('doctor', [
            'total_amount' => 535.30,
            'payment_method' => 'razorpay',
        ]);

        $version = $this->publishDefinition(
            [
                'builderVersion' => '1',
                'nodes' => [
                    $this->node('t1', 'paymentReceived'),
                    $this->node('c1', 'condition', [
                        'expression' => 'payment.status == "completed" && payment.amount > 500',
                    ]),
                    $this->node('a1', 'sendPush', [
                        'title' => 'Payment Successful',
                        'body' => 'Payment of ₹{{payment_amount}} received successfully. Invoice: #{{invoice_id}}',
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
            'paymentReceived',
            hospitalId: 2,
            organizationId: 2,
        );
        $this->publishDefinition(
            $this->linearGraph('paymentReceived', 'sendPush', [
                'title' => 'Wrong hospital',
                'body' => 'should not run',
            ]),
            'paymentReceived',
            hospitalId: 9,
            organizationId: 2,
        );
        $secondSameHospital = $this->publishDefinition(
            $this->linearGraph('paymentReceived', 'sendEmail', [
                'subject' => 'Fanout',
                'body' => 'also ran {{payment_id}}',
                'recipient' => 'patient',
            ]),
            'paymentReceived',
            hospitalId: 2,
            organizationId: 2,
        );

        app(PaymentReceivedDispatcher::class)->dispatch($invoice, $transaction, [
            'gateway_payment_id' => 'pay_abc',
        ]);

        $executions = WorkflowExecution::query()
            ->where('trigger_type', 'paymentReceived')
            ->get();

        $this->assertCount(2, $executions);
        $this->assertTrue($executions->pluck('workflow_id')->contains($version->workflow_id));
        $this->assertTrue($executions->pluck('workflow_id')->contains($secondSameHospital->workflow_id));
        $this->assertFalse($executions->contains(fn ($row) => (int) $row->workflow_id === 0));

        $execution = $executions->firstWhere('workflow_id', $version->workflow_id);
        $this->assertNotNull($execution);
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->status);
        $this->assertSame('completed', $execution->context['payment']['status'] ?? null);
        $this->assertEquals(535.30, (float) ($execution->context['payment']['amount'] ?? 0));
        $this->assertSame($transaction->id, $execution->context['payment']['id'] ?? null);
        $this->assertSame('TXN-'.str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT), $execution->context['payment']['transaction_id'] ?? null);
        $this->assertSame('pay_abc', $execution->context['payment']['gateway_payment_id'] ?? null);
        $this->assertSame($invoice->id, $execution->context['invoice']['id'] ?? null);
        $this->assertSame('completed', $execution->context['invoice']['status'] ?? null);
        $this->assertSame(2, (int) ($execution->context['hospital_id'] ?? 0));
        $this->assertSame(2, (int) ($execution->context['organization_id'] ?? 0));
        $this->assertSame(PaymentReceived::occurrenceIdFor($transaction), $execution->context['event_occurrence_id'] ?? null);

        $evalContext = new WorkflowContext('paymentReceived', $execution->context);
        $this->assertTrue(app(ExpressionEvaluator::class)->evaluate('payment.status == "completed"', $evalContext));
        $this->assertTrue(app(ExpressionEvaluator::class)->evaluate('payment.amount > 500', $evalContext));
        $this->assertTrue(app(ExpressionEvaluator::class)->evaluate('invoice.status == "completed"', $evalContext));
        $this->assertFalse(app(ExpressionEvaluator::class)->evaluate('payment.amount > 1000', $evalContext));

        $body = app(VariableResolver::class)->resolve(
            'Payment of ₹{{payment_amount}} received successfully. Invoice: #{{invoice_id}}',
            $execution->context
        );
        $this->assertStringContainsString('535.3', $body);
        $this->assertStringContainsString('#'.$invoice->id, $body);

        $this->assertTrue(collect($this->providerSends)->contains(
            fn (array $send) => $send['channel'] === 'push'
                && str_contains((string) $send['message'], (string) $invoice->id)
        ));
        $this->assertTrue(collect($this->providerSends)->contains(
            fn (array $send) => $send['channel'] === 'email'
        ));
    }

    public function test_retry_does_not_duplicate_workflow_execution(): void
    {
        [$invoice, $transaction] = $this->createPaidInvoiceAndTransaction('doctor');
        $version = $this->publishDefinition(
            $this->triggerEndGraph('paymentReceived'),
            'paymentReceived',
            hospitalId: 2,
            organizationId: 2,
        );

        $event = new PaymentReceived($invoice, $transaction, PaymentReceived::occurrenceIdFor($transaction));
        $listener = app(DispatchHospitalAutomationWorkflow::class);
        $listener->handle($event);
        $listener->handle($event);

        $this->assertSame(1, WorkflowExecution::query()
            ->where('workflow_id', $version->workflow_id)
            ->where('trigger_type', 'paymentReceived')
            ->count());
    }

    /**
     * @param  array<string, mixed>  $transactionOverrides
     * @return array{0: Invoice, 1: Transactions}
     */
    protected function createPaidInvoiceAndTransaction(string $type, array $transactionOverrides = []): array
    {
        $this->insertDoctorBooking(40, 2);
        $this->insertSecondOpinion(20, 2);
        $this->insertDiagnosticBooking(42, 2);
        $creator = $this->insertCreator('pay-recv-creator', 2, 2);

        $typed = match ($type) {
            'doctor' => [
                'doctor_booking_id' => 40,
                'service_types' => ['doctor_consultation'],
            ],
            'second_opinion' => [
                'second_opinion_id' => 20,
                'service_types' => ['second_opinion'],
            ],
            'diagnostic' => [
                'diagnostic_test_booking_id' => 42,
                'service_types' => ['diagnostic_package'],
            ],
            default => [
                'created_by' => $creator,
                'service_types' => ['family_package'],
                'invoice_details' => ['family_package' => [['package_name' => 'Bronze', 'amount' => 990]]],
            ],
        };

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
        ], $typed));
        Invoice::withoutEvents(fn () => $invoice->save());

        if (($typed['created_by'] ?? null) !== null) {
            DB::table('invoices')->where('id', $invoice->id)->update(['created_by' => $typed['created_by']]);
        }

        $transaction = $this->createTransaction($invoice->fresh(), $transactionOverrides);

        return [$invoice->fresh(), $transaction];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createTransaction(Invoice $invoice, array $overrides = []): Transactions
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

    protected function insertDoctorBooking(int $id, int $hospitalId): void
    {
        if (! DB::table('doctors')->where('id', 'doc-pay-recv')->exists()) {
            DB::table('doctors')->insert([
                'id' => 'doc-pay-recv',
                'name' => 'Dr Recv',
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
            'doctor_id' => 'doc-pay-recv',
            'patient_id' => 'person-pay-1',
            'status' => DoctorBooking::STATUS_CONFIRMED,
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

    protected function insertCreator(string $id, int $hospitalId, int $organizationId): string
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
                $table->string('status')->nullable();
                $table->timestamps();
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
