<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cron_jobs')) {
            return;
        }

        $now = now();
        $command = 'hospital-automation:dispatch-pending-payments';

        if (! DB::table('cron_jobs')->where('command', $command)->exists()) {
            DB::table('cron_jobs')->insert([
                'name' => 'Payment Pending Detector',
                'command' => $command,
                'schedule_type' => 'every_minute',
                'schedule' => '* * * * *',
                'description' => 'Dispatch PaymentPending for invoices still pending after the 30-minute reminder window.',
                'timezone' => 'Asia/Kolkata',
                'is_active' => true,
                'next_run_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('cron_jobs')
            ->where('command', 'reminders:pending-payments')
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('cron_jobs')) {
            return;
        }

        DB::table('cron_jobs')
            ->where('command', 'hospital-automation:dispatch-pending-payments')
            ->delete();
    }
};
