<?php

namespace Tests\Unit;

use App\Modules\MedicineReminder\Notifications\EmailNotificationService;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmailNotificationServiceAttachmentTest extends TestCase
{
    public function test_raw_email_attaches_pdf_and_keeps_body(): void
    {
        $captured = null;
        Event::listen(MessageSent::class, function (MessageSent $event) use (&$captured): void {
            $captured = $event;
        });

        $body = "Payment received successfully at Nano.\n\nInvoice: #243\nAmount: ₹535.30";

        $result = app(EmailNotificationService::class)->send(
            'ada@example.com',
            'Invoice',
            $body,
            [],
            [[
                'filename' => 'invoice-243.pdf',
                'content' => '%PDF-243',
                'mime' => 'application/pdf',
            ]]
        );

        $this->assertTrue($result['success']);
        $this->assertNotNull($captured);

        $message = $captured->message;
        $this->assertSame('Invoice', $message->getSubject());
        $this->assertStringContainsString($body, (string) $message->getTextBody());
        $this->assertStringNotContainsString('%PDF-243', (string) $message->getTextBody());

        $attachments = $message->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertSame('invoice-243.pdf', $attachments[0]->getFilename());
        $this->assertSame('%PDF-243', $attachments[0]->getBody());
    }
}
