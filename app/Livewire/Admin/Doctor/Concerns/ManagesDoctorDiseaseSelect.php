<?php

namespace App\Livewire\Admin\Doctor\Concerns;

use App\Models\Disease;
use App\Models\DiseaseDepartment;

trait ManagesDoctorDiseaseSelect
{
    public string $disease_search = '';

    /**
     * Returns departments with their disease names expanded.
     * Each item: ['id' => int, 'department_name' => string, 'disease_names' => string[]]
     */
    public function getFilteredDiseaseDataProperty(): array
    {
        $query = DiseaseDepartment::query()
            ->where('is_active', true)
            ->select('id', 'department_name', 'diseases')
            ->orderBy('department_name');

        $term = trim($this->disease_search);
        if ($term !== '') {
            $query->where('department_name', 'like', '%' . $term . '%');
        }

        $departments = $query->get();

        // Collect all disease IDs across all departments for a single query
        $allDiseaseIds = $departments
            ->flatMap(fn (DiseaseDepartment $d) => is_array($d->diseases) ? $d->diseases : (json_decode($d->diseases ?? '[]', true) ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        // Fetch all disease names in one query
        $diseaseNameMap = Disease::query()
            ->whereIn('id', $allDiseaseIds)
            ->orderBy('name')
            ->pluck('name', 'id');

        return $departments
            ->map(function (DiseaseDepartment $department) use ($diseaseNameMap) {
                $rawIds = is_array($department->diseases)
                    ? $department->diseases
                    : (json_decode($department->diseases ?? '[]', true) ?? []);

                $diseaseNames = collect($rawIds)
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->map(fn ($id) => $diseaseNameMap->get($id))
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'id'              => $department->id,
                    'department_name' => $department->department_name,
                    'disease_names'   => $diseaseNames,
                ];
            })
            ->all();
    }

    /**
     * Returns selected department labels for the tag display.
     * Each item: ['id' => int, 'name' => string]
     */
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

        return DiseaseDepartment::query()
            ->whereIn('id', $ids)
            ->orderBy('department_name')
            ->get(['id', 'department_name'])
            ->map(fn (DiseaseDepartment $d) => [
                'id'   => $d->id,
                'name' => $d->department_name,
            ])
            ->all();
    }

    public function removeDisease(int $departmentId): void
    {
        $target = (string) $departmentId;

        $this->assigned_diseases = array_values(array_filter(
            $this->assigned_diseases ?? [],
            fn ($id) => (string) $id !== $target
        ));
    }
}