<x-layouts.app>
    <x-slot:header>
        <x-ui.page-header
            title="إدارة الحظر"
            description="عناوين IP المحظورة يدويًا أو تلقائيًا"
            :breadcrumbs="['مركز الرصد' => route('overwatch.dashboard'), 'الحظر' => null]"
        >
            <x-slot:actions>
                <button type="button" class="btn btn-primary btn-sm" onclick="ban_create_modal.showModal()">
                    <x-heroicon-o-plus class="size-4" /> حظر عنوان جديد
                </button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <x-ui.card title="القائمة" icon="no-symbol" class="animate-in">
        @if ($bans->isEmpty())
            <x-ui.empty-state icon="no-symbol" title="لا توجد عناوين محظورة" description="عندما يتم حظر عنوان IP، سيظهر هنا." />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>عنوان IP</th>
                            <th>السبب</th>
                            <th>الحالة</th>
                            <th>بواسطة</th>
                            <th>تاريخ الحظر</th>
                            <th>ينتهي في</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bans as $ban)
                            <tr>
                                <td class="numeric">{{ $ban['ip_address'] }}</td>
                                <td class="muted text-xs">{{ $ban['reason'] ?? '—' }}</td>
                                <td>
                                    @if ($ban['is_active'])
                                        <x-ui.badge color="error">نشط</x-ui.badge>
                                    @else
                                        <x-ui.badge color="neutral">منتهي</x-ui.badge>
                                    @endif
                                </td>
                                <td class="text-xs">{{ $ban['banned_by_name'] }}</td>
                                <td class="numeric muted text-xs">{{ $ban['banned_at'] }}</td>
                                <td class="numeric muted text-xs">{{ $ban['is_permanent'] ? 'دائم' : $ban['banned_until'] }}</td>
                                <td>
                                    <button type="button" class="btn btn-error btn-soft btn-xs" onclick="unban_modal_{{ $ban['id'] }}.showModal()">
                                        رفع الحظر
                                    </button>

                                    <x-ui.modal :id="'unban_modal_'.$ban['id']" title="تأكيد رفع الحظر">
                                        <p>هل أنت متأكد من رفع الحظر عن العنوان <span class="numeric font-bold">{{ $ban['ip_address'] }}</span>؟</p>

                                        <x-slot:actions>
                                            <form method="POST" action="{{ route('overwatch.bans.destroy', $ban['id']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-error">تأكيد رفع الحظر</button>
                                            </form>
                                        </x-slot:actions>
                                    </x-ui.modal>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $bans->links() }}</div>
        @endif
    </x-ui.card>

    <x-ui.modal id="ban_create_modal" title="حظر عنوان IP جديد">
        <form method="POST" action="{{ route('overwatch.bans.store') }}" id="ban-create-form" class="space-y-4">
            @csrf
            <x-ui.input name="ip_address" label="عنوان IP" hint="مثال: 192.168.1.10" />
            <x-ui.input name="reason" label="السبب (اختياري)" />
            <x-ui.input type="number" name="expires_in_minutes" label="مدة الحظر بالدقائق (اتركه فارغًا للحظر الدائم)" />
        </form>

        <x-slot:actions>
            <button type="submit" form="ban-create-form" class="btn btn-primary">حظر</button>
        </x-slot:actions>
    </x-ui.modal>
</x-layouts.app>
