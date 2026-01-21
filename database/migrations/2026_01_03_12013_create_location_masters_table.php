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
        Schema::create('location_masters', function (Blueprint $table) {
            $table->id();
            $table->string('state');
            $table->string('city');
            $table->string('area');
            $table->string('zipcode', 10);
            $table->string('area_code')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->index(['city', 'area']);
            $table->index('zipcode');
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_masters');
    }
};
