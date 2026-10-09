<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Persons extends Model
{
    use HasUuids;
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'persons';
    protected $fillable = [
        'mrn',
        'first_name',
        'last_name',
        'email',
        'mobile',
        'gender',
        'dob',
        'image',
        'parent_id',
        'hip_user_id',
        'is_primary',
        'relationship',
        'relationship_other',
        'blood_group',
        'emergency_contact_name',
        'emergency_contact_number',
        'address',
        'hospital_id',
        'is_active',
        'deactivated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    public function hipUser()
    {
        return $this->belongsTo(HIPUser::class, 'hip_user_id');
    }

    public function parent()
    {
        return $this->belongsTo(Persons::class, 'parent_id');
    }

    public function members()
    {
        return $this->hasMany(Persons::class, 'parent_id');
    }

    public function creator()
    {
        return $this->belongsTo(HIPUser::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(HIPUser::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameAttribute(): string
    {
        $personName = trim(collect([$this->first_name, $this->last_name])
            ->filter(fn ($part) => filled($part))
            ->implode(' '));

        if ($personName !== '') {
            return $personName;
        }

        $hipUserName = trim(collect([$this->hipUser?->first_name, $this->hipUser?->last_name])
            ->filter(fn ($part) => filled($part))
            ->implode(' '));

        return $hipUserName !== '' ? $hipUserName : 'Unnamed patient';
    }

    public function getInitialsAttribute(): string
    {
        if ($this->display_name === 'Unnamed patient') {
            return '?';
        }

        $parts = preg_split('/\s+/', trim($this->display_name)) ?: [];
        $letters = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');

        return $letters !== '' ? $letters : '?';
    }
}
