<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create React Flow blueprint templates table.
 *
 * After renaming the old message-template table, MySQL keeps the original
 * FK name (`workflow_templates_organization_id_foreign`) on
 * `workflow_message_templates`. This migration renames that FK first, then
 * creates the blueprint table with its own constraint name.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->renameLegacyOrganizationForeignKey();

        if (! Schema::hasTable('workflow_templates')) {
            Schema::create('workflow_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('module')->index();
                $table->string('trigger_type')->nullable()->index();
                $table->string('trigger_label')->nullable();
                $table->string('category')->nullable()->index();
                $table->json('definition');
                $table->string('thumbnail')->nullable();
                $table->string('status')->default('active')->index();
                $table->uuid('created_by')->nullable();
                $table->uuid('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['module', 'status']);
                $table->index(['organization_id', 'module']);

                $table->foreign('organization_id', 'wf_blueprint_templates_organization_id_foreign')
                    ->references('id')
                    ->on('organizations')
                    ->nullOnDelete();
            });

            return;
        }

        // Table may exist from a failed run without the organization FK.
        $this->ensureBlueprintOrganizationForeignKey();
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_templates');
    }

    protected function renameLegacyOrganizationForeignKey(): void
    {
        if (! Schema::hasTable('workflow_message_templates')) {
            return;
        }

        $legacy = 'workflow_templates_organization_id_foreign';
        $renamed = 'workflow_message_templates_organization_id_foreign';

        if ($this->constraintExists($legacy) && ! $this->constraintExists($renamed)) {
            DB::statement("ALTER TABLE `workflow_message_templates` DROP FOREIGN KEY `{$legacy}`");

            Schema::table('workflow_message_templates', function (Blueprint $table) use ($renamed) {
                $table->foreign('organization_id', $renamed)
                    ->references('id')
                    ->on('organizations')
                    ->nullOnDelete();
            });
        }
    }

    protected function ensureBlueprintOrganizationForeignKey(): void
    {
        $name = 'wf_blueprint_templates_organization_id_foreign';

        if ($this->constraintExists($name) || $this->constraintExists('workflow_templates_organization_id_foreign')) {
            return;
        }

        if (! Schema::hasColumn('workflow_templates', 'organization_id')) {
            return;
        }

        Schema::table('workflow_templates', function (Blueprint $table) use ($name) {
            $table->foreign('organization_id', $name)
                ->references('id')
                ->on('organizations')
                ->nullOnDelete();
        });
    }

    protected function constraintExists(string $name): bool
    {
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [$name, 'FOREIGN KEY']
        );

        return $row !== null;
    }
};
