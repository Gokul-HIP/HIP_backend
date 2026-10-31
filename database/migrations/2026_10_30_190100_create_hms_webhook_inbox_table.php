<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hms_webhook_inbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->nullable()->constrained('hms_integrations')->nullOnDelete();
            $table->uuid('request_id');
            $table->string('event_id', 191)->nullable();
            $table->string('event_type', 100);
            $table->string('method', 10);
            $table->string('path', 1024);
            $table->json('headers');
            $table->json('payload');
            $table->string('state', 32)->default('received');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();

            $table->index('request_id');
            $table->index(['integration_id', 'received_at']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['sqlite', 'pgsql'], true)) {
            DB::statement(
                'CREATE UNIQUE INDEX hms_webhook_inbox_integration_event_unique '
                .'ON hms_webhook_inbox (integration_id, event_id) WHERE event_id IS NOT NULL'
            );

            return;
        }

        Schema::table('hms_webhook_inbox', function (Blueprint $table) {
            $table->unique(['integration_id', 'event_id'], 'hms_webhook_inbox_integration_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hms_webhook_inbox');
    }
};
