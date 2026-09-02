<x-layouts.guest title="تسجيل الدخول">
    <x-ui.card title="تسجيل الدخول" subtitle="أدخل بياناتك للوصول إلى حسابك" icon="arrow-left-on-rectangle">
        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
            @csrf

            <x-ui.input name="email" type="email" label="البريد الإلكتروني" icon="envelope" autofocus />
            <x-ui.input name="password" type="password" label="كلمة المرور" icon="lock-closed" />

            <label class="label cursor-pointer justify-start gap-2">
                <input type="checkbox" name="remember" value="1" class="checkbox checkbox-sm">
                <span class="label-text">تذكرني</span>
            </label>

            <button type="submit" class="btn btn-primary w-full">دخول</button>
        </form>

        <x-slot:footer>
            <div class="flex flex-col items-center gap-2 text-sm">
                <span class="muted">ليس لديك حساب؟ <a href="{{ route('register') }}" class="link link-primary font-semibold">أنشئ حساباً</a></span>
            </div>
        </x-slot:footer>
    </x-ui.card>
</x-layouts.guest>
