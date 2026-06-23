<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pharmacist', 'technician', 'receptionist'] as $roleName) {
            Role::findOrCreate($roleName, 'filament');
        }
    }

    public function down(): void
    {
        Role::query()
            ->whereIn('name', ['pharmacist', 'technician', 'receptionist'])
            ->where('guard_name', 'filament')
            ->delete();
    }
};
