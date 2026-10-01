<?php

namespace Tests\Feature\Automation;

use App\Models\Prescription;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use App\Services\PrescriptionDocumentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\Support\WorkflowAutomationTestCase;

class PrescriptionEmailPdfAttachmentTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePrescriptionTable();
    }

    public function test_send_email_attaches_prescription_pdf_without_changing_body(): void
    {
        $prescription = $this->createPrescription();
        $this->mockPrescriptionPdf('%PDF-RX-'.$prescription->id);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendEmail', [
                'subject' => 'Your prescription',
                'body' => 'Hello {{patient_name}} from {{hospital_name}}',
                'recipient' => 'patient',
                'attachPrescriptionPdf' => true,
                'attachInvoicePdf' => false,
            ]),
            'appointmentBooked',
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext([
                'prescription_id' => $prescription->id,
            ])
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertCount(1, $this->providerSends);
        $this->assertSame('email', $this->providerSends[0]['channel']);
        $this->assertSame('Your prescription', $this->providerSends[0]['subject']);
        $this->assertSame('Hello Ada Lovelace from Sunrise Hospital', $this->providerSends[0]['message']);
        $this->assertStringNotContainsString('prescription_pdf', $this->providerSends[0]['message']);
        $this->assertCount(1, $this->providerSends[0]['attachments'] ?? []);
        $this->assertSame('prescription-'.$prescription->id.'.pdf', $this->providerSends[0]['attachments'][0]['filename']);
        $this->assertSame('%PDF-RX-'.$prescription->id, $this->providerSends[0]['attachments'][0]['content']);
    }

    public function test_send_email_without_prescription_flag_does_not_attach_pdf(): void
    {
        $prescription = $this->createPrescription();
        $docs = Mockery::mock(PrescriptionDocumentService::class)->makePartial();
        $docs->shouldReceive('renderPdfBinary')->never();
        $this->app->instance(PrescriptionDocumentService::class, $docs);
        $this->rebindRuntime();

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendEmail', [
                'subject' => 'Your prescription',
                'body' => 'Hello',
                'recipient' => 'patient',
                'attachPrescriptionPdf' => false,
            ]),
            'appointmentBooked',
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext([
                'prescription_id' => $prescription->id,
            ])
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame([], $this->providerSends[0]['attachments'] ?? []);
    }

    public function test_attach_prescription_pdf_fails_the_node_when_generation_throws(): void
    {
        $prescription = $this->createPrescription();
        $this->mockPrescriptionPdf(new \RuntimeException('dompdf boom'));

        $version = $this->publishDefinition(
            $this->linearGraph('prescriptionAdded', 'sendEmail', [
                'subject' => 'Your prescription',
                'body' => 'Hello',
                'recipient' => 'patient',
                'attachPrescriptionPdf' => true,
            ]),
            'prescriptionAdded',
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'prescriptionAdded',
            $this->sampleAppointmentContext([
                'prescription_id' => $prescription->id,
            ])
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('failed to generate prescription PDF', (string) $execution->fresh()->failure_reason);
        $this->assertSame('sent', $prescription->fresh()->status);
        $this->assertCount(0, $this->providerSends);
    }

    public function test_invoice_and_prescription_pdf_flags_are_independent(): void
    {
        $prescription = $this->createPrescription();
        $this->mockPrescriptionPdf('%PDF-RX-'.$prescription->id);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendEmail', [
                'subject' => 'Docs',
                'body' => 'Hello',
                'recipient' => 'patient',
                'attachPrescriptionPdf' => false,
                'attachInvoicePdf' => false,
            ]),
            'appointmentBooked',
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext([
                'prescription_id' => $prescription->id,
                'invoice_id' => 99,
            ])
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame([], $this->providerSends[0]['attachments'] ?? []);
    }

    public function test_attach_prescription_pdf_fails_when_prescription_id_missing(): void
    {
        $this->mockPrescriptionPdf('%PDF-should-not-send%');

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendEmail', [
                'subject' => 'Your prescription',
                'body' => 'Hello',
                'recipient' => 'patient',
                'attachPrescriptionPdf' => true,
            ]),
            'appointmentBooked',
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('prescription_id is required', (string) $execution->fresh()->failure_reason);
        $this->assertCount(0, $this->providerSends);
    }

    protected function mockPrescriptionPdf(string|\Throwable $result): void
    {
        $docs = Mockery::mock(PrescriptionDocumentService::class)->makePartial();
        if ($result instanceof \Throwable) {
            $docs->shouldReceive('renderPdfBinary')->andThrow($result);
        } else {
            $docs->shouldReceive('renderPdfBinary')->andReturn($result);
        }
        $docs->shouldReceive('downloadFilename')->andReturnUsing(
            fn (Prescription $prescription) => 'prescription-'.$prescription->id.'.pdf'
        );
        $this->app->instance(PrescriptionDocumentService::class, $docs);
        $this->rebindRuntime();
    }

    protected function rebindRuntime(): void
    {
        $this->app->forgetInstance(\App\Modules\Workflow\Services\Runtime\PrescriptionPdfAttachmentService::class);
        $this->bindNotificationMocks(success: true);
    }

    protected function createPrescription(): Prescription
    {
        $prescription = new Prescription([
            'status' => 'sent',
            'medications' => [
                ['name' => 'Amoxicillin', 'dosage' => '500mg', 'frequency' => 'BID'],
            ],
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
                $table->string('member_id')->nullable();
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->unsignedBigInteger('doctor_booking_id')->nullable();
                $table->json('medications')->nullable();
                $table->json('lab_tests')->nullable();
                $table->json('document_ids')->nullable();
                $table->text('clinical_notes')->nullable();
                $table->string('diagnosis')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }
    }
}
