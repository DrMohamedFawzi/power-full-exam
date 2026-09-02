<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Models;

use App\Modules\Assessment\Models\ExamSession;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Typing-rhythm statistics for one window of the session. A sharp change in
 * mean interval or variance suggests the person at the keyboard changed.
 */
class KeystrokeSample extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'exam_session_id',
        'mean_interval_ms',
        'std_deviation_ms',
        'sample_size',
        'anomaly_score',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'mean_interval_ms' => 'decimal:2',
            'std_deviation_ms' => 'decimal:2',
            'sample_size' => 'integer',
            'anomaly_score' => 'decimal:2',
            'captured_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }
}
