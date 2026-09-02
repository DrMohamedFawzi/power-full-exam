<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceFingerprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_device_id',
        'user_agent',
        'screen_resolution',
        'timezone',
        'canvas_hash',
        'webgl_vendor',
        'webgl_renderer',
        'is_headless',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'is_headless' => 'boolean',
            'raw' => 'array',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'user_device_id');
    }
}
