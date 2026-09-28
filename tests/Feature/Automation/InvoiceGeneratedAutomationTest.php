<?php

namespace Tests\Feature\Automation;

use App\Models\DoctorBooking;
use App\Models\Invoice;
use App\Modules\Automation\Events\InvoiceGenerated;
use App\Modules\Automation\Listeners\DispatchHospitalAutomationWorkflow;
use App\Modules\Automation\Services\InvoiceGeneratedDispatcher;
use App\Modules\Automation\Support\InvoiceAutomationScope;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ExpressionEvaluator;
use App\Services\InvoiceDocumentService;
use App\Services\InvoiceFeeCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\Support\WorkflowAutomationTestCase;

class InvoiceGeneratedAutomationTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'settings.fees.service_charges' => 3,
            'settings.fees.payment_gateway_charges' => 2,
            'settings.fees.gst_percent' => 5,
        ]);
        $this->ensureTables();
    }

    public function test_booking_fee_breakdown_matches_configured_percentages(): void
    {
        $breakdown = app(InvoiceFeeCalculator::class)->bookingBreakdown(1000, 100);

        $this->assertSame(1000.0, $breakdown['original_amount']);
        $this->assertSame(100.0, $breakdown['discount_amount']);
        $this->assertSame(900.0, $breakdown['discounted_amount']);
        $this->assertSame(27.0, $breakdown['service_charges']);
        $this->assertSame(0.0, $breakdown['payment_gateway_charges']);
        $this->assertSame(0.0, $breakdown['gst_amount']);
        $this->assertSame(927.0, $breakdown['invoice_total']);
    }

    public function test_doctor_booking_paid_invoice_dispatches_invoice_generated(): void
    {
        Event::fake([InvoiceGenerated::class]);
        $invoice = $this->createPaidServiceInvoice('doctor');

        $this->finalize($invoice);

        Event::assertDispatched(InvoiceGenerated::class, function (InvoiceGenerated $event) use ($invoice) {
            $payload = $event->payload();

            return $event->triggerType() === 'invoiceGenerated'
                && $event->invoice->id === $invoice->id
                && (int) $payload['hospital_id'] === 2
                && (int) $payload['organization_id'] === 2
                && $payload['payment_status'] === 'completed'
                && $payload['event_occurrence_id'] === InvoiceGenerated::occurrenceIdFor($invoice);
        });
    }

    public function test_family_package_paid_invoice_dispatches_invoice_generated(): void
    {
        Event::fake([InvoiceGenerated::class]);
        $invoice = $this->createPaidServiceInvoice('package');
        $this->finalize($invoice);
        Event::assertDispatchedTimes(InvoiceGenerated::class, 1);
    }

    public function test_second_opinion_paid_invoice_dispatches_invoice_generated(): void
    {
        Event::fake([InvoiceGenerated::class]);
        $invoice = $this->createPaidServiceInvoice('second_opinion');
        $this->finalize($invoice);
        Event::assertDispatchedTimes(InvoiceGenerated::class, 1);
        $this->assertSame(2, InvoiceAutomationScope::hospitalId($invoice->fresh()));
    }

    public function test_diagnostic_paid_invoice_dispatches_invoice_generated(): void
    {
        Event::fake([InvoiceGenerated::class]);
        $invoice = $this->createPaidServiceInvoice('diagnostic');
        $this->finalize($invoice);
        Event::assertDispatchedTimes(InvoiceGenerated::class, 1);
    }

    public function test_pending_and_failed_invoices_do_not_dispatch(): void
    {
        Event::fake([InvoiceGenerated::class]);
        $pending = $this->createPaidServiceInvoice('doctor', ['status' => 'pending']);
        $failed = $this->createPaidServiceInvoice('doctor', ['status' => 'failed']);

        app(InvoiceGeneratedDispatcher::class)->dispatch($pending);
        app(InvoiceGeneratedDispatcher::class)->dispatch($failed);

        Event::assertNotDispatched(InvoiceGenerated::class);
    }

    public function test_invoice_generated_starts_hospital_scoped_workflow_with_frontend_context(): void
    {
        $invoice = $this->createPaidServiceInvoice('doctor');
        $version = $this->publishDefinition(
            [
                'builderVersion' => '1',
                'nodes' => [
                    $this->node('t1', 'invoiceGenerated'),
                    $this->node('c1', 'condition', [
                        'expression' => 'invoice.status == "completed"',
                    ]),
                    $this->node('a1', 'sendPush', [
                        'title' => 'Invoice',
                        'body' => 'Invoice {{invoice_id}} total {{invoice_amount}}',
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
            'invoiceGenerated',
            hospitalId: 2,
            organizationId: 2,
        );
        $this->publishDefinition(
            $this->linearGraph('invoiceGenerated', 'sendPush', [
                'title' => 'Wrong hospital',
                'body' => 'should not run',
            ]),
            'invoiceGenerated',
            hospitalId: 9,
            organizationId: 2,
        );

        $this->finalize($invoice);

        $execution = WorkflowExecution::query()
            ->where('trigger_type', 'invoiceGenerated')
            ->latest('id')
            ->first();

        $this->assertNotNull($execution);
        $this->assertSame($version->workflow_id, $execution->workflow_id);
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->status);
        $this->assertSame($invoice->id, $execution->context['invoice']['id'] ?? null);
        $this->assertSame('completed', $execution->context['invoice']['status'] ?? null);
        $this->assertEquals(927, (float) ($execution->context['invoice']['total_amount'] ?? 0));
        $this->assertSame('completed', $execution->context['invoice']['payment_status'] ?? null);
        $this->assertEquals(1000, (float) ($execution->context['original_amount'] ?? 0));
        $this->assertEquals(100, (float) ($execution->context['discount_amount'] ?? 0));
        $this->assertEquals(900, (float) ($execution->context['discounted_amount'] ?? 0));
        $this->assertEquals(27, (float) ($execution->context['service_charges'] ?? 0));
        $this->assertSame(2, (int) ($execution->context['hospital_id'] ?? 0));
        $this->assertSame(2, (int) ($execution->context['organization_id'] ?? 0));
        $this->assertTrue(
            app(ExpressionEvaluator::class)->evaluate(
                'invoice.status == "completed"',
                new WorkflowContext('invoiceGenerated', $execution->context)
            )
        );
        $this->assertSame(1, WorkflowExecution::query()->where('trigger_type', 'invoiceGenerated')->count());
        $this->assertTrue(collect($this->providerSends)->contains(
            fn (array $send) => $send['channel'] === 'push'
                && str_contains((string) $send['message'], (string) $invoice->id)
                && str_contains((string) $send['message'], '927')
        ));
    }

    public function test_retry_does_not_duplicate_workflow_execution(): void
    {
        $invoice = $this->createPaidServiceInvoice('doctor');
        $version = $this->publishDefinition(
            $this->triggerEndGraph('invoiceGenerated'),
            'invoiceGenerated',
            hospitalId: 2,
            organizationId: 2,
        );

        $event = new InvoiceGenerated($invoice, InvoiceGenerated::occurrenceIdFor($invoice));
        $listener = app(DispatchHospitalAutomationWorkflow::class);
        $listener->handle($event);
        $listener->handle($event);

        $this->assertSame(1, WorkflowExecution::query()
            ->where('workflow_id', $version->workflow_id)
            ->where('trigger_type', 'invoiceGenerated')
            ->count());
    }

    public function test_invoice_template_includes_hospital_id(): void
    {
        $invoice = $this->createPaidServiceInvoice('doctor');
        $html = app(InvoiceDocumentService::class)->renderHtml($invoice);

        $this->assertStringContainsString('Hospital ID:', $html);
        $this->assertStringContainsString('2', $html);
        $this->assertSame('2', app(InvoiceDocumentService::class)->placeholders($invoice)['hospital_id']);
    }

    protected function finalize(Invoice $invoice): void
    {
        $invoice->update(['status' => 'completed', 'payment_method' => 'razorpay']);
        app(InvoiceGeneratedDispatcher::class)->dispatch($invoice->fresh());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createPaidServiceInvoice(string $type, array $overrides = []): Invoice
    {
        $fees = app(InvoiceFeeCalculator::class)->bookingBreakdown(1000, 100);

        $this->insertDoctorBooking(40, 2);
        $this->insertSecondOpinion(20, 2);
        $this->insertDiagnosticBooking(42, 2);
        $creator = $this->insertCreator('pkg-creator', 2, 2);

        $base = [
            'person_id' => 'person-pay-1',
            'amount' => $fees['original_amount'],
            'discount_price' => $fees['discount_amount'],
            'service_charges' => $fees['service_charges'],
            'payment_gateway_charges' => $fees['payment_gateway_charges'],
            'total_gst' => $fees['gst_amount'],
            'total_amount' => $fees['invoice_total'],
            'status' => 'pending',
            'payment_method' => null,
        ];

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
                'invoice_details' => ['family_package' => [['package_name' => 'Bronze', 'amount' => 927]]],
            ],
        };

        $invoice = new Invoice(array_merge($base, $typed, $overrides));
        Invoice::withoutEvents(fn () => $invoice->save());

        if (($typed['created_by'] ?? null) !== null) {
            DB::table('invoices')->where('id', $invoice->id)->update(['created_by' => $typed['created_by']]);
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
