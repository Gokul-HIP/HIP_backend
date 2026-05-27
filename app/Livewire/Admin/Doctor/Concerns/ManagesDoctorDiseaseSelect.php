<?php

namespace App\Livewire\Admin\Doctor\Concerns;

use App\Models\Disease;

trait ManagesDoctorDiseaseSelect
{
    public string $disease_search = '';

    public function getFilteredDiseaseDataProperty(): array
    {
        $query = Disease::query()
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name');

        $term = trim($this->disease_search);
        if ($term !== '') {
            $query->where('name', 'like', '%' . $term . '%');
        }

        return $query
            ->get()
            ->map(fn (Disease $disease) => ['id' => $disease->id, 'name' => $disease->name])
            ->all();
    }

    public function getSelectedDiseaseLabelsProperty(): array
    {
        $ids = collect($this->assigned_diseases ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Disease::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Disease $disease) => ['id' => $disease->id, 'name' => $disease->name])
            ->all();
    }

    public function removeDisease(int $diseaseId): void
    {
        $target = (string) $diseaseId;

        $this->assigned_diseases = array_values(array_filter(
            $this->assigned_diseases ?? [],
            fn ($id) => (string) $id !== $target
        ));
    }
}
