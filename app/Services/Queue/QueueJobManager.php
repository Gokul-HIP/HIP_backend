<?php

namespace App\Services\Queue;

use App\Models\DatabaseQueueJob;
use App\Models\FailedQueueJob;
use App\Services\AdminActionLogger;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class QueueJobManager
{
    public function __construct(
        protected AdminActionLogger $audit,
        protected QueuePayloadSanitizer $sanitizer,
    ) {}

    public function retryFailed(FailedQueueJob $job): void
    {
        $id = $job->uuid ?: (string) $job->id;
        $exit = Artisan::call('queue:retry', ['id' => [$id]]);

        if ($exit !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Failed job retry did not succeed.');
        }

        $this->audit->record('queue.job_retried', [
            'failed_job_id' => $job->id,
            'uuid' => $job->uuid,
        ]);
    }

    public function deleteFailed(FailedQueueJob $job): void
    {
        $id = $job->uuid ?: (string) $job->id;
        Artisan::call('queue:forget', ['id' => $id]);

        $this->audit->record('queue.failed_job_deleted', [
            'failed_job_id' => $job->id,
            'uuid' => $job->uuid,
        ]);
    }

    public function deletePending(DatabaseQueueJob $job): void
    {
        $id = $job->id;
        $job->delete();

        $this->audit->record('queue.pending_job_deleted', [
            'job_id' => $id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function safePayload(DatabaseQueueJob|FailedQueueJob $job): array
    {
        return $this->sanitizer->summarize($job->payload ?? null);
    }

    public function safeException(FailedQueueJob $job): ?string
    {
        return $this->sanitizer->exceptionPreview($job->exception ?? null);
    }
}
