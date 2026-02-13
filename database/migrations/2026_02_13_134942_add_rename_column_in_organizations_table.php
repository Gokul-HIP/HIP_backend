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
        Schema::table('organizations', function (Blueprint $table) {
            $table->renameColumn('org_name', 'name');
            $table->renameColumn('org_city', 'city');
            $table->renameColumn('org_address', 'address');
            $table->renameColumn('org_logo', 'logo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->renameColumn('name', 'org_name');
            $table->renameColumn('city', 'org_city');
            $table->renameColumn('address', 'org_address');
            $table->renameColumn('logo', 'org_logo');
        });
    }
};
