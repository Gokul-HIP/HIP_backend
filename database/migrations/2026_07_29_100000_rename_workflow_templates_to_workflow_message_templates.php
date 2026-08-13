<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Free the `workflow_templates` table name for React Flow blueprint templates.
 * Existing rows are message/channel bodies used by TemplateManager — not workflow graphs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workflow_templates') && ! Schema::hasTable('workflow_message_templates')) {
            Schema::rename('workflow_templates', 'workflow_message_templates');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workflow_message_templates') && ! Schema::hasTable('workflow_templates')) {
            Schema::rename('workflow_message_templates', 'workflow_templates');
        }
    }
};
