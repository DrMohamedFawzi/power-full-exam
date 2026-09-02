<x-layouts.app :title="$exam->title">
    <x-slot:header>
        <x-ui.page-header :title="$exam->title" :breadcrumbs="['الاختبارات' => route('teacher.exams.index'), $exam->title => null]">
            <x-slot:actions>
                <x-ui.badge :color="$exam->status->color()">{{ $exam->status->label() }}</x-ui.badge>
                <a href="{{ route('teacher.exams.show', $exam) }}" class="btn btn-ghost btn-sm">معاينة</a>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <form method="POST" action="{{ route('teacher.exams.update', $exam) }}">
                @csrf
                @method('PUT')

                <x-ui.card title="بيانات الاختبار" icon="cog-6-tooth">
                    @include('assessment.teacher.partials.exam-form', [
                        'exam' => $exam,
                        'classrooms' => $classrooms,
                        'securityLevels' => $securityLevels,
                        'modes' => $modes,
                    ])

                    <x-slot:footer>
                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary btn-sm">حفظ البيانات</button>
                        </div>
                    </x-slot:footer>
                </x-ui.card>
            </form>
        </div>

        <div class="lg:col-span-2">
            @include('assessment.teacher.partials.question-builder', ['exam' => $exam, 'data' => $data])
        </div>
    </div>
</x-layouts.app>
