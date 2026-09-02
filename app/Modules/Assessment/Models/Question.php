<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Models;

use App\Modules\Assessment\Enums\QuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * `correct_answer` never leaves the server before a session is submitted —
 * it is hidden here so an accidental `toArray()` cannot leak it to the runner.
 */
class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'position',
        'type',
        'prompt',
        'options',
        'correct_answer',
        'explanation',
        'points',
    ];

    protected $hidden = [
        'correct_answer',
        'explanation',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'options' => 'array',
            'correct_answer' => 'array',
            'points' => 'decimal:2',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
