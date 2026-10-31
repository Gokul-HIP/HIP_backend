<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hms_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('hospital_ids');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->char('api_key_hash', 64);
            $table->text('signing_secret');
            $table->json('allowed_ips')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('rate_limit_per_minute')->default(120);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('api_key_hash');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hms_integrations');
    }
};
