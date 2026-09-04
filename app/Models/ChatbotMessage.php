<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    protected $fillable = [
        'session_id',
        'organization_id',
        'hospital_id',
        'user_id',
        'workflow_id',
        'execution_id',
        'role',
        'content',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'hospital_id' => 'integer',
        // users.id / HIPUser.id are UUID strings — never cast to integer.
        'user_id' => 'string',
        'workflow_id' => 'integer',
        'execution_id' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatbotSession::class, 'session_id', 'session_id');
    }
}
