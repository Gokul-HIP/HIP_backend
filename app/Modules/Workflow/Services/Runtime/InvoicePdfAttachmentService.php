<?php

namespace App\Modules\Workflow\Services\Runtime;

use App\Models\Invoice;
use App\Services\InvoiceDocumentService;
use RuntimeException;

class InvoicePdfAttachmentService
{
    public function __construct(
        protected InvoiceDocumentService $invoiceDocumentService,
    ) {}

    /**
     * @param  array<string, mixed>  $nodeData
     */
    public function isEnabled(array $nodeData): bool
    {
        $raw = $nodeData['attachInvoicePdf']
            ?? $nodeData['attach_invoice_pdf']
            ?? false;

        if (is_bool($raw)) {
            return $raw;
        }

        if (is_int($raw) || is_float($raw)) {
            return (int) $raw === 1;
        }

        if (! is_string($raw)) {
            return false;
        }

        return in_array(strtolower(trim($raw)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array{filename: string, content: string, mime: string}>
     */
    public function attachments(array $context): array
    {
        $invoiceId = $this->invoiceIdFromContext($context);

        if ($invoiceId === null) {
            throw new RuntimeException('Send Email: invoice_id is required to attach the invoice PDF.');
        }

        $invoice = Invoice::query()->find($invoiceId);

        if (! $invoice) {
            throw new RuntimeException('Send Email: invoice not found for invoice_id '.$invoiceId.'.');
        }

        try {
            $binary = $this->invoiceDocumentService->renderPdfBinary($invoice);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Send Email: failed to generate invoice PDF. '.$e->getMessage(),
                0,
                $e
            );
        }

        if ($binary === '') {
            throw new RuntimeException('Send Email: failed to generate invoice PDF. Renderer returned empty output.');
        }

        return [[
            'filename' => $this->invoiceDocumentService->downloadFilename($invoice),
            'content' => $binary,
            'mime' => 'application/pdf',
        ]];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function invoiceIdFromContext(array $context): ?int
    {
        $raw = $context['invoice_id']
            ?? data_get($context, 'invoice.id')
            ?? data_get($context, 'meta.invoice_id');

        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }
}
