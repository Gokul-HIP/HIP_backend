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
            $table->string('mobile_num')->unique()->after('last_name');
            $table->string('gender')->after('mobile_num');
            $table->date('dob')->nullable()->after('gender');
            $table->string('otp')->nullable()->after('dob');
            $table->dateTime('otp_expires')->nullable()->after('otp');
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('healthinpocket_users', function (Blueprint $table) {
            $table->dropColumn(['mobile_num','dob','gender']);
        });
    }
};
