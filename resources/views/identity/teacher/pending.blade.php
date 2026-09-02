<x-layouts.guest title="بانتظار الموافقة">
    <x-ui.card title="حسابك بانتظار الموافقة" icon="clock">
        <div class="flex flex-col items-center gap-4 py-4 text-center">
            <span class="bg-warning/10 text-warning grid size-16 place-items-center rounded-2xl">
                <x-heroicon-o-clock class="size-8" />
            </span>

            <p class="muted">
                تم إنشاء حساب المعلّم الخاص بك بنجاح، وهو الآن بانتظار موافقة مدير المؤسسة على تفعيله.
                ستتمكن من الوصول إلى لوحة التحكم فور اعتماد حسابك.
            </p>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">تسجيل الخروج</button>
            </form>
        </div>
    </x-ui.card>
</x-layouts.guest>
