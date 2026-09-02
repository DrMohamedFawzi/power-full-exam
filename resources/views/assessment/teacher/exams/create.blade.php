<x-layouts.app :title="'اختبار جديد'">
    <x-slot:header>
        <x-ui.page-header title="اختبار جديد" :breadcrumbs="['الاختبارات' => route('teacher.exams.index'), 'جديد' => null]" />
    </x-slot:header>

    <form method="POST" action="{{ route('teacher.exams.store') }}">
        @csrf

        <x-ui.card title="بيانات الاختبار" icon="clipboard-document-list">
            @include('assessment.teacher.partials.exam-form', [
                'exam' => null,
                'classrooms' => $classrooms,
                'securityLevels' => $securityLevels,
                'modes' => $modes,
            ])

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('teacher.exams.index') }}" class="btn btn-ghost">إلغاء</a>
                    <button type="submit" class="btn btn-primary">إنشاء ومتابعة الأسئلة</button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </form>
</x-layouts.app>
