<?php

namespace Tests\Feature\Automation;

use App\Models\Invoice;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use App\Services\InvoiceDocumentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\Support\WorkflowAutomationTestCase;

class InvoiceEmailPdfAttachmentTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureInvoiceTable();
    }

    public function test_send_email_attaches_invoice_pdf_without_changing_subject_or_body(): void
    {
        $invoice = $this->createInvoice();
        $this->mockInvoicePdf('%PDF-FAKE-'.$invoice->id);

        $body = "Payment received successfully at Nano.\n\n"
            ."Invoice: #{{invoice_id}}\n"
            ."Amount: ₹{{invoice_amount}}\n\n"
            ."Your invoice has been generated successfully.\n"
            ."Thank you for your payment.";

        $version = $this->publishDefinition(
            $this->linearGraph('invoiceGenerated', 'sendEmail', [
                'subject' => 'Invoice',
                'body' => $body,
                'recipient' => 'patient',
                'attachInvoicePdf' => true,
            ]),
            'invoiceGenerated',
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'invoiceGenerated',
            $this->sampleAppointmentContext([
                'invoice_id' => $invoice->id,
                'invoice_amount' => '535.30',
                'patient_email' => 'ada@example.com',
            ])
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertCount(1, $this->providerSends);
        $this->assertSame('email', $this->providerSends[0]['channel']);
        $this->assertSame('Invoice', $this->providerSends[0]['subject']);
        $this->assertSame(
            "Payment received successfully at Nano.\n\n"
            ."Invoice: #{$invoice->id}\n"
            ."Amount: ₹535.30\n\n"
            ."Your invoice has been generated successfully.\n"
            ."Thank you for your payment.",
            $this->providerSends[0]['message']
        );
        $this->assertStringNotContainsString('invoice_pdf', $this->providerSends[0]['message']);
        $this->assertStringNotContainsString('%PDF', $this->providerSends[0]['message']);
        $this->assertCount(1, $this->providerSends[0]['attachments'] ?? []);
        $this->assertSame('invoice-'.$invoice->id.'.pdf', $this->providerSends[0]['attachments'][0]['filename']);
        $this->assertSame('%PDF-FAKE-'.$invoice->id, $this->providerSends[0]['attachments'][0]['content']);
        $this->assertSame('application/pdf', $this->providerSends[0]['attachments'][0]['mime']);
    }

    public function test_send_email_without_attach_flag_does_not_attach_pdf(): void
    {
        $invoice = $this->createInvoice();
        $docs = Mockery::mock(InvoiceDocumentService::class)->makePartial();
        $docs->shouldReceive('renderPdfBinary')->never();
        $this->app->instance(InvoiceDocumentService::class, $docs);
        $this->rebindRuntime();

        $version = $this->publishDefinition(
            $this->linearGraph('invoiceGenerated', 'sendEmail', [
                'subject' => 'Invoice',
                'body' => 'Payment received successfully at Nano.',
                'recipient' => 'patient',
            ]),
            'invoiceGenerated',
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'invoiceGenerated',
            $this->sampleAppointmentContext(['invoice_id' => $invoice->id])
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame([], $this->providerSends[0]['attachments'] ?? []);
        $this->assertSame('Payment received successfully at Nano.', $this->providerSends[0]['message']);
    }

    public function test_attach_invoice_pdf_fails_when_invoice_id_missing(): void
    {
        $this->mockInvoicePdf('%PDF-should-not-send%');

        $version = $this->publishDefinition(
            $this->linearGraph('invoiceGenerated', 'sendEmail', [
                'subject' => 'Invoice',
                'body' => 'Payment received successfully at Nano.',
                'recipient' => 'patient',
                'attachInvoicePdf' => true,
            ]),
            'invoiceGenerated',
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'invoiceGenerated',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('invoice_id is required', (string) $execution->fresh()->failure_reason);
        $this->assertCount(0, $this->providerSends);
    }

    public function test_attach_invoice_pdf_fails_when_generation_fails(): void
    {
        $invoice = $this->createInvoice();
        $this->mockInvoicePdf(new \RuntimeException('renderer crashed'));

        $version = $this->publishDefinition(
            $this->linearGraph('invoiceGenerated', 'sendEmail', [
                'subject' => 'Invoice',
                'body' => 'Payment received successfully at Nano.',
                'recipient' => 'patient',
                'attach_invoice_pdf' => true,
            ]),
            'invoiceGenerated',
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'invoiceGenerated',
            $this->sampleAppointmentContext(['invoice_id' => $invoice->id])
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('failed to generate invoice PDF', (string) $execution->fresh()->failure_reason);
        $this->assertCount(0, $this->providerSends);
    }

    public function test_attach_invoice_pdf_fails_when_invoice_row_is_missing(): void
    {
        $this->mockInvoicePdf('%PDF-should-not-send%');

        $version = $this->publishDefinition(
            $this->linearGraph('invoiceGenerated', 'sendEmail', [
                'subject' => 'Invoice',
                'body' => 'Payment received successfully at Nano.',
                'recipient' => 'patient',
                'attachInvoicePdf' => true,
            ]),
            'invoiceGenerated',
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'invoiceGenerated',
            $this->sampleAppointmentContext(['invoice_id' => 999999])
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('invoice not found', (string) $execution->fresh()->failure_reason);
        $this->assertCount(0, $this->providerSends);
    }

    protected function mockInvoicePdf(string|\Throwable $result): void
    {
        $docs = Mockery::mock(InvoiceDocumentService::class)->makePartial();
        if ($result instanceof \Throwable) {
            $docs->shouldReceive('renderPdfBinary')->andThrow($result);
        } else {
            $docs->shouldReceive('renderPdfBinary')->andReturn($result);
        }
        $this->app->instance(InvoiceDocumentService::class, $docs);
        $this->rebindRuntime();
    }

    protected function rebindRuntime(): void
    {
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\InvoicePdfAttachmentService::class);
        $this->bindNotificationMocks(success: true);
    }

    protected function createInvoice(): Invoice
    {
        $invoice = new Invoice([
            'person_id' => 'person-pay-1',
            'amount' => 500,
            'discount_price' => 0,
            'service_charges' => 15,
            'payment_gateway_charges' => 0,
            'total_gst' => 0,
            'total_amount' => 535.30,
            'status' => 'completed',
        ]);
        Invoice::withoutEvents(fn () => $invoice->save());

        return $invoice->fresh();
    }

    protected function ensureInvoiceTable(): void
    {
        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
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
                $table->timestamps();
            });
        }
    }
}
