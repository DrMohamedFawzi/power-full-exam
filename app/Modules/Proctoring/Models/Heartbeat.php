<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Models;

use App\Modules\Assessment\Models\ExamSession;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Heartbeat extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'exam_session_id',
        'status',
        'duration_seconds',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'detected_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }
}
