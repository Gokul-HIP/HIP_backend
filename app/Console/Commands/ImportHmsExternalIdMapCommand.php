<?php

namespace App\Console\Commands;

use App\Models\DiagnosticPackage;
use App\Models\Doctor;
use App\Models\FamilyPackage;
use App\Models\HmsExternalIdMap;
use App\Models\HmsIntegration;
use App\Models\Hospital;
use Illuminate\Console\Command;

class ImportHmsExternalIdMapCommand extends Command
{
    protected $signature = 'hms:map:import
        {integration_id : Integration id}
        {file : CSV with entity_type, external_id, local_id}';

    protected $description = 'Upsert HMS external id mappings from a CSV file.';

    public function handle(): int
    {
        $integration = HmsIntegration::query()->find($this->argument('integration_id'));
        if (! $integration) {
            $this->error('Integration was not found.');

            return self::FAILURE;
        }

        $path = (string) $this->argument('file');
        if (! is_file($path) || ! is_readable($path)) {
            $this->error('CSV file was not found.');

            return self::FAILURE;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $this->error('CSV file was not found.');

            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            $this->error('CSV header must include entity_type, external_id, and local_id.');

            return self::FAILURE;
        }

        $columns = [];
        foreach ($header as $index => $name) {
            $name = strtolower(trim((string) $name));
            $name = preg_replace('/^\xEF\xBB\xBF/', '', $name) ?? $name;
            if ($name !== '') {
                $columns[$name] = $index;
            }
        }

        foreach (['entity_type', 'external_id', 'local_id'] as $required) {
            if (! array_key_exists($required, $columns)) {
                fclose($handle);
                $this->error('CSV header must include entity_type, external_id, and local_id.');

                return self::FAILURE;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if ($this->blankRow($row)) {
                continue;
            }

            $entityType = strtolower(trim((string) ($row[$columns['entity_type']] ?? '')));
            $externalId = trim((string) ($row[$columns['external_id']] ?? ''));
            $localId = trim((string) ($row[$columns['local_id']] ?? ''));

            if ($entityType === '' || $externalId === '' || $localId === '') {
                $skipped[] = "row {$rowNumber}: entity_type, external_id, and local_id are required";
                continue;
            }

            $exists = $this->localIdExists($entityType, $localId);
            if ($exists === null) {
                $skipped[] = "row {$rowNumber}: entity_type \"{$entityType}\" is not allowed";
                continue;
            }
            if ($exists === false) {
                $skipped[] = "row {$rowNumber}: {$entityType} local_id {$localId} was not found";
                continue;
            }

            $map = HmsExternalIdMap::query()->updateOrCreate(
                [
                    'integration_id' => $integration->getKey(),
                    'entity_type' => $entityType,
                    'external_id' => $externalId,
                ],
                ['local_id' => $localId]
            );

            if ($map->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        fclose($handle);

        $this->info('Created: '.$created);
        $this->info('Updated: '.$updated);
        $this->info('Skipped: '.count($skipped));
        foreach ($skipped as $line) {
            $this->warn($line);
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function blankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function localIdExists(string $entityType, string $localId): ?bool
    {
        return match ($entityType) {
            HmsExternalIdMap::TYPE_HOSPITAL => Hospital::query()->whereKey($localId)->exists(),
            HmsExternalIdMap::TYPE_DOCTOR => Doctor::query()->whereKey($localId)->exists(),
            HmsExternalIdMap::TYPE_PACKAGE => DiagnosticPackage::query()->whereKey($localId)->exists(),
            HmsExternalIdMap::TYPE_PLAN => FamilyPackage::query()->whereKey($localId)->exists(),
            HmsExternalIdMap::TYPE_PATIENT => true,
            default => null,
        };
    }
}
