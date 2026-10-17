<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiseaseChatbotMessage extends Model
{
    public const SENDER_BOT = 'bot';

    public const SENDER_USER = 'user';

    protected $table = 'disease_chatbot_messages';

    protected $fillable = [
        'session_id',
        'sender',
        'type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(DiseaseChatbotSession::class, 'session_id');
    }
}
