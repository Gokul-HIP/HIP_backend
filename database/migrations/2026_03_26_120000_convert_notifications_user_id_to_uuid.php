<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        if (!Schema::hasTable('notifications') || !Schema::hasColumn('notifications', 'user_id')) {
            return;
        }

        $type = DB::table('information_schema.columns')
            ->where('table_schema', DB::raw('database()'))
            ->where('table_name', 'notifications')
            ->where('column_name', 'user_id')
            ->value('data_type');

        if ($type === 'bigint') {
            DB::statement('ALTER TABLE `notifications` MODIFY `user_id` CHAR(36) NOT NULL;');
        }
    }

    public function down(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        if (!Schema::hasTable('notifications') || !Schema::hasColumn('notifications', 'user_id')) {
            return;
        }

        $type = DB::table('information_schema.columns')
            ->where('table_schema', DB::raw('database()'))
            ->where('table_name', 'notifications')
            ->where('column_name', 'user_id')
            ->value('data_type');

        if ($type === 'char') {
            DB::statement('ALTER TABLE `notifications` MODIFY `user_id` BIGINT UNSIGNED NOT NULL;');
        }
    }
};

