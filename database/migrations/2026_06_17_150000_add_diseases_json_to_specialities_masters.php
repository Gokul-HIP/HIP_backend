<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialities_masters', function (Blueprint $table) {
            if (! Schema::hasColumn('specialities_masters', 'diseases')) {
                $table->json('diseases')->nullable()->after('description');
            }
        });

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
                'symptoms'          => is_array($symptoms) ? $symptoms : [],
                'about'             => $row->about,
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
    }

    public function down(): void
    {
        Schema::table('specialities_masters', function (Blueprint $table) {
            if (Schema::hasColumn('specialities_masters', 'diseases')) {
                $table->dropColumn('diseases');
            }
        });
    }
};
