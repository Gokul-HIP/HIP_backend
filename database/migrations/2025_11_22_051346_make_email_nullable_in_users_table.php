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
            $table->string('email')->nullable()->change();
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('gender')->nullable()->change();
            $table->date('dob')->nullable()->change();
            $table->boolean('profile_update')->default(0)->after('otp_expires');   //0 -> user not updated profile if updated it will be 1 in db
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('healthinpocket_users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->string('gender')->nullable(false)->change();
            $table->date('dob')->nullable(false)->change();
        });
    }
};
