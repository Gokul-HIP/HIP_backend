<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Services\GoogleVisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class PdfUploadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => [
                'database/migrations/2026_04_09_000000_create_reports_table.php',
            ],
        ];
    }

    public function test_validation_rejects_non_pdf_upload(): void
    {
        $file = UploadedFile::fake()->image('bill.jpg');

        $response = $this->postJson('/api/reports/ocr', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_successful_upload_mocks_google_vision_service(): void
    {
        $file = UploadedFile::fake()->create('hospital-bill.pdf', 200, 'application/pdf');
        $report = Report::create([
            'original_file_name' => 'hospital-bill.pdf',
            'content' => 'Sample OCR text',
            'total_amount' => 1234.50,
        ]);

        $mock = Mockery::mock(GoogleVisionService::class);
        $mock->shouldReceive('processPdfReport')
            ->once()
            ->andReturn($report);

        $this->app->instance(GoogleVisionService::class, $mock);

        $response = $this->postJson('/api/reports/ocr', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'PDF bill processed successfully.',
                'data' => [
                    'id' => $report->id,
                    'original_file_name' => 'hospital-bill.pdf',
                    'content' => 'Sample OCR text',
                    'total_amount' => '1234.50',
                ],
            ]);
    }
}

