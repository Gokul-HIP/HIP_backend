<?php

namespace App\Filament\Resources\SpecialitiesMasters\Concerns;

trait NormalizesSpecialityDiseasePayload
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeSpecialityFormData(array $data): array
    {
        if (! isset($data['diseases']) || ! is_array($data['diseases'])) {
            $data['diseases'] = null;

            return $data;
        }

        $normalized = collect($data['diseases'])
            ->filter(fn ($entry) => is_array($entry) && filled($entry['name'] ?? null))
            ->map(function (array $entry) {
                return [
                    'name'              => trim((string) $entry['name']),
                    'about'             => filled($entry['about'] ?? null) ? trim((string) $entry['about']) : null,
                    'symptoms'          => collect($entry['symptoms'] ?? [])
                        ->map(fn ($item) => trim((string) $item))
                        ->filter()
                        ->values()
                        ->all(),
                    'recommended_tests' => collect($entry['recommended_tests'] ?? [])
                        ->map(fn ($item) => trim((string) $item))
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $data['diseases'] = $normalized === [] ? null : $normalized;

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->normalizeSpecialityFormData($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->normalizeSpecialityFormData($data);
    }
}
