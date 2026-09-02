<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Models;

use App\Modules\Academics\Models\Classroom;
use App\Modules\Assessment\Enums\ExamMode;
use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Enums\SecurityLevel;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'classroom_id',
        'created_by',
        'duration_minutes',
        'security_level',
        'mode',
        'status',
        'shuffle_questions',
        'preserve_time_offline',
        'max_attempts',
        'opens_at',
        'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExamStatus::class,
            'mode' => ExamMode::class,
            'security_level' => SecurityLevel::class,
            'shuffle_questions' => 'boolean',
            'preserve_time_offline' => 'boolean',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    /** @param Builder<Exam> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ExamStatus::Published->value);
    }

    /** @param Builder<Exam> $query */
    public function scopeOpenNow(Builder $query): void
    {
        $query->published()
            ->where(fn (Builder $q) => $q->whereNull('opens_at')->orWhere('opens_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('closes_at')->orWhere('closes_at', '>=', now()));
    }
}
