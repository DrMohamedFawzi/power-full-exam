<x-layouts.app>
    <x-slot:header>
        <x-ui.page-header title="الملف الشخصي" description="عدّل بياناتك الشخصية وكلمة المرور والصورة الرمزية." />
    </x-slot:header>

    <div class="mx-auto max-w-2xl">
        <x-ui.card title="البيانات الأساسية" icon="user-circle">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-4">
                    <span class="avatar avatar-placeholder">
                        <span class="bg-primary/10 text-primary size-16 rounded-full">
                            @if ($user->avatar_path)
                                <img src="{{ Storage::url($user->avatar_path) }}" alt="" class="rounded-full object-cover">
                            @else
                                <span class="text-xl font-bold">{{ mb_substr($user->official_name, 0, 1) }}</span>
                            @endif
                        </span>
                    </span>

                    <div class="min-w-0 flex-1">
                        <x-ui.input name="avatar" type="file" label="الصورة الرمزية" hint="JPG أو PNG، بحد أقصى 2 ميجابايت" accept="image/*" />
                    </div>
                </div>

                <x-ui.input name="official_name" label="الاسم الرسمي" icon="identification" :value="$user->official_name" />
                <x-ui.input name="email" type="email" label="البريد الإلكتروني" icon="envelope" :value="$user->email" />

                <div class="divider">تغيير كلمة المرور (اختياري)</div>

                <x-ui.input name="password" type="password" label="كلمة المرور الجديدة" icon="lock-closed" />
                <x-ui.input name="password_confirmation" type="password" label="تأكيد كلمة المرور" icon="lock-closed" />

                <button type="submit" class="btn btn-primary w-full">حفظ التغييرات</button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
