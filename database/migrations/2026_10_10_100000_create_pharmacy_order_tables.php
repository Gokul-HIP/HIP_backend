<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pharmacy_orders')) {
            Schema::create('pharmacy_orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number', 30)->unique();
                $table->foreignUuid('hip_user_id')->constrained('healthinpocket_users')->restrictOnDelete();
                $table->foreignUuid('person_id')->nullable()->constrained('persons')->nullOnDelete();
                $table->foreignId('hospital_id')->constrained('hospitals');
                $table->foreignId('pharmacy_id')->constrained('pharmacies');
                $table->foreignId('prescription_document_id')->nullable()->constrained('documents')->nullOnDelete();
                $table->string('status', 30)->default('requested')->index();
                $table->string('payment_status', 20)->default('unpaid')->index();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('delivery_fee', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->text('delivery_name')->nullable();
                $table->text('delivery_phone')->nullable();
                $table->text('delivery_address')->nullable();
                $table->foreignUuid('assigned_pharmacist_id')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
                $table->uuid('cancelled_by')->nullable();
                $table->text('cancel_reason')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('processing_at')->nullable();
                $table->timestamp('ready_at')->nullable();
                $table->timestamp('out_for_delivery_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->userstampsUuid();
                $table->timestamps();

                $table->index(['pharmacy_id', 'status']);
                $table->index(['hip_user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('pharmacy_order_items')) {
            Schema::create('pharmacy_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('pharmacy_orders')->cascadeOnDelete();
                $table->foreignId('pharmacy_product_id')->nullable()->constrained('pharmacy_products')->nullOnDelete();
                $table->string('name')->nullable();
                $table->string('dosage_form')->nullable();
                $table->string('strength')->nullable();
                $table->unsignedInteger('qty');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('line_total', 12, 2);
                $table->boolean('is_custom')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pharmacy_order_comments')) {
            Schema::create('pharmacy_order_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('pharmacy_orders')->cascadeOnDelete();
                $table->string('author_type', 20);
                $table->uuid('author_id')->nullable();
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('pharmacy_order_status_logs')) {
            Schema::create('pharmacy_order_status_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('pharmacy_orders')->cascadeOnDelete();
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30);
                $table->string('actor_type', 20);
                $table->uuid('actor_id')->nullable();
                $table->text('note')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_order_status_logs');
        Schema::dropIfExists('pharmacy_order_comments');
        Schema::dropIfExists('pharmacy_order_items');
        Schema::dropIfExists('pharmacy_orders');
    }
};
