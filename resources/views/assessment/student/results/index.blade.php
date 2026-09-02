<x-layouts.app>
    <x-slot:header>
        <x-ui.page-header title="نتائجي" description="محاولاتك المكتملة في الاختبارات الرسمية والتدريبية." />
    </x-slot:header>

    <x-ui.card>
        @if (empty($results))
            <x-ui.empty-state icon="chart-bar" title="لا توجد نتائج بعد"
                description="ستظهر نتيجة كل اختبار تُنهيه هنا مباشرة." />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>الاختبار</th>
                            <th>الحالة</th>
                            <th class="text-start">الدرجة</th>
                            <th class="text-start">مؤشر النزاهة</th>
                            <th>تاريخ التسليم</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $result)
                            <tr>
                                <td>{{ $result['exam_title'] }}</td>
                                <td><x-ui.badge :color="$result['status']['color']">{{ $result['status']['label'] }}</x-ui.badge></td>
                                <td class="numeric text-start">{{ $result['score'] ?? '—' }}</td>
                                <td class="numeric text-start">{{ $result['integrity_index'] }}%</td>
                                <td class="numeric">{{ $result['submitted_at_label'] }}</td>
                                <td>
                                    <a href="{{ route('student.results.show', $result['id']) }}" class="btn btn-ghost btn-sm">التفاصيل</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
