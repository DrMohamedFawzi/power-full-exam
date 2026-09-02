<x-layouts.app>
    <x-slot:header>
        <x-ui.page-header title="تسجيل الدخول برمز QR" description="امسح هذا الرمز من جهاز آخر لتسجيل الدخول فوراً بحسابك." />
    </x-slot:header>

    <div class="mx-auto max-w-md">
        <x-ui.card title="رمز الدخول السريع" icon="qr-code">
            <div class="flex flex-col items-center gap-4">
                <div class="rounded-box bg-base-100 border-base-300 border p-4">
                    {!! $qrSvg !!}
                </div>

                <p class="muted text-center text-sm">
                    هذا الرمز صالح لمدة <span class="numeric font-semibold">{{ $ttlSeconds }}</span> ثانية فقط،
                    ويُستخدم مرة واحدة. أعد فتح هذه الصفحة للحصول على رمز جديد.
                </p>
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
