<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SERVER NOTE — run only AFTER verifying data migration (2026_06_17_140000):
 *
 * 1. php artisan migrate --path=database/migrations/2026_06_17_140000_merge_diseases_into_specialities_masters.php
 * 2. Verify specialities_masters has disease rows (symptoms, about populated).
 * 3. Verify doctors.assigned_diseases contains specialities_masters IDs.
 * 4. Verify /api/diseases and /api/disease-doctors responses in staging.
 * 5. Then run this migration to drop legacy tables.
 *
 * Manual cleanup if needed before drop:
 *   SELECT COUNT(*) FROM diseases;
 *   SELECT id, name, symptoms FROM specialities_masters WHERE symptoms IS NOT NULL LIMIT 20;
 *   SELECT id, assigned_diseases FROM doctors WHERE assigned_diseases IS NOT NULL LIMIT 20;
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('disease_packages') && Schema::hasColumn('disease_packages', 'disease_id')) {
            Schema::table('disease_packages', function (Blueprint $table) {
                try {
                    $table->dropForeign(['disease_id']);
                } catch (\Throwable) {
                    // FK name may differ on server.
                }
            });

            Schema::table('disease_packages', function (Blueprint $table) {
                $table->foreign('disease_id')
                    ->references('id')
                    ->on('specialities_masters')
                    ->nullOnDelete();
            });
        }

        Schema::dropIfExists('diseases');
        Schema::dropIfExists('disease_departments');
    }

    public function down(): void
    {
        Schema::create('diseases', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->string('about')->nullable();
            $table->json('symptoms')->nullable();
            $table->json('recommended_tests')->nullable();
            $table->timestamps();
        });

        Schema::create('disease_departments', function (Blueprint $table) {
            $table->id();
            $table->string('department_name')->nullable();
            $table->string('department_image')->nullable();
            $table->json('diseases')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
