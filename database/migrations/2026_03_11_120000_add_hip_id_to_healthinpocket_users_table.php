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
            $table->string('hip_id')->nullable()->unique()->after('id');
        });

        // DB::table('healthinpocket_users')
        //     ->select('id')
        //     ->orderBy('id')
        //     ->get()
        //     ->each(function ($user): void {
        //         DB::table('healthinpocket_users')
        //             ->where('id', $user->id)
        //             ->update([
        //                 'hip_id' => 'HIP' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
        //             ]);
        //     });
    }

    public function down(): void
    {
        Schema::table('healthinpocket_users', function (Blueprint $table) {
            $table->dropUnique(['hip_id']);
            $table->dropColumn('hip_id');
        });
    }
};
