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
        Schema::create('procedure_masters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('duration');
            $table->foreignId('speciality_master_id')->nullable()->constrained('specialities_masters')->nullOnDelete();
            $table->bigInteger("cost")->nullable();
            $table->longText("description")->nullable();
            $table->string("status")->default("active");
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_masters');
    }
};
