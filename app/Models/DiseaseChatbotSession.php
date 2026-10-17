<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiseaseChatbotSession extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_EXPIRED = 'expired';

    public const STATE_MENU = 'MENU';

    public const STATE_ASK_SYMPTOMS = 'ASK_SYMPTOMS';

    public const STATE_SHOW_RESULTS = 'SHOW_RESULTS';

    public const STATE_ASK_CONSULT = 'ASK_CONSULT';

    public const STATE_CHOOSE_PERSON = 'CHOOSE_PERSON';

    public const STATE_CHOOSE_SPECIALITY = 'CHOOSE_SPECIALITY';

    public const STATE_CHOOSE_DOCTOR = 'CHOOSE_DOCTOR';

    public const STATE_CHOOSE_DATE = 'CHOOSE_DATE';

    public const STATE_CHOOSE_SLOT = 'CHOOSE_SLOT';

    public const STATE_ASK_COINS = 'ASK_COINS';

    public const STATE_ASK_COINS_AMOUNT = 'ASK_COINS_AMOUNT';

    public const STATE_CONFIRM_SUMMARY = 'CONFIRM_SUMMARY';

    public const STATE_PAYMENT_PENDING = 'PAYMENT_PENDING';

    public const STATE_CONFIRMED = 'CONFIRMED';

    public const STATE_EXPIRED = 'EXPIRED';

    public const STATE_COMPLETED = 'COMPLETED';

    public const STATE_DISABLED = 'DISABLED';

    public const STATE_EMERGENCY_NOTICE = 'EMERGENCY_NOTICE';

    protected $table = 'disease_chatbot_sessions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'person_id',
        'hospital_id',
        'state',
        'context',
        'status',
        'last_activity_at',
        'expires_at',
    ];

    protected $casts = [
        'context' => 'array',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'hospital_id' => 'integer',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(DiseaseChatbotMessage::class, 'session_id')->orderBy('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'user_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Persons::class, 'person_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function isExpiredByIdle(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        $last = $this->last_activity_at;
        if ($last === null) {
            return false;
        }

        $minutes = (int) config('chatbot.idle_minutes', 30);

        return $last->lt(now()->subMinutes($minutes));
    }
}
