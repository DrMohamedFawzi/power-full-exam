<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Overwatch\Enums\AttackType;
use App\Support\Enums\Severity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Threat extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'ip_address',
        'user_id',
        'attack_type',
        'severity',
        'request_path',
        'payload',
        'user_agent',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'attack_type' => AttackType::class,
            'severity' => Severity::class,
            'detected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
