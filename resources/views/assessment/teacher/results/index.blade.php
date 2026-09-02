<x-layouts.app :title="'التقارير والنتائج'">
    <x-slot:header>
        <x-ui.page-header title="التقارير والنتائج" description="نتائج الاختبارات المنشورة ومؤشرات النزاهة." />
    </x-slot:header>

    <x-ui.card>
        @if ($exams->isEmpty())
            <x-ui.empty-state icon="chart-bar" title="لا توجد نتائج بعد" description="ستظهر هنا نتائج أي اختبار قمت بنشره." />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>الاختبار</th>
                            <th>الحالة</th>
                            <th>عدد المشاركات</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($exams as $exam)
                            <tr>
                                <td class="font-semibold">{{ $exam->title }}</td>
                                <td><x-ui.badge :color="$exam->status->color()">{{ $exam->status->label() }}</x-ui.badge></td>
                                <td class="numeric">{{ $exam->sessions_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('teacher.results.show', $exam) }}" class="btn btn-ghost btn-xs">عرض التفاصيل</a>
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
