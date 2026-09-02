{{-- Expects: $pendingGrading (list from PendingGradingQuery) --}}
<x-ui.card title="بانتظار التصحيح اليدوي" icon="pencil-square" subtitle="أسئلة الإجابة القصيرة تحتاج تقييمك.">
    @if (empty($pendingGrading))
        <x-ui.empty-state icon="check-circle" title="لا توجد إجابات بانتظار التصحيح" />
    @else
        <div class="flex flex-col gap-4">
            @foreach ($pendingGrading as $item)
                <form method="POST" action="{{ route('teacher.answers.grade', $item['id']) }}"
                      class="border-base-300 rounded-box flex flex-col gap-3 border p-4 sm:flex-row sm:items-end sm:justify-between">
                    @csrf
                    @method('PATCH')

                    <div class="min-w-0 flex-1">
                        <p class="font-semibold">{{ $item['student_name'] }}</p>
                        <p class="muted mt-1 text-sm">{{ $item['prompt'] }}</p>
                        <p class="mt-2 text-sm"><span class="muted">إجابة الطالب:</span> {{ $item['submitted_answer'] ?? '—' }}</p>
                        @if ($item['reference_answer'])
                            <p class="text-sm"><span class="muted">الإجابة النموذجية:</span> {{ $item['reference_answer'] }}</p>
                        @endif
                    </div>

                    <div class="flex items-end gap-2">
                        <x-ui.input type="number" name="points_awarded" label="الدرجة" value="0"
                            hint="من {{ $item['max_points'] }}" step="0.5" min="0" :max="$item['max_points']" class="w-28" />
                        <button type="submit" class="btn btn-primary btn-sm">حفظ الدرجة</button>
                    </div>
                </form>
            @endforeach
        </div>
    @endif
</x-ui.card>
