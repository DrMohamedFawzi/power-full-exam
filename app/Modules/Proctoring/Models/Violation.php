<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Models;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Models\User;
use App\Modules\Proctoring\Enums\ViolationType;
use App\Support\Enums\Severity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Violation extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'student_id',
        'type',
        'severity',
        'penalty',
        'details',
        'metadata',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ViolationType::class,
            'severity' => Severity::class,
            'penalty' => 'integer',
            'metadata' => 'array',
            'detected_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @param Builder<Violation> $query */
    public function scopeSeverity(Builder $query, Severity $severity): void
    {
        $query->where('severity', $severity->value);
    }
}
