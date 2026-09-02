{{-- Expects: $stats (list from ExamResultsQuery) --}}
<x-ui.card title="صعوبة الأسئلة" icon="chart-bar" subtitle="نسبة الإجابات الصحيحة لكل سؤال (الأسئلة المصحَّحة تلقائياً فقط تظهر النسبة).">
    @if (empty($stats))
        <x-ui.empty-state icon="chart-bar" title="لا توجد بيانات كافية بعد" />
    @else
        <div class="flex flex-col gap-3">
            @foreach ($stats as $stat)
                <div>
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="font-semibold">{{ $stat['position'] }}. {{ \Illuminate\Support\Str::limit($stat['prompt'], 80) }}</span>
                        <span class="numeric muted">
                            {{ $stat['correct_percentage'] !== null ? $stat['correct_percentage'].'%' : 'يحتاج تصحيحاً يدوياً' }}
                        </span>
                    </div>
                    @if ($stat['correct_percentage'] !== null)
                        <progress class="progress progress-primary w-full" value="{{ $stat['correct_percentage'] }}" max="100"></progress>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-ui.card>
