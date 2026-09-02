@php
    $roleOptions = [
        'student' => 'طالب',
        'teacher' => 'معلّم',
        'institution' => 'مؤسسة',
    ];
@endphp

<x-layouts.guest title="إنشاء حساب">
    <x-ui.card
        title="إنشاء حساب جديد"
        subtitle="اختر نوع الحساب ثم أكمل بياناتك"
        icon="user-plus"
        x-data="{ role: '{{ old('role', 'student') }}' }"
    >
        <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
            @csrf

            <x-ui.select name="role" label="نوع الحساب" :options="$roleOptions" :selected="old('role', 'student')" x-model="role" />

            <x-ui.input name="official_name" label="الاسم الرسمي" icon="identification" />
            <x-ui.input name="username" label="اسم المستخدم" icon="at-symbol" />
            <x-ui.input name="email" type="email" label="البريد الإلكتروني" icon="envelope" />
            <x-ui.input name="password" type="password" label="كلمة المرور" icon="lock-closed" />
            <x-ui.input name="password_confirmation" type="password" label="تأكيد كلمة المرور" icon="lock-closed" />

            <div x-show="role === 'student' || role === 'teacher'">
                <x-ui.select
                    name="institution_id"
                    label="المؤسسة"
                    :options="$institutions"
                    :selected="old('institution_id')"
                    placeholder="بدون مؤسسة (اختياري للطالب)"
                    hint="إلزامي للمعلّم — تحتاج موافقة المؤسسة قبل تفعيل حسابك"
                />
            </div>

            <div x-show="role === 'institution'" class="flex flex-col gap-4">
                <x-ui.input name="institution_name" label="اسم المؤسسة" icon="building-library" />
                <x-ui.input name="institution_code" label="رمز المؤسسة" icon="hashtag" />
                <x-ui.input name="institution_contact_email" type="email" label="البريد الإلكتروني للتواصل" icon="envelope" />
            </div>

            <button type="submit" class="btn btn-primary w-full">إنشاء الحساب</button>
        </form>

        <x-slot:footer>
            <div class="text-center text-sm">
                <span class="muted">لديك حساب بالفعل؟ <a href="{{ route('login') }}" class="link link-primary font-semibold">سجّل الدخول</a></span>
            </div>
        </x-slot:footer>
    </x-ui.card>
</x-layouts.guest>
