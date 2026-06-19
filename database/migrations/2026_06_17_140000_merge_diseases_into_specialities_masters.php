<?php

use App\Models\Doctor;
use App\Models\SpecialitiesMaster;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialities_masters', function (Blueprint $table) {
            if (! Schema::hasColumn('specialities_masters', 'about')) {
                $table->text('about')->nullable()->after('description');
            }
            if (! Schema::hasColumn('specialities_masters', 'symptoms')) {
                $table->json('symptoms')->nullable()->after('about');
            }
            if (! Schema::hasColumn('specialities_masters', 'recommended_tests')) {
                $table->json('recommended_tests')->nullable()->after('symptoms');
            }
            if (! Schema::hasColumn('specialities_masters', 'department_name')) {
                $table->string('department_name')->nullable()->after('recommended_tests');
            }
            if (! Schema::hasColumn('specialities_masters', 'department_image')) {
                $table->string('department_image')->nullable()->after('department_name');
            }
            if (! Schema::hasColumn('specialities_masters', 'legacy_department_id')) {
                $table->unsignedBigInteger('legacy_department_id')->nullable()->after('department_image');
            }
        });

        if (! Schema::hasTable('diseases')) {
            return;
        }

        $diseaseToSpeciality = [];

        DB::table('diseases')->orderBy('id')->get()->each(function ($disease) use (&$diseaseToSpeciality) {
            $existing = SpecialitiesMaster::query()
                ->where('name', $disease->name)
                ->first();

            $payload = [
                'about'              => $disease->about ?? null,
                'symptoms'           => $disease->symptoms,
                'recommended_tests'  => $disease->recommended_tests,
                'status'             => ($disease->is_active ?? true) ? 'active' : 'inactive',
            ];

            if ($existing) {
                $existing->update($payload);
                $diseaseToSpeciality[(int) $disease->id] = (int) $existing->id;

                return;
            }

            $speciality = SpecialitiesMaster::query()->create(array_merge([
                'name'        => $disease->name,
                'code'        => null,
                'description' => $disease->about ?? null,
            ], $payload));

            $diseaseToSpeciality[(int) $disease->id] = (int) $speciality->id;
        });

        if (Schema::hasTable('disease_departments')) {
            DB::table('disease_departments')->orderBy('id')->get()->each(function ($department) use ($diseaseToSpeciality) {
                $raw = json_decode($department->diseases ?? '[]', true) ?? [];
                $specialityIds = collect($raw)
                    ->map(fn ($id) => $diseaseToSpeciality[(int) $id] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                if ($specialityIds === []) {
                    return;
                }

                SpecialitiesMaster::query()
                    ->whereIn('id', $specialityIds)
                    ->update([
                        'department_name'       => $department->department_name,
                        'department_image'      => $department->department_image,
                        'legacy_department_id'  => (int) $department->id,
                    ]);

                DB::table('disease_departments')
                    ->where('id', $department->id)
                    ->update([
                        'diseases' => json_encode($specialityIds),
                    ]);
            });
        }

        Doctor::query()->select(['id', 'assigned_diseases'])->chunk(100, function ($doctors) use ($diseaseToSpeciality) {
            $departmentSpecialityMap = [];

            if (Schema::hasTable('disease_departments')) {
                DB::table('disease_departments')->orderBy('id')->get()->each(function ($department) use (&$departmentSpecialityMap, $diseaseToSpeciality) {
                    $raw = json_decode($department->diseases ?? '[]', true) ?? [];
                    $ids = collect($raw)
                        ->map(function ($id) use ($diseaseToSpeciality) {
                            $id = (int) $id;

                            return $diseaseToSpeciality[$id] ?? $id;
                        })
                        ->filter(fn ($id) => $id > 0)
                        ->unique()
                        ->values()
                        ->all();

                    $departmentSpecialityMap[(int) $department->id] = $ids;
                });
            }

            foreach ($doctors as $doctor) {
                $assigned = collect((array) ($doctor->assigned_diseases ?? []))
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0);

                if ($assigned->isEmpty()) {
                    continue;
                }

                $resolved = collect();

                foreach ($assigned as $id) {
                    if (isset($departmentSpecialityMap[$id])) {
                        $resolved = $resolved->merge($departmentSpecialityMap[$id]);
                        continue;
                    }

                    if (isset($diseaseToSpeciality[$id])) {
                        $resolved->push($diseaseToSpeciality[$id]);
                        continue;
                    }

                    if (SpecialitiesMaster::query()->whereKey($id)->exists()) {
                        $resolved->push($id);
                    }
                }

                $newIds = $resolved->unique()->values()->all();

                if ($newIds !== $assigned->unique()->values()->all()) {
                    $doctor->update(['assigned_diseases' => $newIds ?: null]);
                }
            }
        });

        if (Schema::hasTable('disease_packages') && Schema::hasColumn('disease_packages', 'disease_id')) {
            DB::table('disease_packages')
                ->whereNotNull('disease_id')
                ->orderBy('id')
                ->get()
                ->each(function ($package) use ($diseaseToSpeciality) {
                    $mapped = $diseaseToSpeciality[(int) $package->disease_id] ?? null;

                    if ($mapped) {
                        DB::table('disease_packages')
                            ->where('id', $package->id)
                            ->update(['disease_id' => $mapped]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('specialities_masters', function (Blueprint $table) {
            $columns = [
                'about',
                'symptoms',
                'recommended_tests',
                'department_name',
                'department_image',
                'legacy_department_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('specialities_masters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
