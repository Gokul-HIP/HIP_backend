<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_family_subscriptions', function (Blueprint $table) {
            $table->string('payment_mode', 20)->nullable()->after('payment_status');
            $table->timestamp('activated_at')->nullable()->after('payment_mode');
            $table->foreignUuid('created_by')->nullable()->after('activated_at')
                ->constrained('healthinpocket_users')->nullOnDelete();
        });

        // Allow pending subscriptions for online payment flows initiated by cashier.
        DB::statement("ALTER TABLE user_family_subscriptions MODIFY status ENUM('pending', 'active', 'expired', 'cancelled') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        Schema::table('user_family_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['payment_mode', 'activated_at', 'created_by']);
        });

        DB::statement("UPDATE user_family_subscriptions SET status = 'expired' WHERE status = 'pending'");
        DB::statement("ALTER TABLE user_family_subscriptions MODIFY status ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active'");
    }
};
