<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotSession extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'session_id',
        'organization_id',
        'hospital_id',
        'user_id',
        'status',
        'last_message_at',
        'ended_at',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'hospital_id' => 'integer',
        // users.id / HIPUser.id are UUID strings — never cast to integer.
        'user_id' => 'string',
        'last_message_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'session_id', 'session_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
