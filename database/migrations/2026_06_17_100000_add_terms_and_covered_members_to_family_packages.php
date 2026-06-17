<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_packages', function (Blueprint $table) {
            $table->json('terms_conditions')->nullable()->after('benefits');
        });

        Schema::table('user_family_subscriptions', function (Blueprint $table) {
            $table->json('covered_member_ids')->nullable()->after('family_package_id');
        });
    }

    public function down(): void
    {
        Schema::table('family_packages', function (Blueprint $table) {
            $table->dropColumn('terms_conditions');
        });

        Schema::table('user_family_subscriptions', function (Blueprint $table) {
            $table->dropColumn('covered_member_ids');
        });
    }
};
