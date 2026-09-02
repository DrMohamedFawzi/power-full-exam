<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SandboxSurvey extends Model
{
    use HasFactory;

    protected $table = 'sandbox_surveys';

    protected $fillable = [
        'role',
        'name',
        'email',
        'organization',
        'overall_rating',
        'support_anti_cheat',
        'face_match_rating',
        'time_freeze_rating',
        'security_rating',
        'usability_rating',
        'feedback_text',
        'sentiment_score',
        'sentiment_label',
        'detected_keywords',
        'meta',
    ];

    protected $casts = [
        'overall_rating' => 'integer',
        'face_match_rating' => 'integer',
        'time_freeze_rating' => 'integer',
        'security_rating' => 'integer',
        'usability_rating' => 'integer',
        'sentiment_score' => 'float',
        'detected_keywords' => 'array',
        'meta' => 'array',
    ];

    public function isPositive(): bool
    {
        return $this->sentiment_label === 'positive';
    }

    public function isNegative(): bool
    {
        return $this->sentiment_label === 'negative';
    }

    public function isNeutral(): bool
    {
        return $this->sentiment_label === 'neutral';
    }

    public function roleLabelAr(): string
    {
        return match ($this->role) {
            'student' => 'طالب',
            'teacher' => 'معلّم / أستاذ',
            'institution' => 'مسؤول مؤسسة',
            'family' => 'ولي أمر / عائلة',
            default => 'مستخدم',
        };
    }

    public function supportLabelAr(): string
    {
        return match ($this->support_anti_cheat) {
            'strongly_support' => 'مؤيد بشدة',
            'support' => 'مؤيد',
            'neutral' => 'محايد',
            'oppose' => 'معارض',
            'strongly_oppose' => 'معارض بشدة',
            default => 'غير محدد',
        };
    }
}
