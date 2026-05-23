<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('member_id')
                ->constrained('hospitals')
                ->nullOnDelete();

            $table->string('appointment_type')->nullable()->after('consultation_type');

            $table->foreignId('department_id')
                ->nullable()
                ->after('appointment_type')
                ->constrained('specialities_masters')
                ->nullOnDelete();

            $table->uuid('patient_id')->nullable()->after('department_id');

            $table->string('relationship')->nullable()->after('patient_id');

            $table->string('reason_of_visit')->nullable()->after('purpose');

            $table->text('message')->nullable()->after('reason_of_visit');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn([
                'appointment_type',
                'patient_id',
                'relationship',
                'reason_of_visit',
                'message',
            ]);
        });
    }
};
