<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('healthinpocket_users', function (Blueprint $table) {
            // Holds the address being verified; committed to `email` only after link is clicked.
            $table->string('pending_email')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('healthinpocket_users', function (Blueprint $table) {
            $table->dropColumn('pending_email');
        });
    }
};
