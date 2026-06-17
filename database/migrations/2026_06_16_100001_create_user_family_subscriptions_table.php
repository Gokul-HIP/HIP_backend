<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_family_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('hip_user_id')->constrained('healthinpocket_users')->cascadeOnDelete();
            $table->foreignId('family_package_id')->constrained('family_packages')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->enum('payment_status', ['paid', 'pending', 'free'])->default('paid');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['hip_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_family_subscriptions');
    }
};
