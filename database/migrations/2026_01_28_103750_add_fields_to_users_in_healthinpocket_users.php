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
        Schema::table('healthinpocket_users', function (Blueprint $table) {
            $table->uuid('hospital_id')->nullable()->after('id');
            $table->uuid('organization_id')->nullable()->after('hospital_id');
            $table->string('role')->nullable()->after('organization_id');
            $table->string('mobile_num')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('healthinpocket_users', function (Blueprint $table) {
            $table->dropColumn(['hospital_id', 'organization_id', 'role']);
            $table->string('mobile_num')->nullable(false)->change();
        });
    }
};
