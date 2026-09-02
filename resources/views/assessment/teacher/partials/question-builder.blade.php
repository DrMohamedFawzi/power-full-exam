{{-- Alpine-driven question builder. Expects: $exam, $data (from ExamBuilderQuery). --}}
<div
    x-data="questionBuilder({
        examId: {{ $exam->id }},
        questions: {{ Illuminate\Support\Js::from($data['questions']) }},
        locked: {{ $data['locked'] ? 'true' : 'false' }},
        routes: {
            questions: '{{ route('teacher.exams.questions.store', $exam) }}',
            reorder: '{{ route('teacher.exams.questions.reorder', $exam) }}',
        },
    })"
    class="flex flex-col gap-4"
>
    <x-ui.card title="الأسئلة" icon="queue-list" :subtitle="$data['locked'] ? 'الأسئلة غير قابلة للتعديل لوجود محاولات مسجّلة على هذا الاختبار.' : null">
        <x-slot:actions>
            <a href="{{ route('teacher.exams.ai.create', $exam) }}" class="btn btn-outline btn-accent btn-sm">
                <x-heroicon-o-sparkles class="size-4" /> توليد بالذكاء الاصطناعي
            </a>
            <button type="button" class="btn btn-primary btn-sm" @click="openAdd()" x-show="!locked">
                <x-heroicon-o-plus class="size-4" /> سؤال جديد
            </button>
        </x-slot:actions>

        <template x-if="questions.length === 0">
            <x-ui.empty-state icon="queue-list" title="لا توجد أسئلة بعد" description="أضف سؤالك الأول أو استخدم الذكاء الاصطناعي." />
        </template>

        <div class="flex flex-col gap-3">
            <template x-for="(question, index) in questions" :key="question.id">
                <div class="border-base-300 rounded-box flex items-start gap-3 border p-4">
                    <div class="flex flex-col items-center gap-1 pt-1">
                        <span class="numeric bg-base-200 grid size-7 place-items-center rounded-full text-xs font-bold" x-text="question.position"></span>
                        <button type="button" class="btn btn-ghost btn-xs" @click="move(question, -1)" x-show="!locked" aria-label="نقل لأعلى">
                            <x-heroicon-o-chevron-up class="size-4" />
                        </button>
                        <button type="button" class="btn btn-ghost btn-xs" @click="move(question, 1)" x-show="!locked" aria-label="نقل لأسفل">
                            <x-heroicon-o-chevron-down class="size-4" />
                        </button>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="mb-1 flex flex-wrap items-center gap-2">
                            <span class="badge badge-soft badge-sm" x-text="question.type_label"></span>
                            <span class="muted numeric text-xs" x-text="question.points + ' نقطة'"></span>
                        </div>
                        <p class="font-semibold" x-text="question.prompt"></p>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <button type="button" class="btn btn-ghost btn-xs" @click="duplicate(question)" x-show="!locked" aria-label="تكرار السؤال">
                            <x-heroicon-o-document-duplicate class="size-4" />
                        </button>
                        <button type="button" class="btn btn-ghost btn-xs" @click="openEdit(question)" x-show="!locked" aria-label="تعديل السؤال">
                            <x-heroicon-o-pencil-square class="size-4" />
                        </button>
                        <button type="button" class="btn btn-ghost btn-xs text-error" @click="remove(question)" x-show="!locked" aria-label="حذف السؤال">
                            <x-heroicon-o-trash class="size-4" />
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </x-ui.card>

    @include('assessment.teacher.partials.question-form-modal')
</div>
