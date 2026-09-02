<x-layouts.app :title="'الاختبارات'">
    <x-slot:header>
        <x-ui.page-header title="الاختبارات" description="أنشئ اختباراتك وتابع حالتها.">
            <x-slot:actions>
                <a href="{{ route('teacher.exams.create') }}" class="btn btn-primary btn-sm">
                    <x-heroicon-o-plus class="size-4" /> اختبار جديد
                </a>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <x-ui.card>
        @if ($exams->isEmpty())
            <x-ui.empty-state icon="clipboard-document-list" title="لا توجد اختبارات بعد"
                description="أنشئ أول اختبار لصفوفك لتبدأ.">
                <x-slot:action>
                    <a href="{{ route('teacher.exams.create') }}" class="btn btn-primary btn-sm">إنشاء اختبار</a>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>الرمز</th>
                            <th>الصف</th>
                            <th>الحالة</th>
                            <th>الأسئلة</th>
                            <th>المشاركات</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($exams as $exam)
                            <tr>
                                <td class="font-semibold">{{ $exam->title }}</td>
                                <td class="numeric">{{ $exam->code }}</td>
                                <td>{{ $exam->classroom?->name ?? 'بلا صف' }}</td>
                                <td><x-ui.badge :color="$exam->status->color()">{{ $exam->status->label() }}</x-ui.badge></td>
                                <td class="numeric">{{ $exam->questions_count }}</td>
                                <td class="numeric">{{ $exam->sessions_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('teacher.exams.show', $exam) }}" class="btn btn-ghost btn-xs">عرض</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $exams->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.app>
