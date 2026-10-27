<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ad_placements MODIFY placement_type ENUM('home_banner','mid_scroll','overlay','content_break','post_action','bottom_banner','fixed') NULL");
    }

    public function down(): void
    {
        if (DB::table('ad_placements')->where('placement_type', 'fixed')->exists()) {
            throw new RuntimeException('Cannot remove the fixed placement type while ads use it.');
        }

        DB::statement("ALTER TABLE ad_placements MODIFY placement_type ENUM('home_banner','mid_scroll','overlay','content_break','post_action','bottom_banner') NULL");
    }
};