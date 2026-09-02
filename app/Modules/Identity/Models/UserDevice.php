<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_hash',
        'label',
        'status',
        'last_ip',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeviceStatus::class,
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fingerprints(): HasMany
    {
        return $this->hasMany(DeviceFingerprint::class);
    }

    public function latestFingerprint(): HasOne
    {
        return $this->hasOne(DeviceFingerprint::class)->latestOfMany();
    }

    /** @param Builder<UserDevice> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', DeviceStatus::Active);
    }
}
