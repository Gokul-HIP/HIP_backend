<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {

            $table->string('hospital_address')->nullable()->change();
            $table->string('hospital_logo')->nullable()->change();
            $table->string('hospital_admin_name')->nullable()->change();
            $table->string('hospital_admin_contact')->nullable()->change();
            $table->string('hospital_admin_email')->nullable()->change();
            $table->string('hospital_admin_address')->nullable()->change();

            $table->decimal('hospital_admin_latitude', 10, 7)->nullable()->change();
            $table->decimal('hospital_admin_longitude', 10, 7)->nullable()->change();

            $table->string('hospital_subtitle')->nullable()->change();
            $table->longText('hospital_about')->nullable()->change();

            $table->foreignId('diagnostic_center_id')->nullable()->change();
            $table->json('pharmacy_ids')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->string('hospital_address')->nullable(false)->change();
            $table->string('hospital_logo')->nullable(false)->change();
            $table->string('hospital_admin_name')->nullable(false)->change();
            $table->string('hospital_admin_contact')->nullable(false)->change();
            $table->string('hospital_admin_email')->nullable(false)->change();
            $table->string('hospital_admin_address')->nullable(false)->change();

            $table->decimal('hospital_admin_latitude', 10, 7)->nullable(false)->change();
            $table->decimal('hospital_admin_longitude', 10, 7)->nullable(false)->change();

            $table->string('hospital_subtitle')->nullable(false)->change();
            $table->longText('hospital_about')->nullable(false)->change();

            $table->foreignId('diagnostic_center_id')->nullable(false)->change();
            $table->json('pharmacy_ids')->nullable(false)->change();
        });
    }
};
?>