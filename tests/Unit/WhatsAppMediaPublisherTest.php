<?php

namespace Tests\Unit;

use App\Modules\Workflow\Services\Runtime\WhatsAppMediaPublisher;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class WhatsAppMediaPublisherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://api.healthinpocket.in',
            'filesystems.disks.public.url' => 'https://api.healthinpocket.in/storage',
            'services.whatsapp.media_disk' => 'public',
            'services.whatsapp.media_public_url' => null,
        ]);

        Storage::fake('public');
    }

    public function test_pdf_is_stored_on_configured_public_disk_and_url_matches_path(): void
    {
        $url = app(WhatsAppMediaPublisher::class)->publicPdfUrl([
            'filename' => 'invoice-270.pdf',
            'content' => '%PDF-1.4 test',
            'invoice_id' => 270,
        ], ['workflow_execution_id' => 88]);

        $this->assertTrue(Storage::disk('public')->exists('invoices/invoice-270.pdf'));
        $this->assertSame('https://api.healthinpocket.in/storage/invoices/invoice-270.pdf', $url);
        $this->assertStringStartsWith('https://', $url);
        $this->assertStringNotContainsString('localhost', $url);
        $this->assertStringNotContainsString('127.0.0.1', $url);
        $this->assertStringNotContainsString(storage_path('app'), $url);
    }

    public function test_media_public_url_override_keeps_storage_path_convention(): void
    {
        config(['services.whatsapp.media_public_url' => 'https://api.healthinpocket.in']);

        $url = app(WhatsAppMediaPublisher::class)->publicPdfUrl([
            'filename' => 'invoice-12.pdf',
            'content' => '%PDF-1.4 test',
            'invoice_id' => 12,
        ]);

        $this->assertSame('https://api.healthinpocket.in/storage/invoices/invoice-12.pdf', $url);
    }

    public function test_production_http_app_url_is_upgraded_to_https(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.url' => 'http://api.healthinpocket.in',
            'filesystems.disks.public.url' => 'http://api.healthinpocket.in/storage',
        ]);

        $url = app(WhatsAppMediaPublisher::class)->publicPdfUrl([
            'filename' => 'invoice-3.pdf',
            'content' => '%PDF-1.4 test',
            'invoice_id' => 3,
        ]);

        $this->assertSame('https://api.healthinpocket.in/storage/invoices/invoice-3.pdf', $url);
    }

    public function test_empty_pdf_fails_clearly(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invoice PDF is empty');

        app(WhatsAppMediaPublisher::class)->publicPdfUrl([
            'filename' => 'invoice-1.pdf',
            'content' => '',
        ]);
    }

    public function test_local_private_disk_is_rejected(): void
    {
        config(['services.whatsapp.media_disk' => 'local']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('public disk');

        app(WhatsAppMediaPublisher::class)->publicPdfUrl([
            'filename' => 'invoice-1.pdf',
            'content' => '%PDF-1.4 test',
        ]);
    }

    public function test_localhost_url_is_rejected(): void
    {
        config([
            'app.url' => 'http://localhost',
            'filesystems.disks.public.url' => 'http://localhost/storage',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTPS');

        app(WhatsAppMediaPublisher::class)->publicPdfUrl([
            'filename' => 'invoice-1.pdf',
            'content' => '%PDF-1.4 test',
            'invoice_id' => 1,
        ]);
    }
}
