<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BackfillPersonMrn extends Command
{
    protected $signature = 'persons:backfill-mrn {--dry-run} {--chunk=200}';

    protected $description = 'Assign MRN00001-style ids to persons that have no MRN, without touching other columns.';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');

        if ($chunkSize < 1) {
            $this->error('The chunk size must be at least 1.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $ids = $this->missingMrnIds();
        $count = $ids->count();
        $start = $this->nextMrnNumber();

        if ($count === 0) {
            $this->info('No persons need an MRN. Nothing changed.');

            return self::SUCCESS;
        }

        $first = $this->formatMrn($start);
        $last = $this->formatMrn($start + $count - 1);

        if ($dryRun) {
            $this->info("Dry run: {$count} persons would receive an MRN.");
            $this->line("First MRN: {$first}");
            $this->line("Last MRN: {$last}");
            $this->line('Examples:');

            foreach ($ids->take(5)->values() as $offset => $id) {
                $this->line('  '.$id.' '.$this->formatMrn($start + $offset));
            }

            $this->info("Summary: {$count} rows would change. No rows written.");

            return self::SUCCESS;
        }

        $assigned = 0;
        $next = $start;

        foreach ($ids->chunk($chunkSize) as $chunk) {
            DB::transaction(function () use ($chunk, &$next, &$assigned): void {
                foreach ($chunk as $id) {
                    $this->assignMrn((string) $id, $next);
                    $assigned++;
                }
            });
        }

        $this->info("Updated {$assigned} persons.");
        $this->line('First MRN: '.$this->formatMrn($start));
        $this->line('Last MRN: '.$this->formatMrn($next - 1));
        $this->info("Summary: {$assigned} rows updated.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, string>
     */
    private function missingMrnIds(): Collection
    {
        return DB::table('persons')
            ->where(function ($query): void {
                $query->whereNull('mrn')
                    ->orWhereRaw("TRIM(mrn) = ''");
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values();
    }

    private function nextMrnNumber(): int
    {
        $max = DB::table('persons')
            ->whereRaw("mrn REGEXP ?", ['^MRN[0-9]+$'])
            ->selectRaw('MAX(CAST(SUBSTRING(mrn, 4) AS UNSIGNED)) as maxnum')
            ->value('maxnum');

        return ((int) $max) + 1;
    }

    private function formatMrn(int $number): string
    {
        return 'MRN'.str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }

    private function assignMrn(string $personId, int &$next): void
    {
        while (true) {
            $mrn = $this->formatMrn($next);

            if (DB::table('persons')->where('mrn', $mrn)->exists()) {
                $next++;

                continue;
            }

            try {
                DB::table('persons')->where('id', $personId)->update(['mrn' => $mrn]);
                $next++;

                return;
            } catch (QueryException $exception) {
                if (! $this->isUniqueViolation($exception)) {
                    throw $exception;
                }

                $next++;
            }
        }
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $sqlState = (string) $exception->getCode();
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return $sqlState === '23000' || $driverCode === 1062 || $driverCode === 19;
    }
}
