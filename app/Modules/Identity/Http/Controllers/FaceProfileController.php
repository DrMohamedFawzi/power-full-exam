<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class FaceProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('identity.profile.face', [
            'user' => $request->user(),
        ]);
    }

    public function storeDescriptor(Request $request): JsonResponse
    {
        $request->validate([
            'descriptor' => ['required', 'array', 'min:1'],
            'descriptor.*' => ['numeric'],
            'photo'       => ['nullable', 'string'], // base64 data-URL للصورة الملتقطة
        ]);

        $user = $request->user();
        $updateData = [
            'face_descriptor' => json_encode($request->input('descriptor')),
            'photo_status'    => 'pending',
        ];

        // ─── حفظ الصورة الحقيقية في Storage ────────────────────────────────
        $photoBase64 = $request->input('photo');
        if ($photoBase64 && str_starts_with($photoBase64, 'data:image')) {
            // استخراج بيانات base64 نقية
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $photoBase64);
            $imageData  = base64_decode($base64Data);

            if ($imageData !== false && strlen($imageData) > 100) {
                // حذف الصورة القديمة إن وُجدت
                if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                    Storage::disk('public')->delete($user->avatar_path);
                }

                $path = 'faces/' . $user->id . '_' . time() . '.jpg';
                Storage::disk('public')->put($path, $imageData);
                $updateData['avatar_path'] = $path;
            }
        }

        $user->update($updateData);

        return response()->json([
            'message'      => 'تم حفظ البصمة الرقمية للوجه بنجاح، وهي الآن قيد المراجعة والاعتماد من الإدارة.',
            'photo_status' => 'pending',
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        $user->update(['photo_status' => 'approved']);

        return back()->with('status', 'تم اعتماد بصمة الوجه للطالب بنجاح.');
    }

    public function reject(User $user): RedirectResponse
    {
        $user->update(['photo_status' => 'rejected']);

        return back()->with('status', 'تم رفض بصمة الوجه للطالب.');
    }
}
