<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('healthinpocket_users', function (Blueprint $table) {
            if (! Schema::hasColumn('healthinpocket_users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (! Schema::hasColumn('healthinpocket_users', 'deactivated_at')) {
                $table->timestamp('deactivated_at')->nullable();
            }
        });

        if (Schema::hasColumn('healthinpocket_users', 'is_active')) {
            DB::table('healthinpocket_users')->whereNull('is_active')->update(['is_active' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('healthinpocket_users', function (Blueprint $table) {
            foreach (['deactivated_at', 'is_active'] as $column) {
                if (Schema::hasColumn('healthinpocket_users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
