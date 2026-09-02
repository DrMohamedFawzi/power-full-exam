<?php

declare(strict_types=1);

namespace App\Modules\Academics\Models;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Identity\Models\Institution;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'teacher_id',
        'institution_id',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function enrollmentRequests(): HasMany
    {
        return $this->hasMany(EnrollmentRequest::class);
    }

    /** Students whose enrollment has been approved. */
    public function students(): HasMany
    {
        return $this->hasMany(EnrollmentRequest::class)
            ->where('status', EnrollmentStatus::Approved->value);
    }

    /** @param Builder<Classroom> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_archived', false);
    }
}
