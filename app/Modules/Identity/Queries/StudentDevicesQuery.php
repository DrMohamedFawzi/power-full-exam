<?php

declare(strict_types=1);

namespace App\Modules\Identity\Queries;

use App\Modules\Identity\Enums\DeviceStatus;
use App\Modules\Identity\Models\User;

/**
 * Shapes a student's trusted devices for the devices index view, one row per
 * device with its latest fingerprint summary already resolved.
 */
final class StudentDevicesQuery
{
    /** @return array<int, array<string, mixed>> */
    public function __invoke(User $student): array
    {
        return $student->devices()
            ->with('latestFingerprint')
            ->latest('last_used_at')
            ->get()
            ->map(static fn ($device): array => [
                'id' => $device->id,
                'label' => $device->label,
                'status_label' => $device->status->label(),
                'status_color' => $device->status->color(),
                'is_active' => $device->status === DeviceStatus::Active,
                'last_ip' => $device->last_ip,
                'last_used_at' => $device->last_used_at,
                'fingerprint_summary' => self::summarise($device->latestFingerprint),
            ])
            ->all();
    }

    private static function summarise(mixed $fingerprint): ?string
    {
        if ($fingerprint === null) {
            return null;
        }

        $parts = array_filter([
            $fingerprint->screen_resolution,
            $fingerprint->timezone,
            $fingerprint->webgl_renderer,
        ]);

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
