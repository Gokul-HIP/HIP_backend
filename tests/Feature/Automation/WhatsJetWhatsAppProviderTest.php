<?php

namespace Tests\Feature\Automation;

use App\Models\Invoice;
use App\Models\Prescription;
use App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService;
use App\Modules\MedicineReminder\Notifications\WhatsJetClient;
use App\Modules\Workflow\Enums\CommunicationStatus;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Models\CommunicationLog;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use App\Modules\Workflow\NodeProcessorRegistry;
use App\Services\InvoiceDocumentService;
use App\Services\PrescriptionDocumentService;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\Support\WorkflowAutomationTestCase;

class WhatsJetWhatsAppProviderTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://files.example.com',
            'filesystems.disks.public.url' => 'https://files.example.com/storage',
            'services.whatsapp.base_url' => 'https://whatsjet.test/api',
            'services.whatsapp.vendor_uid' => 'vendor-uid',
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.default_country_code' => '91',
            'services.whatsapp.media_disk' => 'public',
            'services.whatsapp.timeout' => 10,
        ]);

        Storage::fake('public');
        $this->bindRealWhatsApp();
    }

    public function test_send_text_uses_correct_endpoint_auth_vendor_and_resolved_body(): void
    {
        Http::fake([
            'https://whatsjet.test/api/vendor-uid/contact/send-message' => Http::response([
                'result' => 'success',
                'data' => [
                    'log_uid' => 'log-1',
                    'contact_uid' => 'c-1',
                    'phone_number' => '919999999999',
                    'wamid' => 'wamid-1',
                    'status' => 'sent',
                ],
            ], 200),
        ]);

        $body = "Your appointment request has been received.\n\n"
            ."Doctor: {{doctor_name}}\n"
            ."Date: {{appointment_date}}\n"
            ."Time: {{appointment_time}}\n\n"
            .'The hospital will confirm your appointment shortly.';

        $execution = $this->runWhatsAppNode([
            'messageTemplate' => $body,
            'recipient' => 'patient',
        ]);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);

        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://whatsjet.test/api/vendor-uid/contact/send-message'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['phone_number'] === '919999999999'
                && str_contains((string) $request['message_body'], 'Dr Green')
                && str_contains((string) $request['message_body'], '01 Sep 2026')
                && str_contains((string) $request['message_body'], '10:30 AM')
                && ! str_contains((string) $request['message_body'], '{{');
        });

        $log = CommunicationLog::query()->first();
        $this->assertNotNull($log);
        $this->assertSame(CommunicationStatus::Sent->value, $log->status);
        $this->assertStringContainsString('log-1', (string) $log->provider_response);
        $this->assertStringContainsString('wamid-1', (string) $log->provider_response);
        $this->assertStringNotContainsString('test-token', (string) $log->provider_response);
        $this->assertArrayNotHasKey('token', $log->payload ?? []);
    }

    public function test_provider_failure_marks_node_failed(): void
    {
        Http::fake([
            'https://whatsjet.test/api/vendor-uid/contact/send-message' => Http::response([
                'result' => 'failed',
                'message' => 'inactive vendor',
            ], 200),
        ]);

        $execution = $this->runWhatsAppNode(['messageTemplate' => 'Hello {{doctor_name}}', 'recipient' => 'patient']);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('WhatsApp provider error', (string) $execution->fresh()->failure_reason);
        $this->assertStringNotContainsString('test-token', (string) $execution->fresh()->failure_reason);
    }

    public function test_invalid_token_is_handled(): void
    {
        Http::fake([
            'https://whatsjet.test/api/vendor-uid/contact/send-message' => Http::response([
                'message' => 'Unauthorized',
            ], 401),
        ]);

        $execution = $this->runWhatsAppNode(['messageTemplate' => 'Hello', 'recipient' => 'patient']);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('Invalid token', (string) $execution->fresh()->failure_reason);
    }

    public function test_missing_phone_fails_before_api_call(): void
    {
        Http::fake();

        $execution = $this->runWhatsAppNode(
            ['messageTemplate' => 'Hello', 'recipient' => 'patient'],
            ['patient_mobile' => '', 'patient' => ['first_name' => 'Ada']]
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('mobile number', (string) $execution->fresh()->failure_reason);
        Http::assertNothingSent();
    }

    public function test_attach_invoice_pdf_false_sends_text_only(): void
    {
        Http::fake([
            'https://whatsjet.test/api/vendor-uid/contact/send-message' => Http::response([
                'result' => 'success',
                'data' => ['log_uid' => 'log-text', 'wamid' => 'w-text', 'status' => 'sent'],
            ], 200),
        ]);

        $docs = Mockery::mock(InvoiceDocumentService::class)->makePartial();
        $docs->shouldReceive('renderPdfBinary')->never();
        $this->app->instance(InvoiceDocumentService::class, $docs);
        $this->bindRealWhatsApp();

        $this->runWhatsAppNode([
            'messageTemplate' => 'Invoice {{invoice_id}}',
            'recipient' => 'patient',
            'attachInvoicePdf' => false,
        ], ['invoice_id' => 12]);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'send-message'));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'send-media-message'));
    }

    public function test_attach_invoice_pdf_sends_document_media_with_public_https_url_and_resolved_caption(): void
    {
        $this->ensureInvoiceTable();
        $invoice = $this->createInvoice();
        $this->mockInvoicePdf('%PDF-FAKE-'.$invoice->id);

        Http::fake([
            'https://whatsjet.test/api/vendor-uid/contact/send-media-message' => Http::response([
                'result' => 'success',
                'data' => ['log_uid' => 'log-pdf', 'wamid' => 'w-pdf', 'status' => 'sent'],
            ], 200),
        ]);

        $execution = $this->runWhatsAppNode([
            'messageTemplate' => 'Invoice {{invoice_id}} for {{doctor_name}}',
            'recipient' => 'patient',
            'attachInvoicePdf' => true,
        ], ['invoice_id' => $invoice->id]);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);

        Http::assertSent(function (Request $request) use ($invoice) {
            $url = (string) $request['media_url'];

            return $request->url() === 'https://whatsjet.test/api/vendor-uid/contact/send-media-message'
                && $request['media_type'] === 'document'
                && $request['phone_number'] === '919999999999'
                && $url === 'https://files.example.com/storage/invoices/invoice-'.$invoice->id.'.pdf'
                && Storage::disk('public')->exists('invoices/invoice-'.$invoice->id.'.pdf')
                && ! str_contains($url, 'localhost')
                && ! str_contains($url, '127.0.0.1')
                && ! str_contains($url, storage_path())
                && str_contains((string) $request['caption'], (string) $invoice->id)
                && str_contains((string) $request['caption'], 'Dr Green')
                && ! str_contains((string) $request['caption'], '{{')
                && ! str_contains((string) $request['caption'], '%PDF');
        });
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'send-message'));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'send-template-message'));
    }

    public function test_attach_invoice_pdf_fails_when_invoice_file_cannot_be_generated(): void
    {
        $this->ensureInvoiceTable();
        Http::fake();

        $execution = $this->runWhatsAppNode([
            'messageTemplate' => 'Invoice ready',
            'recipient' => 'patient',
            'attachInvoicePdf' => true,
        ], ['invoice_id' => 999999]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('invoice not found', strtolower((string) $execution->fresh()->failure_reason));
        Http::assertNothingSent();
    }

    public function test_localhost_pdf_url_is_rejected_before_whatsjet(): void
    {
        $this->ensureInvoiceTable();
        $invoice = $this->createInvoice();
        $this->mockInvoicePdf('%PDF-FAKE-'.$invoice->id);

        config([
            'app.url' => 'https://127.0.0.1:8000',
            'filesystems.disks.public.url' => 'https://127.0.0.1:8000/storage',
            'services.whatsapp.media_public_url' => null,
        ]);

        Http::fake();

        $execution = $this->runWhatsAppNode([
            'messageTemplate' => 'Invoice {{invoice_id}}',
            'recipient' => 'patient',
            'attachInvoicePdf' => true,
        ], ['invoice_id' => $invoice->id]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('not publicly reachable', (string) $execution->fresh()->failure_reason);
        Http::assertNothingSent();
    }

    public function test_attach_prescription_pdf_sends_document_media_with_public_https_url(): void
    {
        $this->ensurePrescriptionTable();
        $prescription = $this->createPrescription();
        $this->mockPrescriptionPdf('%PDF-RX-'.$prescription->id);

        Http::fake([
            'https://whatsjet.test/api/vendor-uid/contact/send-media-message' => Http::response([
                'result' => 'success',
                'data' => ['log_uid' => 'log-rx', 'wamid' => 'w-rx', 'status' => 'sent'],
            ], 200),
        ]);

        $execution = $this->runWhatsAppNode([
            'messageTemplate' => 'Rx for {{patient_name}}',
            'recipient' => 'patient',
            'attachPrescriptionPdf' => true,
            'attachInvoicePdf' => false,
        ], ['prescription_id' => $prescription->id]);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);

        Http::assertSent(function (Request $request) use ($prescription) {
            $url = (string) $request['media_url'];

            return $request->url() === 'https://whatsjet.test/api/vendor-uid/contact/send-media-message'
                && $request['media_type'] === 'document'
                && $url === 'https://files.example.com/storage/prescriptions/prescription-'.$prescription->id.'.pdf'
                && Storage::disk('public')->exists('prescriptions/prescription-'.$prescription->id.'.pdf')
                && str_contains((string) $request['caption'], 'Ada')
                && ! str_contains((string) $request['caption'], '{{prescription_pdf}}');
        });
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'send-message'));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'send-template-message'));
    }

    public function test_template_message_uses_template_endpoint_and_maps_variables(): void
    {
        Http::fake([
            'https://whatsjet.test/api/vendor-uid/contact/send-template-message' => Http::response([
                'result' => 'success',
                'data' => ['log_uid' => 'log-tpl', 'wamid' => 'w-tpl', 'status' => 'sent'],
            ], 200),
        ]);

        $execution = $this->runWhatsAppNode([
            'recipient' => 'patient',
            'whatsappTemplateName' => 'appointment_confirmed',
            'templateLanguage' => 'en',
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => '{{doctor_name}}'],
                    ],
                ],
            ],
        ]);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);

        Http::assertSent(function (Request $request) {
            $components = $request['components'] ?? [];

            return $request->url() === 'https://whatsjet.test/api/vendor-uid/contact/send-template-message'
                && $request['template_name'] === 'appointment_confirmed'
                && $request['template_language'] === 'en'
                && $request['phone_number'] === '919999999999'
                && ($components[0]['parameters'][0]['text'] ?? null) === 'Dr Green';
        });
    }

    public function test_provider_template_list_is_fetched_by_backend_not_browser_token(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        Http::fake([
            'https://whatsjet.test/api/vendor-uid/contact/template-list' => Http::response([
                'result' => 'success',
                'data' => [
                    ['name' => 'hello', 'language' => 'en', 'status' => 'APPROVED', 'secret' => 'nope'],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/workflow/whatsapp/provider-templates');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'hello')
            ->assertJsonPath('data.0.language', 'en')
            ->assertJsonMissing(['token' => 'test-token'])
            ->assertJsonMissing(['secret' => 'nope']);

        Http::assertSent(function (Request $request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://whatsjet.test/api/vendor-uid/contact/template-list'
                && $request->hasHeader('Authorization', 'Bearer test-token');
        });
    }

    public function test_retry_does_not_duplicate_successful_whatsapp_send(): void
    {
        Http::fake([
            'https://whatsjet.test/api/vendor-uid/contact/send-message' => Http::response([
                'result' => 'success',
                'data' => ['log_uid' => 'log-once', 'wamid' => 'w-once', 'status' => 'sent'],
            ], 200),
        ]);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendWhatsApp', [
                'messageTemplate' => 'Hello {{doctor_name}}',
                'recipient' => 'patient',
            ]),
            'appointmentBooked',
        );

        $executor = app(WorkflowExecutor::class);
        $execution = $executor->start($version, 'appointmentBooked', $this->sampleAppointmentContext());
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);

        $manager = app(ChannelManager::class);
        $second = $manager->send(
            channel: 'whatsapp',
            execution: $execution->fresh(),
            nodeId: 'a1',
            message: 'Hello again',
            context: $this->sampleAppointmentContext(),
            recipient: 'patient',
        );

        $this->assertTrue($second['success']);
        $this->assertStringContainsString('duplicate', (string) $second['response']);
        Http::assertSentCount(1);
    }

    /**
     * @param  array<string, mixed>  $nodeData
     * @param  array<string, mixed>  $contextOverrides
     */
    protected function runWhatsAppNode(array $nodeData, array $contextOverrides = []): \App\Modules\Workflow\Models\WorkflowExecution
    {
        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendWhatsApp', $nodeData),
            'appointmentBooked',
        );

        return app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext($contextOverrides)
        );
    }

    protected function bindRealWhatsApp(): void
    {
        $this->app->forgetInstance(WhatsJetClient::class);
        $this->app->forgetInstance(WhatsAppNotificationService::class);
        $this->app->instance(WhatsAppNotificationService::class, $this->app->make(WhatsAppNotificationService::class));
        $this->app->forgetInstance(ChannelManager::class);
        $this->app->forgetInstance(NodeProcessorRegistry::class);
        $this->app->forgetInstance(WorkflowExecutor::class);

        $registry = $this->app->make(NodeProcessorRegistry::class);
        if (! $registry->has('aiPrompt')) {
            $registry->register($this->app->make(
                \App\Modules\Workflow\NodeProcessors\AiPromptNodeProcessor::class
            ));
        }
    }

    protected function mockInvoicePdf(string $binary): void
    {
        $docs = Mockery::mock(InvoiceDocumentService::class)->makePartial();
        $docs->shouldReceive('renderPdfBinary')->andReturn($binary);
        $docs->shouldReceive('downloadFilename')->andReturnUsing(
            fn (Invoice $invoice) => 'invoice-'.$invoice->id.'.pdf'
        );
        $this->app->instance(InvoiceDocumentService::class, $docs);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\InvoicePdfAttachmentService::class);
        $this->bindRealWhatsApp();
    }

    protected function mockPrescriptionPdf(string $binary): void
    {
        $docs = Mockery::mock(PrescriptionDocumentService::class)->makePartial();
        $docs->shouldReceive('renderPdfBinary')->andReturn($binary);
        $docs->shouldReceive('downloadFilename')->andReturnUsing(
            fn (Prescription $prescription) => 'prescription-'.$prescription->id.'.pdf'
        );
        $this->app->instance(PrescriptionDocumentService::class, $docs);
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\PrescriptionPdfAttachmentService::class);
        $this->bindRealWhatsApp();
    }

    protected function createPrescription(): Prescription
    {
        $prescription = new Prescription([
            'status' => 'sent',
            'medications' => [['name' => 'Amoxicillin', 'dosage' => '500mg']],
        ]);
        Prescription::withoutEvents(fn () => $prescription->save());

        return $prescription->fresh();
    }

    protected function ensurePrescriptionTable(): void
    {
        if (! Schema::hasTable('prescriptions')) {
            Schema::create('prescriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->string('patient_id')->nullable();
                $table->json('medications')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }
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
