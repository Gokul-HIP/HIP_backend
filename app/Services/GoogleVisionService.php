<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;

class GoogleVisionService
{
    public function processPdfReport(UploadedFile $pdfFile): Report
    {
        $apiKey = (string) config('services.google_vision.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('Google Vision API key is not configured.');
        }

        $disk = Storage::disk('local');
        $tempDirectory = 'reports/tmp/' . Str::uuid();
        $storedPdfPath = $pdfFile->storeAs($tempDirectory, 'source.pdf', 'local');

        if ($storedPdfPath === false) {
            throw new RuntimeException('Failed to store the uploaded PDF.');
        }

        try {
            $imagePaths = $this->convertPdfToImages($disk->path($storedPdfPath), $disk->path($tempDirectory));
            $fullText = $this->extractTextFromImages($imagePaths, $apiKey);
            $billAmount = $this->extractBillAmount($fullText);
            $patientId = $this->extractPatientId($fullText);
            $patientName = $this->extractPatientName($fullText);
            $invoiceId = $this->extractInvoiceId($fullText);

            return DB::transaction(function () use ($pdfFile, $fullText, $billAmount, $patientId, $patientName, $invoiceId): Report {
                return Report::create([
                    'original_file_name' => $pdfFile->getClientOriginalName(),
                    'content' => $fullText,
                    'total_amount' => $billAmount,
                    'patient_id' => $patientId,
                    'patient_name' => $patientName,
                    'invoice_id' => $invoiceId,
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException('Unable to process the uploaded PDF bill.', previous: $exception);
        } finally {
            $disk->deleteDirectory($tempDirectory);
        }
    }

    /**
     * @return array<int, string>
     */
    private function convertPdfToImages(string $pdfAbsolutePath, string $outputDirectory): array
    {
        $binary = (string) config('services.google_vision.imagemagick_binary', 'magick');
        $density = (int) config('services.google_vision.render_density', 300);
        $outputPattern = $outputDirectory . DIRECTORY_SEPARATOR . 'page-%03d.png';
        $process = new Process([
            $binary,
            '-density',
            (string) $density,
            $pdfAbsolutePath,
            '-quality',
            '100',
            $outputPattern,
        ]);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            $message = trim($process->getErrorOutput() ?: $process->getOutput());
            if ($message === '') {
                $message = $exception->getMessage();
            }

            throw new RuntimeException('ImageMagick conversion failed: ' . $message, previous: $exception);
        }

        $imagePaths = glob($outputDirectory . DIRECTORY_SEPARATOR . 'page-*.png') ?: [];
        sort($imagePaths);

        if ($imagePaths === []) {
            throw new RuntimeException('No image was generated from the uploaded PDF.');
        }

        return $imagePaths;
    }

    /**
     * @param array<int, string> $imagePaths
     */
    private function extractTextFromImages(array $imagePaths, string $apiKey): string
    {
        $endpoint = (string) config('services.google_vision.endpoint');
        $caBundle = (string) config('services.google_vision.ca_bundle', 'C:\xampp\php\extras\ssl\cacert.pem');
        $texts = [];

        foreach ($imagePaths as $imagePath) {
            $imageContents = file_get_contents($imagePath);

            if ($imageContents === false) {
                throw new RuntimeException('Failed to read the generated image for OCR.');
            }

            $httpClient = Http::acceptJson()
                ->asJson()
                ->timeout(60);

            if ($caBundle !== '') {
                $httpClient = $httpClient->withOptions([
                    'verify' => $caBundle,
                ]);
            }

            $response = $httpClient->post($endpoint . '?key=' . urlencode($apiKey), [
                    'requests' => [
                        [
                            'image' => [
                                'content' => base64_encode($imageContents),
                            ],
                            'features' => [
                                [
                                    'type' => 'DOCUMENT_TEXT_DETECTION',
                                ],
                            ],
                        ],
                    ],
                ]);

            try {
                $response->throw();
            } catch (RequestException $exception) {
                $responseBody = $exception->response?->body();
                $responseStatus = $exception->response?->status();

                $message = 'Google Vision API request failed.';

                if ($responseStatus !== null) {
                    $message .= ' HTTP ' . $responseStatus . '.';
                }

                if (is_string($responseBody) && trim($responseBody) !== '') {
                    $message .= ' Response: ' . trim($responseBody);
                } else {
                    $message .= ' ' . $exception->getMessage();
                }

                throw new RuntimeException($message, previous: $exception);
            }

            $payload = $response->json();
            $visionResponse = data_get($payload, 'responses.0', []);
            $errorMessage = data_get($visionResponse, 'error.message');

            if ($errorMessage) {
                throw new RuntimeException('Google Vision API error: ' . $errorMessage);
            }

            $pageText = trim((string) data_get($visionResponse, 'fullTextAnnotation.text', ''));

            if ($pageText !== '') {
                $texts[] = $pageText;
            }
        }

        $fullText = trim(implode(PHP_EOL . PHP_EOL, $texts));

        if ($fullText === '') {
            throw new RuntimeException('No text was detected in the uploaded hospital bill.');
        }

        return $fullText;
    }

    private function extractBillAmount(string $text): ?string
    {
        $patterns = [
            '/bill\s*amount\s*[:\-]?\s*(?:rs\.?|inr|usd|\$)?\s*([\d,]+(?:\.\d{1,2})?)/i',
            '/total\s*amount\s*[:\-]?\s*(?:rs\.?|inr|usd|\$)?\s*([\d,]+(?:\.\d{1,2})?)/i',
            '/amount\s*due\s*[:\-]?\s*(?:rs\.?|inr|usd|\$)?\s*([\d,]+(?:\.\d{1,2})?)/i',
            '/net\s*amount\s*[:\-]?\s*(?:rs\.?|inr|usd|\$)?\s*([\d,]+(?:\.\d{1,2})?)/i',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $text, $matches)) {
                continue;
            }

            $normalized = str_replace(',', '', $matches[1]);

            if (! is_numeric($normalized)) {
                continue;
            }

            return number_format((float) $normalized, 2, '.', '');
        }

        return null;
    }

    private function extractPatientId(string $text): ?string
    {
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        if (preg_match('/ID\s*No\.?\s*:\s*(\d+)/i', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractPatientName(string $text): ?string
    {
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $lines = preg_split('/\r\n|\r|\n/', $text);

        foreach ($lines as $index => $line) {
            if (stripos($line, 'PATIENT DETAILS') !== false) {
                return trim($lines[$index + 1] ?? '');
            }
        }

        return null;
    }

    private function extractInvoiceId(string $text): ?string
    {
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        if (preg_match('/Bill\s*No\.?\s*:\s*([A-Z0-9]+)/i', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
