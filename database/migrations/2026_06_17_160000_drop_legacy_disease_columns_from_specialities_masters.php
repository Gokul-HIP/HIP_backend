<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('specialities_masters')->orderBy('id')->get()->each(function ($row) {
            $existing = json_decode($row->diseases ?? '[]', true);

            if (is_array($existing) && $existing !== []) {
                return;
            }

            $symptoms = json_decode($row->symptoms ?? 'null', true);
            $recommendedTests = json_decode($row->recommended_tests ?? 'null', true);

            if (! is_array($symptoms) && empty($row->about) && ! is_array($recommendedTests)) {
                return;
            }

            $entry = array_filter([
                'name'              => $row->name,
                'about'             => $row->about,
                'symptoms'          => is_array($symptoms) ? $symptoms : [],
                'recommended_tests' => is_array($recommendedTests) ? $recommendedTests : [],
            ], fn ($value) => $value !== null && $value !== []);

            if ($entry === []) {
                return;
            }

            DB::table('specialities_masters')
                ->where('id', $row->id)
                ->update([
                    'diseases' => json_encode([$entry]),
                ]);
        });

        Schema::table('specialities_masters', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('specialities_masters', 'about') ? 'about' : null,
                Schema::hasColumn('specialities_masters', 'symptoms') ? 'symptoms' : null,
                Schema::hasColumn('specialities_masters', 'recommended_tests') ? 'recommended_tests' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn(array_values($columns));
            }
        });
    }

    public function down(): void
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
        });
    }
};
