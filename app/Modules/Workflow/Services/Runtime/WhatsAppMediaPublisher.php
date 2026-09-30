<?php

namespace App\Modules\Workflow\Services\Runtime;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class WhatsAppMediaPublisher
{
    /**
     * @param  array{filename?: string, content?: string, mime?: string, invoice_id?: int|string|null}  $attachment
     * @param  array{workflow_execution_id?: int|string|null, invoice_id?: int|string|null}  $meta
     */
    public function publicPdfUrl(array $attachment, array $meta = []): string
    {
        $binary = (string) ($attachment['content'] ?? '');
        $filename = (string) ($attachment['filename'] ?? 'invoice.pdf');

        if ($binary === '') {
            throw new RuntimeException('Send WhatsApp: invoice PDF is empty; cannot publish a provider URL.');
        }

        $diskName = $this->configuredPublicDisk();
        $path = $this->storagePath($filename, $attachment['invoice_id'] ?? $meta['invoice_id'] ?? null);

        Storage::disk($diskName)->put($path, $binary, 'public');

        if (! Storage::disk($diskName)->exists($path)) {
            throw new RuntimeException(
                'Send WhatsApp: invoice PDF was not stored on disk '.$diskName.' at '.$path.'.'
            );
        }

        $this->assertPublicStorageLink($diskName);

        $url = $this->buildPublicUrl($diskName, $path);
        $this->assertProviderReachable($url);
        $access = $this->assertHttpAccessible($url);

        Log::info('WhatsApp invoice PDF published', [
            'workflow_execution_id' => $meta['workflow_execution_id'] ?? null,
            'invoice_id' => $attachment['invoice_id'] ?? $meta['invoice_id'] ?? null,
            'storage_disk' => $diskName,
            'stored_path' => $path,
            'url_scheme' => parse_url($url, PHP_URL_SCHEME),
            'url_host' => parse_url($url, PHP_URL_HOST),
            'url_path' => parse_url($url, PHP_URL_PATH),
            'http_status' => $access['http_status'],
            'content_type' => $access['content_type'],
        ]);

        return $url;
    }

    protected function configuredPublicDisk(): string
    {
        $diskName = trim((string) config('services.whatsapp.media_disk', 'public'));
        if ($diskName === '') {
            $diskName = 'public';
        }

        $root = (string) config('filesystems.disks.'.$diskName.'.root', '');
        $visibility = (string) config('filesystems.disks.'.$diskName.'.visibility', '');

        if ($diskName === 'local' || str_contains(str_replace('\\', '/', $root), '/private')) {
            throw new RuntimeException(
                'Send WhatsApp: WHATSAPP_PROVIDER_MEDIA_DISK must be a public disk (default: public), not FILESYSTEM_DISK/local.'
            );
        }

        if ($visibility !== '' && $visibility !== 'public') {
            throw new RuntimeException(
                'Send WhatsApp: disk '.$diskName.' is not public; WhatsJet cannot fetch private files.'
            );
        }

        if (! is_array(config('filesystems.disks.'.$diskName))) {
            throw new RuntimeException('Send WhatsApp: filesystem disk '.$diskName.' is not configured.');
        }

        return $diskName;
    }

    protected function storagePath(string $filename, mixed $invoiceId): string
    {
        $base = basename(str_replace('\\', '/', $filename));
        if ($base === '' || $base === '.' || $base === '..') {
            $base = 'invoice.pdf';
        }
        if (! str_ends_with(strtolower($base), '.pdf')) {
            $base .= '.pdf';
        }

        $id = is_numeric($invoiceId) ? (int) $invoiceId : 0;
        if ($id > 0) {
            return 'invoices/invoice-'.$id.'.pdf';
        }

        if (preg_match('/^invoice-\d+\.pdf$/i', $base) === 1) {
            return 'invoices/'.strtolower($base);
        }

        return 'invoices/'.$base;
    }

