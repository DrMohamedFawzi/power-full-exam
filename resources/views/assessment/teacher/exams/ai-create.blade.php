<x-layouts.app :title="'توليد أسئلة بالذكاء الاصطناعي'">
    <x-slot:header>
        <x-ui.page-header title="توليد أسئلة بالذكاء الاصطناعي"
            :breadcrumbs="['الاختبارات' => route('teacher.exams.index'), $exam->title => route('teacher.exams.edit', $exam), 'توليد بالذكاء الاصطناعي' => null]" />
    </x-slot:header>

    @unless ($configured)
        <div class="alert alert-warning alert-soft mb-6">
            <x-heroicon-o-exclamation-triangle class="size-5" />
            <span>ميزة توليد الأسئلة بالذكاء الاصطناعي غير مُفعّلة حالياً — لم يتم إعداد مفتاح Gemini API بعد. يمكنك إنشاء الأسئلة يدوياً.</span>
        </div>
    @endunless

    <x-ui.card title="إعدادات التوليد" icon="sparkles">
        <form method="POST" action="{{ route('teacher.exams.ai.generate', $exam) }}" class="flex flex-col gap-5">
            @csrf

            <x-ui.input name="topic" label="الموضوع" hint="مثال: الحرب العالمية الثانية، أساسيات لغة PHP" required />

            <div class="grid gap-5 sm:grid-cols-3">
                <x-ui.input type="number" name="count" label="عدد الأسئلة" value="5" required />

                <x-ui.select name="difficulty" label="مستوى الصعوبة" :options="['easy' => 'سهل', 'medium' => 'متوسط', 'hard' => 'صعب']" selected="medium" required />

                <x-ui.select name="language" label="لغة الأسئلة" :options="['ar' => 'العربية', 'en' => 'الإنجليزية']" selected="ar" required />
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-semibold">أنواع الأسئلة</span></label>
                <div class="flex flex-wrap gap-4">
                    @foreach ($types as $type)
                        <label class="label cursor-pointer gap-2">
                            <input type="checkbox" name="types[]" value="{{ $type->value }}" class="checkbox checkbox-primary" checked>
                            <span class="label-text">{{ $type->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('teacher.exams.edit', $exam) }}" class="btn btn-ghost">إلغاء</a>
                <button type="submit" class="btn btn-accent" @disabled(! $configured)>
                    <x-heroicon-o-sparkles class="size-4" /> توليد الأسئلة
                </button>
            </div>
        </form>
    </x-ui.card>
</x-layouts.app>
