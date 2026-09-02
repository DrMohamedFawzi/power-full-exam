{{-- Expects: $sessions (list from ExamResultsQuery) --}}
<x-ui.card title="المشاركات" icon="users">
    @if (empty($sessions))
        <x-ui.empty-state icon="users" title="لا توجد مشاركات بعد" description="ستظهر هنا محاولات الطلاب فور تسليمها." />
    @else
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الحالة</th>
                        <th>الدرجة</th>
                        <th>مؤشر النزاهة</th>
                        <th>المخالفات</th>
                        <th>المدة (دقيقة)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $session)
                        <tr>
                            <td class="font-semibold">{{ $session['student_name'] }}</td>
                            <td><x-ui.badge :color="$session['status']->color()">{{ $session['status']->label() }}</x-ui.badge></td>
                            <td class="numeric">{{ $session['score'] ?? '—' }}</td>
                            <td class="w-40"><x-ui.integrity-meter :value="$session['integrity_index']" :show-label="false" /></td>
                            <td class="numeric">{{ $session['violation_count'] }}</td>
                            <td class="numeric">{{ $session['duration_minutes'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>
