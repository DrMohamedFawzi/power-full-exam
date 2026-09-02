<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\RevokeDevice;
use App\Modules\Identity\Models\UserDevice;
use App\Modules\Identity\Queries\StudentDevicesQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class DeviceController
{
    public function index(Request $request, StudentDevicesQuery $devices): View
    {
        return view('identity.student.devices', [
            'devices' => $devices($request->user()),
        ]);
    }

    public function destroy(UserDevice $device, RevokeDevice $revokeDevice): RedirectResponse
    {
        Gate::authorize('revoke', $device);

        $revokeDevice($device);

        return redirect()->route('student.devices.index')->with('success', 'تم إلغاء تنشيط الجهاز بنجاح.');
    }
}
