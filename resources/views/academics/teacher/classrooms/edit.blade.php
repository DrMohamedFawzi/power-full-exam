<x-layouts.app title="تعديل الصف">
    <x-slot:header>
        <x-ui.page-header
            :title="'تعديل صف: '.$classroom->name"
            description="رمز الانضمام لا يمكن تغييره."
        />
    </x-slot:header>

    <x-ui.card padding="p-5">
        <form method="POST" action="{{ route('teacher.classrooms.update', $classroom) }}" class="max-w-xl space-y-4">
            @csrf
            @method('PUT')

            <div class="form-control w-full">
                <label class="label"><span class="label-text font-semibold">رمز الانضمام</span></label>
                <p class="numeric text-lg font-bold">{{ $classroom->code }}</p>
            </div>

            <x-ui.input name="name" label="اسم الصف" :value="$classroom->name" />
            <x-ui.textarea name="description" label="وصف الصف (اختياري)" :value="$classroom->description" />

            <label class="label cursor-pointer justify-start gap-3">
                <input type="hidden" name="is_archived" value="0">
                <input type="checkbox" name="is_archived" value="1" class="checkbox" @checked(old('is_archived', $classroom->is_archived))>
                <span class="label-text">أرشفة هذا الصف (يخفيه عن الطلاب دون حذف بياناته)</span>
            </label>

            <div class="flex justify-end gap-2">
                <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="btn btn-ghost">إلغاء</a>
                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
            </div>
        </form>
    </x-ui.card>
</x-layouts.app>
