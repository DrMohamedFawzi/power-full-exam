<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Support\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property-read Role $role
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'official_name',
        'role',
        'institution_id',
        'is_approved',
        'qr_token',
        'face_descriptor',
        'photo_status',
        'avatar_path',
        'last_login_ip',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'qr_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'is_approved' => 'boolean',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path) {
            return asset('storage/' . $this->avatar_path);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->official_name ?? $this->username);
    }

    public function isPhotoApproved(): bool
    {
        return $this->photo_status === 'approved';
    }

    public function isStudent(): bool
    {
        return $this->role === Role::Student;
    }

    public function isTeacher(): bool
    {
        return $this->role === Role::Teacher;
    }

    public function isInstitution(): bool
    {
        return $this->role === Role::Institution;
    }

    /** @param Builder<User> $query */
    public function scopeRole(Builder $query, Role $role): void
    {
        $query->where('role', $role->value);
    }

    /** @param Builder<User> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('is_approved', false);
    }
}
