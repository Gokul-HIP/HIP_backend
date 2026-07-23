<?php

namespace App\Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowTemplate extends Model
{
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
