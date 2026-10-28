<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hms_external_id_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('hms_integrations')->cascadeOnDelete();
            $table->string('entity_type', 32);
            $table->string('external_id', 191);
            $table->string('local_id', 64);
            $table->timestamps();

            $table->unique(
                ['integration_id', 'entity_type', 'external_id'],
                'hms_external_id_maps_lookup_unique'
            );
            $table->index(['integration_id', 'entity_type', 'local_id'], 'hms_external_id_maps_local_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hms_external_id_maps');
    }
};
