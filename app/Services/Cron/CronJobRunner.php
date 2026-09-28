<?php

namespace App\Services\Cron;

use App\Models\CronJob;
use App\Models\CronJobRun;
use App\Services\AdminActionLogger;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class CronJobRunner
{
    public function __construct(
        protected CronCommandRegistry $registry,
        protected CronSchedule $schedule,
        protected AdminActionLogger $audit,
    ) {}

    public function runDueJobs(): int
    {
        $executed = 0;

        CronJob::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->each(function (CronJob $job) use (&$executed): void {
                if (! $this->schedule->isDue($job->schedule, now(), $job->timezone ?: config('cron.timezone'))) {
                    return;
                }

                $run = $this->execute($job, 'scheduler');

                if ($run->status !== CronJobRun::STATUS_SKIPPED) {
                    $executed++;
                }
            });

        return $executed;
    }

    public function runNow(CronJob $job, string $triggeredBy = 'manual'): CronJobRun
    {
        $run = $this->execute($job, $triggeredBy);

        if ($triggeredBy === 'manual') {
            $this->audit->record('cron.manually_run', [
                'cron_job_id' => $job->id,
                'run_id' => $run->id,
                'status' => $run->status,
            ]);
        }

        return $run;
    }

    public function execute(CronJob $job, string $triggeredBy): CronJobRun
    {
        $this->registry->assertApproved((string) $job->command);

        $lock = Cache::lock($this->lockKey($job), (int) config('cron.lock_seconds', 900));

        if (! $lock->get()) {
            return $this->recordSkipped($job, $triggeredBy, 'Skipped because a previous execution is still running.');
        }

        try {
            if ($job->runs()->where('status', CronJobRun::STATUS_RUNNING)->exists()) {
                return $this->recordSkipped($job, $triggeredBy, 'Skipped because a previous execution is still running.');
            }

            $startedAt = now();
            $run = CronJobRun::query()->create([
                'cron_job_id' => $job->id,
                'started_at' => $startedAt,
                'status' => CronJobRun::STATUS_RUNNING,
                'triggered_by' => $triggeredBy,
            ]);

            $started = hrtime(true);
            $output = null;
            $error = null;
            $status = CronJobRun::STATUS_SUCCESS;

            try {
                $buffer = new BufferedOutput();
                $exitCode = Artisan::call($job->command, [], $buffer);
                $output = $this->truncate($buffer->fetch());

                if ($exitCode !== 0) {
                    $status = CronJobRun::STATUS_FAILED;
                    $error = 'Artisan command exited with code '.$exitCode;
                }
            } catch (Throwable $e) {
                $status = CronJobRun::STATUS_FAILED;
                $error = $this->truncate($e->getMessage());
                $output = $output ?? null;
            }

            $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
            $finishedAt = now();

            $run->update([
                'finished_at' => $finishedAt,
                'status' => $status,
                'duration_ms' => $durationMs,
                'output' => $output,
                'error' => $error,
            ]);

            $job->forceFill([
                'last_run_at' => $startedAt,
                'last_status' => $status,
                'last_duration_ms' => $durationMs,
                'last_error' => $error,
                'next_run_at' => $this->schedule->nextRunAt(
                    $job->schedule,
                    $finishedAt,
                    $job->timezone ?: config('cron.timezone')
                ),
            ])->saveQuietly();

            return $run->refresh();
        } finally {
            optional($lock)->release();
        }
    }

    protected function recordSkipped(CronJob $job, string $triggeredBy, string $reason): CronJobRun
    {
        return CronJobRun::query()->create([
            'cron_job_id' => $job->id,
            'started_at' => now(),
            'finished_at' => now(),
            'status' => CronJobRun::STATUS_SKIPPED,
            'duration_ms' => 0,
            'error' => $reason,
            'triggered_by' => $triggeredBy,
        ]);
    }

    protected function lockKey(CronJob $job): string
    {
        return 'cron-job-run:'.$job->id;
    }

    protected function truncate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $max = (int) config('cron.max_output_chars', 20000);

        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max).'…';
    }
}
