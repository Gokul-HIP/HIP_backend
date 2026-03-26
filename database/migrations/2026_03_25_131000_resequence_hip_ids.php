<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('healthinpocket_users') || !Schema::hasColumn('healthinpocket_users', 'hip_id')) {
            return;
        }

        // Re-sequence all hip_id values to: HIP00001, HIP00002, ...
        // Order by created_at then id to approximate registration order.
        $users = DB::table('healthinpocket_users')
            ->select(['id', 'created_at'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $seq = 1;
        foreach ($users as $user) {
            $hipId = 'HIP' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
            DB::table('healthinpocket_users')
                ->where('id', $user->id)
                ->update(['hip_id' => $hipId]);
            $seq++;
        }
    }

    public function down(): void
    {
        // No rollback: hip_id sequence is a data format.
    }
};

