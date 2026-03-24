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
        Schema::create('procedure_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('message')->nullable();
            $table->foreignId('procedure_id')->nullable()->constrained('procedures')->nullOnDelete();
            $table->foreignUuid('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('procedure_bookings')) {

            Schema::table('procedure_bookings', function (Blueprint $table) {
                $table->dropForeign(['procedure_id']);
                $table->dropForeign(['hospital_id']);
            });

            Schema::dropIfExists('procedure_bookings');
        }
    }
};