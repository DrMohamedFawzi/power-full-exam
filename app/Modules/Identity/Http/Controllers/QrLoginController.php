<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\ConsumeQrLoginToken;
use App\Modules\Identity\Actions\IssueQrLoginToken;
use App\Modules\Identity\Http\Controllers\Concerns\RedirectsAfterAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

final class QrLoginController
{
    use RedirectsAfterAuthentication;

    public function show(Request $request, IssueQrLoginToken $issueToken): View
    {
        $token = $issueToken($request->user());
        $url = route('qr.login', ['token' => $token]);

        return view('identity.student.qr', [
            'qrSvg' => QrCode::size(260)->generate($url),
            'ttlSeconds' => (int) config('aegis.qr.ttl_seconds'),
        ]);
    }

    public function login(Request $request, ConsumeQrLoginToken $consumeToken): RedirectResponse
    {
        $user = $consumeToken($request, (string) $request->query('token'));

        if ($user === null) {
            return redirect()->route('login')->with('error', 'رمز الاستجابة السريعة غير صالح أو منتهي الصلاحية.');
        }

        return $this->redirectAfterAuthentication($user);
    }
}
