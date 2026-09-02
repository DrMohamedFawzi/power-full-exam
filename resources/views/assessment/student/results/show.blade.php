<x-layouts.app>
    <x-slot:header>
        <x-ui.page-header :title="$result['exam_title']" description="مراجعة إجاباتك بعد التسليم."
            :breadcrumbs="['نتائجي' => route('student.results.index'), $result['exam_title'] => null]" />
    </x-slot:header>

    <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.stat label="الحالة" :value="$result['status']['label']" icon="flag" :color="$result['status']['color']" />
        <x-ui.stat label="الدرجة" :value="$result['score'] ?? '—'" icon="star" color="primary" />
        <x-ui.stat label="مؤشر النزاهة" :value="$result['integrity_index'].'%'" icon="shield-check"
            :color="$result['integrity_index'] >= 85 ? 'success' : ($result['integrity_index'] >= 60 ? 'warning' : 'error')" />
        <x-ui.stat label="عدد المخالفات" :value="$result['violation_count']" icon="exclamation-triangle" color="warning" />
    </div>

    <x-ui.card title="الإجابات">
        @if (empty($result['answers']))
            <x-ui.empty-state icon="document-text" title="لم تتم الإجابة عن أي سؤال" />
        @else
            <div class="flex flex-col gap-4">
                @foreach ($result['answers'] as $answer)
                    <div class="border-base-300 rounded-box border p-4">
                        <div class="flex items-start justify-between gap-4">
                            <p class="font-semibold">{{ $answer['prompt'] }}</p>
                            @if ($answer['needs_manual_grading'])
                                <x-ui.badge color="warning">بانتظار تصحيح المعلّم</x-ui.badge>
                            @elseif ($answer['is_correct'])
                                <x-ui.badge color="success" icon="check">صحيحة</x-ui.badge>
                            @else
                                <x-ui.badge color="error" icon="x-mark">غير صحيحة</x-ui.badge>
                            @endif
                        </div>

                        <p class="muted mt-2 text-sm">إجابتك: <span class="numeric">{{ implode('، ', (array) $answer['given_answer']) ?: '—' }}</span></p>

                        @if (! $answer['needs_manual_grading'] && ! $answer['is_correct'] && $answer['correct_answer'])
                            <p class="text-success mt-1 text-sm">الإجابة الصحيحة: {{ implode('، ', (array) $answer['correct_answer']) }}</p>
                        @endif

                        <p class="muted mt-1 text-xs">
                            <span class="numeric">{{ $answer['points_awarded'] }}</span> / <span class="numeric">{{ $answer['points_possible'] }}</span> نقطة
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
