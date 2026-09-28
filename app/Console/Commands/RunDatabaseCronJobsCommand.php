<?php

namespace App\Console\Commands;

use App\Services\Cron\CronJobRunner;
use Illuminate\Console\Command;

class RunDatabaseCronJobsCommand extends Command
{
    protected $signature = 'cron:run-database-jobs';

    protected $description = 'Run due active application cron jobs defined in the database.';

    public function handle(CronJobRunner $runner): int
    {
        $executed = $runner->runDueJobs();

        $this->info("Executed {$executed} database cron job(s).");

        return self::SUCCESS;
    }
}
