<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HmsIntegration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'hospital_ids',
        'organization_id',
        'api_key_hash',
        'signing_secret',
        'allowed_ips',
        'is_enabled',
        'rate_limit_per_minute',
        'last_used_at',
    ];

    protected $hidden = [
        'api_key_hash',
        'signing_secret',
    ];

    protected $casts = [
        'hospital_ids' => 'array',
        'allowed_ips' => 'array',
        'is_enabled' => 'boolean',
        'rate_limit_per_minute' => 'integer',
        'last_used_at' => 'datetime',
        'signing_secret' => 'encrypted',
    ];

    public function inboxEntries(): HasMany
    {
        return $this->hasMany(HmsWebhookInbox::class, 'integration_id');
    }

    public static function hashKey(string $apiKey): string
    {
        return hash('sha256', $apiKey);
    }
}
