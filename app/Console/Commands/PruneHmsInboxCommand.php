<?php

namespace App\Console\Commands;

use App\Models\HmsWebhookInbox;
use Illuminate\Console\Command;

class PruneHmsInboxCommand extends Command
{
    protected $signature = 'hms:inbox:prune {--days=90 : Delete inbox rows older than this many days}';

    protected $description = 'Delete HMS webhook inbox rows older than the retention window.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $this->error('days must be at least 1.');

            return self::FAILURE;
        }

        $deleted = HmsWebhookInbox::query()
            ->where('received_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Pruned {$deleted} inbox row(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
