<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Overwatch\Actions\BanIp;
use App\Modules\Overwatch\Actions\UnbanIp;
use App\Modules\Overwatch\Http\Requests\StoreBannedIpRequest;
use App\Modules\Overwatch\Models\BannedIp;
use App\Modules\Overwatch\Queries\BansQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class BansController extends Controller
{
    public function index(BansQuery $bans): View
    {
        return view('overwatch.bans.index', ['bans' => $bans()]);
    }

    public function store(StoreBannedIpRequest $request, BanIp $banIp): RedirectResponse
    {
        $banIp(
            ipAddress: $request->string('ip_address')->toString(),
            reason: $request->string('reason')->toString() ?: null,
            minutes: $request->integer('expires_in_minutes') ?: null,
            bannedByUserId: $request->user()?->id,
        );

        return back()->with('success', 'تم حظر عنوان IP بنجاح.');
    }

    public function destroy(BannedIp $ban, UnbanIp $unbanIp): RedirectResponse
    {
        $unbanIp($ban);

        return back()->with('success', 'تم رفع الحظر عن عنوان IP.');
    }
}
