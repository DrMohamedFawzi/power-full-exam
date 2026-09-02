<x-layouts.app>
    <x-slot:header>
        <x-ui.page-header title="أجهزتي الموثوقة" description="الأجهزة المرتبطة بحسابك. يمكنك إلغاء تنشيط أي جهاز لم تعد تستخدمه." />
    </x-slot:header>

    <x-ui.card title="الأجهزة" icon="device-phone-mobile">
        @if (empty($devices))
            <x-ui.empty-state
                icon="device-phone-mobile"
                title="لا توجد أجهزة مرتبطة بعد"
                description="سيظهر جهازك هنا فور تسجيل الدخول أو أداء أول اختبار عليه."
            />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>الجهاز</th>
                            <th>البصمة</th>
                            <th>آخر عنوان IP</th>
                            <th>آخر استخدام</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($devices as $device)
                            <tr>
                                <td class="font-semibold">{{ $device['label'] }}</td>
                                <td class="muted">{{ $device['fingerprint_summary'] ?? '—' }}</td>
                                <td class="numeric">{{ $device['last_ip'] ?? '—' }}</td>
                                <td class="numeric">{{ $device['last_used_at']?->diffForHumans() ?? '—' }}</td>
                                <td><x-ui.badge :color="$device['status_color']">{{ $device['status_label'] }}</x-ui.badge></td>
                                <td>
                                    @if ($device['is_active'])
                                        <form method="POST" action="{{ route('student.devices.destroy', $device['id']) }}"
                                              onsubmit="return confirm('هل أنت متأكد من إلغاء تنشيط هذا الجهاز؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-error btn-soft btn-sm">إلغاء التنشيط</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
