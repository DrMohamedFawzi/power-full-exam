<x-layouts.app :title="'مراجعة الأسئلة المولّدة'">
    <x-slot:header>
        <x-ui.page-header title="مراجعة الأسئلة المولّدة بالذكاء الاصطناعي"
            description="راجع كل سؤال وعدّله أو احذفه قبل إضافته للاختبار. لن يُحفظ أي شيء قبل ضغط زر الحفظ."
            :breadcrumbs="['الاختبارات' => route('teacher.exams.index'), $exam->title => route('teacher.exams.edit', $exam), 'مراجعة الذكاء الاصطناعي' => null]" />
    </x-slot:header>

    <div
        x-data="questionAiPreview({
            storeUrl: '{{ route('teacher.exams.ai.store', $exam) }}',
            redirectUrl: '{{ route('teacher.exams.edit', $exam) }}',
            questions: {{ Illuminate\Support\Js::from($questions) }},
        })"
        class="flex flex-col gap-4"
    >
        <div x-show="error" class="alert alert-error alert-soft text-sm" x-text="error"></div>

        <template x-if="questions.length === 0">
            <x-ui.empty-state icon="sparkles" title="لم يتبقَّ أي سؤال" description="أزلت كل الأسئلة المقترحة. عد وولّد أسئلة جديدة." />
        </template>

        <template x-for="(question, index) in questions" :key="index">
            <div class="border-accent/40 bg-accent/5 rounded-box border p-4">
                <div class="mb-2 flex items-center justify-between">
                    <span class="badge badge-accent badge-soft badge-sm gap-1">
                        <x-heroicon-o-sparkles class="size-3" /> مولّد بالذكاء الاصطناعي
                    </span>
                    <button type="button" class="btn btn-ghost btn-xs text-error" @click="remove(index)" aria-label="حذف السؤال">
                        <x-heroicon-o-trash class="size-4" />
                    </button>
                </div>

                <div class="form-control mb-3">
                    <label class="label"><span class="label-text font-semibold">نص السؤال</span></label>
                    <textarea class="textarea textarea-bordered w-full" rows="2" x-model="question.prompt"></textarea>
                </div>

                <template x-if="question.options && question.options.length">
                    <div class="mb-3 flex flex-col gap-1">
                        <label class="label"><span class="label-text font-semibold">الخيارات</span></label>
                        <template x-for="(option, oIndex) in question.options" :key="oIndex">
                            <input type="text" class="input input-bordered input-sm w-full" x-model="question.options[oIndex]">
                        </template>
                    </div>
                </template>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">الشرح</span></label>
                        <input type="text" class="input input-bordered w-full" x-model="question.explanation">
                    </div>
                    <div class="form-control max-w-32">
                        <label class="label"><span class="label-text font-semibold">الدرجة</span></label>
                        <input type="number" min="0.1" step="0.5" class="input input-bordered w-full" x-model.number="question.points">
                    </div>
                </div>
            </div>
        </template>

        <div class="flex justify-end gap-2">
            <a href="{{ route('teacher.exams.ai.create', $exam) }}" class="btn btn-ghost">توليد من جديد</a>
            <button type="button" class="btn btn-accent" @click="submit()" :disabled="saving || questions.length === 0">
                <span x-show="!saving">إضافة الأسئلة المختارة للاختبار</span>
                <span x-show="saving">جارٍ الحفظ...</span>
            </button>
        </div>
    </div>
</x-layouts.app>
