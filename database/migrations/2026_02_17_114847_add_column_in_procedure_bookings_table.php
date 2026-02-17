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
        Schema::table('procedure_bookings', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->constrained('healthinpocket_users')->nullOnDelete()->after('mobile_number');
            $table->date('booking_date')->nullable()->after('member_id');
            $table->json('required_time_slots')->nullable()->after('booking_date');
            $table->enum('status', ['pending','confirmed','cancelled','completed'])->default('pending')->after('required_time_slots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procedure_bookings', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropColumn('member_id');
            $table->dropColumn('booking_date');
            $table->dropColumn('required_time_slots');
            $table->dropColumn('status');
        });
    }
};
