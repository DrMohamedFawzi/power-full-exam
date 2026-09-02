<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Models;

use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\UserDevice;
use App\Modules\Proctoring\Models\Heartbeat;
use App\Modules\Proctoring\Models\KeystrokeSample;
use App\Modules\Proctoring\Models\Violation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'device_id',
        'status',
        'shuffle_seed',
        'score',
        'integrity_index',
        'offline_seconds',
        'started_at',
        'expires_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SessionStatus::class,
            'score' => 'decimal:2',
            'integrity_index' => 'integer',
            'offline_seconds' => 'integer',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'device_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }

    public function heartbeats(): HasMany
    {
        return $this->hasMany(Heartbeat::class);
    }

    public function keystrokeSamples(): HasMany
    {
        return $this->hasMany(KeystrokeSample::class);
    }

    /** @param Builder<ExamSession> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', SessionStatus::Active->value);
    }
}
