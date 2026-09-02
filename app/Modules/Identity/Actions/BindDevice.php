<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Data\BindDeviceResult;
use App\Modules\Identity\Data\FingerprintPayload;
use App\Modules\Identity\Enums\DeviceStatus;
use App\Modules\Identity\Models\DeviceFingerprint;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\UserDevice;
use Illuminate\Support\Facades\DB;

/**
 * Find-or-create a user's device by hash, record a fingerprint snapshot, and
 * bump last_used_at/last_ip. Other modules (the exam runner, in particular)
 * call this and use `isNewDevice` to decide whether a violation is due.
 */
final class BindDevice
{
    public function __invoke(
        User $user,
        string $deviceHash,
        FingerprintPayload $fingerprint,
        ?string $label = null,
        ?string $ip = null,
    ): BindDeviceResult {
        return DB::transaction(function () use ($user, $deviceHash, $fingerprint, $label, $ip): BindDeviceResult {
            $device = UserDevice::query()->firstOrNew([
                'user_id' => $user->id,
                'device_hash' => $deviceHash,
            ]);

            $isNewDevice = ! $device->exists;

            if ($isNewDevice) {
                $device->label = $label ?? 'جهاز غير معروف';
                $device->status = DeviceStatus::Active;
            }

            $device->last_ip = $ip;
            $device->last_used_at = now();
            $device->save();

            DeviceFingerprint::create([
                'user_device_id' => $device->id,
                'user_agent' => $fingerprint->userAgent,
                'screen_resolution' => $fingerprint->screenResolution,
                'timezone' => $fingerprint->timezone,
                'canvas_hash' => $fingerprint->canvasHash,
                'webgl_vendor' => $fingerprint->webglVendor,
                'webgl_renderer' => $fingerprint->webglRenderer,
                'is_headless' => $fingerprint->isHeadless,
                'raw' => $fingerprint->raw,
            ]);

            return new BindDeviceResult($device, $isNewDevice);
        });
    }
}