    protected function buildPublicUrl(string $diskName, string $path): string
    {
        $originOverride = rtrim((string) config('services.whatsapp.media_public_url'), '/');
        $diskUrl = rtrim((string) config('filesystems.disks.'.$diskName.'.url'), '/');

        if ($originOverride !== '') {
            $prefix = parse_url($diskUrl !== '' ? $diskUrl : $originOverride.'/storage', PHP_URL_PATH);
            $prefix = is_string($prefix) && $prefix !== '' ? $prefix : '/storage';
            $url = $originOverride.'/'.trim($prefix, '/').'/'.ltrim($path, '/');
        } elseif ($diskUrl !== '') {
            $url = $diskUrl.'/'.ltrim($path, '/');
        } else {
            $generated = Storage::disk($diskName)->url($path);
            $url = is_string($generated) ? $generated : '';
        }

        if ($url === '') {
            throw new RuntimeException('Send WhatsApp: could not build a public URL for the invoice PDF.');
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
        }

        if (app()->environment('production') && str_starts_with($url, 'http://')) {
            $url = 'https://'.substr($url, strlen('http://'));
        }

        return $url;
    }

    protected function assertPublicStorageLink(string $diskName): void
    {
        if (app()->environment('testing') || $diskName !== 'public') {
            return;
        }

        $link = public_path('storage');
        if (! file_exists($link)) {
            throw new RuntimeException(
                'Send WhatsApp: public/storage is missing. Run php artisan storage:link so /storage/invoices/*.pdf is publicly served.'
            );
        }
    }

    protected function assertProviderReachable(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if ($scheme !== 'https') {
            throw new RuntimeException(
                'Send WhatsApp: invoice PDF URL must be HTTPS so the WhatsApp provider can download it.'
            );
        }

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            throw new RuntimeException(
                'Send WhatsApp: invoice PDF URL is not publicly reachable. Set APP_URL or WHATSAPP_PROVIDER_MEDIA_PUBLIC_URL to the public HTTPS API origin.'
            );
        }

        if (filter_var($host, FILTER_VALIDATE_IP) && ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new RuntimeException(
                'Send WhatsApp: invoice PDF URL points at a private IP; WhatsJet cannot fetch it.'
            );
        }

        if (preg_match('#^[A-Za-z]:\\\\#', $url) === 1 || str_starts_with($url, 'file:') || str_contains($path, '/storage/app/')) {
            throw new RuntimeException(
                'Send WhatsApp: invoice PDF URL must be a public HTTPS URL, not a filesystem path.'
            );
        }
    }

    /**
     * @return array{http_status: int|null, content_type: string|null}
     */
    protected function assertHttpAccessible(string $url): array
    {
        if (! filter_var(config('services.whatsapp.verify_media_url', true), FILTER_VALIDATE_BOOLEAN)) {
            return ['http_status' => null, 'content_type' => null];
        }

        if (app()->environment('testing')) {
            return ['http_status' => null, 'content_type' => null];
        }

        $response = Http::timeout(15)
            ->withHeaders([
                'Accept' => 'application/pdf',
                'Range' => 'bytes=0-15',
            ])
            ->get($url);

        $status = $response->status();
        $contentType = strtolower((string) $response->header('Content-Type'));
        $contentType = trim(explode(';', $contentType)[0]);
        $snippet = ltrim(substr((string) $response->body(), 0, 16));

        if (in_array($status, [200, 206], true) === false) {
            throw new RuntimeException(
                'Send WhatsApp: invoice PDF URL returned HTTP '.$status
                .'. WhatsJet will fail to download it. Confirm php artisan storage:link and that APP_URL/WHATSAPP_PROVIDER_MEDIA_PUBLIC_URL match the public API host.'
            );
        }

        $looksPdf = str_contains($contentType, 'pdf') || str_starts_with($snippet, '%PDF');
        $looksHtml = str_contains($contentType, 'html') || str_starts_with($snippet, '<');

        if ($looksHtml || ! $looksPdf) {
            throw new RuntimeException(
                'Send WhatsApp: invoice PDF URL did not return application/pdf (got '.$contentType.').'
            );
        }

        return [
            'http_status' => $status,
            'content_type' => $contentType !== '' ? $contentType : 'application/pdf',
        ];
    }
}
