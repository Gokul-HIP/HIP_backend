<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->decimal('hospital_admin_latitude', 10, 7)->nullable()->change();
            $table->decimal('hospital_admin_longitude', 10, 7)->nullable()->change();
            $table->string('hospital_subtitle')->nullable()->after('hospital_name');
            $table->longText('hospital_about')->nullable()->after('hospital_subtitle');
            $table->boolean('is_promoted')->default(false)->after('status');
            $table->timestamp('promoted_start_at')->nullable()->after('is_promoted');
            $table->timestamp('promoted_end_at')->nullable()->after('promoted_start_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn('hospital_subtitle');
            $table->dropColumn('hospital_about');
            $table->string('hospital_admin_latitude')->change();
            $table->string('hospital_admin_longitude')->change();
            $table->dropColumn([
                'is_promoted',
                'promoted_start_at',
                'promoted_end_at',
            ]);
        });
    }
};
