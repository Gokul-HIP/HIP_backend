<?php declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Chiiya\FilamentAccessControl\Contracts\AccessControlUser;
use Chiiya\FilamentAccessControl\Database\Factories\FilamentUserFactory;
use Chiiya\FilamentAccessControl\Enumerators\Feature;
use Chiiya\FilamentAccessControl\Enumerators\RoleName;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser as FilamentUserInterface;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use App\Models\DoctorBooking;
use App\Models\DoctorBookingStatus;
use App\Models\CaregiverBooking;
use App\Models\CaregiverBookingStatus;
use App\Models\ContentLike;
use App\Models\ContentView;
use App\Models\ContentComment;
// use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Chiiya\FilamentAccessControl\Models\FilamentUser.
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property null|string $first_name
 * @property null|string $last_name
 * @property null|Carbon|CarbonImmutable $expires_at
 * @property null|string $remember_token
 * @property null|Carbon|CarbonImmutable $created_at
 * @property null|Carbon|CarbonImmutable $updated_at
 * @property string $full_name
 * @property DatabaseNotification[]|DatabaseNotificationCollection $notifications
 * @property null|int $notifications_count
 * @property Collection|Permission[] $permissions
 * @property null|int $permissions_count
 * @property Collection|Role[] $roles
 * @property null|int $roles_count
 *
 * @method static Builder|FilamentUser newModelQuery()
 * @method static Builder|FilamentUser newQuery()
 * @method static Builder|FilamentUser permission($permissions)
 * @method static Builder|FilamentUser query()
 * @method static Builder|FilamentUser role($roles, $guard = null)
 *
 * @mixin \Eloquent
 */
class HIPUser extends Authenticatable implements AccessControlUser, FilamentUserInterface, HasEmailAuthentication, HasName
{
    use HasUuids;
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use HasApiTokens;
    // use SoftDeletes;

    /** {@inheritDoc} */

    protected $guard_name = 'filament';
    protected $table = 'healthinpocket_users';
    public $incrementing = false;
    protected $keyType = 'string';

    /** {@inheritDoc} */
    protected $hidden = ['password', 'remember_token'];

    /** {@inheritDoc} */
    protected $fillable = ['hip_id', 'email', 'pending_email', 'password', 'first_name', 'last_name', 'expires_at','mobile_num','gender','dob','otp',
    'otp_expires','profile_image','organization_id','hospital_id','role','marital_status','blood_group','preferred_branch_id',
    'emergency_contact_person_name','emergency_contact_person_phone','emergency_contact_person_relationship','house_number','street',
    'city','state','zip_code','mobile_verified_at','email_verified_at','email_verification_token','email_verification_token_expires_at'];

    /** {@inheritDoc} */
    protected $casts = [
        'expires_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'email_verification_token_expires_at' => 'datetime',
    ];

    /**
     * {@inheritDoc}
     */
    protected static function newFactory(): FilamentUserFactory
    {
        return FilamentUserFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $user): void {
            // Ensure hip_id is sequential: HIP00001, HIP00002, ... (based on registration order).
            // Do not derive it from UUID primary key.
            if (!($user->hip_id ?? null) || !preg_match('/^HIP[0-9]+$/', (string) $user->hip_id)) {
                $lastError = null;
                for ($attempt = 0; $attempt < 5; $attempt++) {
                    try {
                        $user->forceFill(['hip_id' => self::generateNextHipId()])->saveQuietly();
                        return;
                    } catch (QueryException $e) {
                        $lastError = $e;
                        // Handle rare race-condition where two requests generate the same next id.
                        if (str_contains(strtolower($e->getMessage()), 'duplicate')) {
                            usleep(200000); // 0.2s
                            continue;
                        }
                        throw $e;
                    }
                }

                if ($lastError) {
                    throw $lastError;
                }
            }
        });
    }

    /**
     * Check whether the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        // Only 'super-admin' role, not 'super-admin-hip'
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user is super admin for custom dashboard
     */
    public function isSuperAdminHip(): bool
    {
        return $this->hasRole('super-admin-hip');
    }

    /**
     * Check if user is hospital admin
     */
    public function isHospitalAdmin(): bool
    {
        return $this->hasRole('hospital_admin');
    }


    /**
     * Provides full name of the current filament user.
     */
    public function getFullNameAttribute(): string
    {
        if (!$this->first_name && !$this->last_name) {
            return '—';
        }

        $name = $this->first_name ?? '';

        if ($this->last_name) {
            $name .= ' ' . $this->last_name;
        }

        return $name;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'master') {
            return $this->hasRole('super-admin');
        }
        
        return true;
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    /**
     * Return a name.
     *
     * Needed for compatibility with filament-logger.
     */
    public function getNameAttribute(): string
    {
        return $this->getFilamentName();
    }

    /**
     * {@inheritDoc}
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->gt($this->expires_at);
    }

    /**
     * {@inheritDoc}
     */
    public function extend(): void
    {
        $this->update([
            'expires_at' => now()->addMonths(6)->endOfDay(),
        ]);
    }

    public function hasEmailAuthentication(): bool
    {
        return Feature::enabled(Feature::TWO_FACTOR);
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        // Nothing to do
    }

    protected $appends = ['profile_image_url'];

    public function getProfileImageUrlAttribute()
    {
        if (!$this->profile_image) {
            return null;
        }

        return asset('storage/users/' . $this->profile_image);
    }

    public function getHipIdAttribute($value): string
    {
        return $value ?? '';
    }

    public static function formatHipId(?string $id): ?string
    {
        if (!$id) {
            return null;
        }

        // Only accept already-numeric parts.
        if (!ctype_digit((string) $id)) {
            return null;
        }

        // Pad to 5 digits. After HIP99999, it will naturally become HIP100000 (6 digits).
        return 'HIP' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    private static function generateNextHipId(): string
    {
        $max = DB::table('healthinpocket_users')
            ->whereRaw("hip_id REGEXP '^HIP[0-9]+$'")
            ->selectRaw("MAX(CAST(SUBSTRING(hip_id, 4) AS UNSIGNED)) as max_num")
            ->value('max_num');

        $next = ((int) ($max ?? 0)) + 1;

        return self::formatHipId((string) $next) ?? ('HIP' . (string) $next);
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function doctorBookings()
    {
        return $this->hasMany(DoctorBooking::class, 'member_id');
    }

    public function doctorBookingStatuses()
    {
        return $this->hasMany(DoctorBookingStatus::class, 'changed_by');
    }

    public function doctorBookingStatusesNotes()
    {
        return $this->hasMany(DoctorBookingStatus::class, 'notes_by');
    }

    public function caregiverBookings()
    {
        return $this->hasMany(CaregiverBooking::class, 'member_id');
    }

    public function caregiverBookingStatuses()
    {
        return $this->hasMany(CaregiverBookingStatus::class, 'changed_by');
    }

    public function caregiverBookingStatusesNotes()
    {
        return $this->hasMany(CaregiverBookingStatus::class, 'notes_by');
    }

    public function contentLikes()
    {
        return $this->hasMany(ContentLike::class, 'member_id');
    }

    public function contentViews()
    {
        return $this->hasMany(ContentView::class, 'member_id');
    }

    public function contentComments()
    {
        return $this->hasMany(ContentComment::class, 'member_id');
    }

}
