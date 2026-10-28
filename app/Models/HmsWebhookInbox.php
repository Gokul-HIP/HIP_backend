<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmsWebhookInbox extends Model
{
    public const STATE_RECEIVED = 'received';

    public const STATE_PROCESSED = 'processed';

    public const STATE_FAILED = 'failed';

    public const STATE_DUPLICATE = 'duplicate';

    public $timestamps = false;

    protected $table = 'hms_webhook_inbox';

    protected $fillable = [
        'integration_id',
        'request_id',
        'event_id',
        'event_type',
        'method',
        'path',
        'headers',
        'payload',
        'state',
        'response_status',
        'response_body',
        'error',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'response_body' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(HmsIntegration::class, 'integration_id');
    }
}
