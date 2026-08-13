<?php

namespace App\Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Channel message body templates (WhatsApp/Email/SMS/Push copy).
 * Not React Flow workflow blueprints — see {@see WorkflowTemplate}.
 */
class WorkflowMessageTemplate extends Model
{
    protected $table = 'workflow_message_templates';

    protected $fillable = [
        'organization_id',
        'name',
        'channel',
        'category',
        'locale',
        'body',
        'variables',
        'is_active',
        'version_number',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
