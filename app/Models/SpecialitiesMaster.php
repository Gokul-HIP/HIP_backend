<?php

namespace App\Models;

use App\Support\SpecialityDiseaseId;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use Illuminate\Support\Facades\Storage;

class SpecialitiesMaster extends Model
{
    use Userstamps;

    protected $fillable = [
        'name',
        'code',
        'description',
        'diseases',
        'department_name',
        'department_image',
        'legacy_department_id',
        'display_image',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'diseases' => 'array',
    ];

    /**
     * @return array<int, array{name: string, symptoms: array, about?: string, recommended_tests?: array}>
     */
    public function normalizedDiseaseEntries(): array
    {
        $entries = collect($this->diseases ?? [])
            ->filter(fn ($entry) => is_array($entry) && filled($entry['name'] ?? null))
            ->values()
            ->map(function ($entry) {
                return [
                    'name'              => (string) $entry['name'],
                    'symptoms'          => array_values((array) ($entry['symptoms'] ?? [])),
                    'about'             => $entry['about'] ?? null,
                    'recommended_tests' => array_values((array) ($entry['recommended_tests'] ?? [])),
                ];
            })
            ->all();

        if ($entries !== []) {
            return $entries;
        }

        return [];
    }

    public function formatDiseaseForApi(int $index, ?string $departmentImage = null): ?array
    {
        $entries = $this->normalizedDiseaseEntries();

        if (! array_key_exists($index, $entries)) {
            return null;
        }

        $entry = $entries[$index];

        return [
            'id'               => SpecialityDiseaseId::encode((int) $this->id, $index),
            'name'             => $entry['name'],
            'symptoms'         => $entry['symptoms'] ?? [],
            'department_image' => $departmentImage,
        ];
    }

    public function matchesDiseaseSearch(?string $search): bool
    {
        if ($search === null || $search === '') {
            return true;
        }

        $term = mb_strtolower($search);

        if (str_contains(mb_strtolower((string) $this->name), $term)
            || str_contains(mb_strtolower((string) ($this->department_name ?? '')), $term)) {
            return true;
        }

        foreach ($this->normalizedDiseaseEntries() as $entry) {
            if (str_contains(mb_strtolower((string) ($entry['name'] ?? '')), $term)) {
                return true;
            }

            foreach ($entry['symptoms'] ?? [] as $symptom) {
                if (str_contains(mb_strtolower((string) $symptom), $term)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function departmentKey(): string
    {
        return (string) ($this->legacy_department_id ?? $this->department_name ?? 'general');
    }

    public function departmentImageUrl(): ?string
    {
        if (! $this->department_image) {
            return null;
        }

        return url('storage/' . ltrim(str_replace('\\', '/', $this->department_image), '/'));
    }

    public static function resolveDepartmentImageUrl(
        ?int $legacyDepartmentId = null,
        ?string $departmentName = null
    ): ?string {
        $query = static::query()
            ->where('status', 'active')
            ->whereNotNull('department_image')
            ->where('department_image', '!=', '');

        if ($legacyDepartmentId) {
            $match = (clone $query)->where('legacy_department_id', $legacyDepartmentId)->first();

            if ($match) {
                return $match->departmentImageUrl();
            }
        }

        if ($departmentName) {
            $match = (clone $query)->where('department_name', $departmentName)->first();

            if ($match) {
                return $match->departmentImageUrl();
            }
        }

        return null;
    }

    /**
     * Flat list of diseases across all active specialities for package/search dropdowns.
     *
     * @return \Illuminate\Support\Collection<int, object{id: int, name: string, speciality_name: string}>
     */
    public static function searchableDiseaseOptions(?string $term = null, int $limit = 15): \Illuminate\Support\Collection
    {
        $term = trim((string) $term);

        return static::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->flatMap(function (self $speciality) use ($term) {
                return collect($speciality->normalizedDiseaseEntries())
                    ->values()
                    ->map(function (array $entry, int $index) use ($speciality, $term) {
                        $name = (string) ($entry['name'] ?? '');

                        if ($name === '') {
                            return null;
                        }

                        if ($term !== '' && ! str_contains(mb_strtolower($name), mb_strtolower($term))) {
                            return null;
                        }

                        return (object) [
                            'id'              => SpecialityDiseaseId::encode((int) $speciality->id, $index),
                            'name'            => $name,
                            'speciality_name' => $speciality->name,
                        ];
                    })
                    ->filter();
            })
            ->take($limit)
            ->values();
    }

    public function procedureMasters()
    {
        return $this->hasMany(ProcedureMaster::class, 'speciality_master_id');
    }

    protected static function booted()
    {
        static::deleting(function ($record) {
            if ($record->display_image && Storage::disk('public')->exists($record->display_image)) {
                Storage::disk('public')->delete($record->display_image);
            }

            if ($record->department_image && Storage::disk('public')->exists($record->department_image)) {
                Storage::disk('public')->delete($record->department_image);
            }
        });
    }
}
