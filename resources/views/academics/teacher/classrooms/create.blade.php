<x-layouts.app title="صف جديد">
    <x-slot:header>
        <x-ui.page-header title="إنشاء صف جديد" description="سيحصل الصف تلقائيًا على رمز انضمام فريد يشاركه الطلاب." />
    </x-slot:header>

    <x-ui.card padding="p-5">
        <form method="POST" action="{{ route('teacher.classrooms.store') }}" class="max-w-xl space-y-4">
            @csrf

            <x-ui.input name="name" label="اسم الصف" placeholder="مثال: رياضيات - الصف الثالث" />
            <x-ui.textarea name="description" label="وصف الصف (اختياري)" />

            <div class="flex justify-end gap-2">
                <a href="{{ route('teacher.classrooms.index') }}" class="btn btn-ghost">إلغاء</a>
                <button type="submit" class="btn btn-primary">إنشاء الصف</button>
            </div>
        </form>
    </x-ui.card>
</x-layouts.app>
