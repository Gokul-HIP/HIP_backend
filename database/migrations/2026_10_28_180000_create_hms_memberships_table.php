<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hms_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_id')->constrained('hms_integrations')->cascadeOnDelete();
            $table->string('hms_membership_id');
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('plan_id');
            $table->uuid('hip_user_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('type', 16);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('expected_amount', 12, 2);
            $table->boolean('price_mismatch')->default(false);
            $table->string('payment_reference');
            $table->char('request_hash', 64);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['integration_id', 'hms_membership_id'], 'hms_memberships_integration_id_unique');
            $table->unique(['integration_id', 'payment_reference'], 'hms_memberships_integration_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hms_memberships');
    }
};
