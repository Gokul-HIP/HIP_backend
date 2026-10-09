<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing person columns if they don't already exist.
        Schema::table('persons', function (Blueprint $table) {
            if (! Schema::hasColumn('persons', 'blood_group')) {
                $table->string('blood_group')->nullable()->after('dob');
            }

            if (! Schema::hasColumn('persons', 'relationship')) {
                $table->string('relationship')->nullable()->after('parent_id');
            }

            if (! Schema::hasColumn('persons', 'relationship_other')) {
                $table->string('relationship_other')->nullable()->after('relationship');
            }

            if (! Schema::hasColumn('persons', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('relationship_other');
            }

            if (! Schema::hasColumn('persons', 'emergency_contact_number')) {
                $table->string('emergency_contact_number')->nullable()->after('emergency_contact_name');
            }

            if (! Schema::hasColumn('persons', 'address')) {
                $table->text('address')->nullable()->after('emergency_contact_number');
            }

            if (! Schema::hasColumn('persons', 'hospital_id')) {
                // hospitals.id is unsignedBigInteger() in migrations; match that type.
                $table->unsignedBigInteger('hospital_id')->nullable()->after('hip_user_id');
            }

            if (! Schema::hasColumn('persons', 'mrn')) {
                $table->string('mrn', 20)->nullable()->after('image');
            }

            // Ensure parent_id exists and index for faster lookups.
            if (! Schema::hasColumn('persons', 'parent_id')) {
                $table->uuid('parent_id')->nullable()->after('image');
            }
        });

        // Convert any empty-string MRNs to NULL so unique index can be applied safely.
        if (Schema::hasColumn('persons', 'mrn')) {
            try {
                DB::transaction(function () {
                    // Convert empty strings to NULL
                    DB::table('persons')->whereRaw("mrn = ''")->update(['mrn' => null]);
                });
            } catch (\Throwable $e) {
                // ignore conversion errors; we'll avoid adding unique index if unsafe
            }

            // Check for duplicates or still-empty values
            $hasDuplicates = false;
            $hasEmpty = false;
            try {
                $hasDuplicates = DB::table('persons')
                    ->select('mrn')
                    ->whereNotNull('mrn')
                    ->groupBy('mrn')
                    ->havingRaw('COUNT(*) > 1')
                    ->limit(1)
                    ->exists();

                $hasEmpty = DB::table('persons')->whereNull('mrn')->exists() && DB::table('persons')->whereRaw("mrn IS NULL")->exists();
            } catch (\Throwable $e) {
                $hasDuplicates = true;
                $hasEmpty = true;
            }

            if (! $hasDuplicates && ! $hasEmpty) {
                // Add unique index safely.
                Schema::table('persons', function (Blueprint $table) {
                    try {
                        $table->unique('mrn');
                    } catch (\Throwable $e) {
                        // ignore
                    }
                });
            }
        }

        // Add indexes for parent_id and hospital_id if present
        Schema::table('persons', function (Blueprint $table) {
            try { if (Schema::hasColumn('persons', 'parent_id')) $table->index('parent_id'); } catch (\Throwable $e) {}
            try { if (Schema::hasColumn('persons', 'hospital_id')) $table->index('hospital_id'); } catch (\Throwable $e) {}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            if (Schema::hasColumn('persons', 'blood_group')) {
                $table->dropColumn('blood_group');
            }
            if (Schema::hasColumn('persons', 'relationship')) {
                $table->dropColumn('relationship');
            }
            if (Schema::hasColumn('persons', 'relationship_other')) {
                $table->dropColumn('relationship_other');
            }
            if (Schema::hasColumn('persons', 'emergency_contact_name')) {
                $table->dropColumn('emergency_contact_name');
            }
            if (Schema::hasColumn('persons', 'emergency_contact_number')) {
                $table->dropColumn('emergency_contact_number');
            }
            if (Schema::hasColumn('persons', 'address')) {
                $table->dropColumn('address');
            }
            if (Schema::hasColumn('persons', 'hospital_id')) {
                try { $table->dropIndex(['hospital_id']); } catch (\Throwable $e) {}
                $table->dropColumn('hospital_id');
            }
            if (Schema::hasColumn('persons', 'mrn')) {
                try { $table->dropUnique(['mrn']); } catch (\Throwable $e) {}
                $table->dropColumn('mrn');
            }
            try { $table->dropIndex(['parent_id']); } catch (\Throwable $e) {}
        });
    }
};
