<?php

namespace App\Http\Controllers\PharmacistAdmin;

use App\Http\Controllers\Controller;
use App\Models\HIPUser;
use App\Models\PharmacyOrder;
use App\Policies\PharmacyOrderPolicy;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrescriptionOrderFileController extends Controller
{
    public function show(PharmacyOrder $order): StreamedResponse
    {
        $user = auth()->user();

        if (! $user instanceof HIPUser || ! app(PharmacyOrderPolicy::class)->pharmacistCanManage($user, $order)) {
            abort(404);
        }

        $document = $order->prescriptionDocument;
        $path = $document?->document_path;

        if (! is_string($path) || $path === '' || str_contains($path, '..') || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $mime = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';
        $filename = $this->downloadName($document->document_name ?? null, $path);
        $disposition = request()->boolean('download') ? 'attachment' : 'inline';

        return Storage::disk('local')->response($path, $filename, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function downloadName(?string $original, string $path): string
    {
        $name = trim((string) $original);
        $name = str_replace(["\r", "\n", '"', '\\', '/'], '', $name);
        $stored = basename($path);

        if ($name === '' || $name === $path || $name === $stored) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $name = 'prescription'.($extension !== '' ? '.'.$extension : '');
        }

        return $name;
    }
}
