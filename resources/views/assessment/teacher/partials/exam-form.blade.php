{{-- Shared fields for create/edit exam forms. Expects: $exam (nullable), $classrooms, $securityLevels, $modes --}}
<div class="grid gap-5 lg:grid-cols-2">
    <x-ui.input name="title" label="عنوان الاختبار" :value="$exam->title ?? null" required />

    <x-ui.select
        name="classroom_id"
        label="الصف (اختياري)"
        :options="$classrooms"
        :selected="$exam->classroom_id ?? null"
        placeholder="بلا صف محدد"
    />

    <div class="lg:col-span-2">
        <x-ui.textarea name="description" label="الوصف" :value="$exam->description ?? null" hint="يظهر للطالب قبل بدء الاختبار." />
    </div>

    <x-ui.input type="number" name="duration_minutes" label="مدة الاختبار (بالدقائق)" :value="$exam->duration_minutes ?? 60" required />

    <x-ui.input type="number" name="max_attempts" label="عدد المحاولات المسموح بها" :value="$exam->max_attempts ?? 1" required />

    <x-ui.select
        name="security_level"
        label="مستوى المراقبة"
        :options="collect($securityLevels)->mapWithKeys(fn ($level) => [$level->value => $level->label()])"
        :selected="$exam->security_level->value ?? 'strict'"
        required
    />

    <x-ui.select
        name="mode"
        label="نوع الاختبار"
        :options="collect($modes)->mapWithKeys(fn ($mode) => [$mode->value => $mode->label()])"
        :selected="$exam->mode->value ?? 'official'"
        required
    />

    <x-ui.input type="datetime-local" name="opens_at" label="وقت الفتح (اختياري)" :value="optional($exam->opens_at ?? null)->format('Y-m-d\TH:i')" />

    <x-ui.input type="datetime-local" name="closes_at" label="وقت الإغلاق (اختياري)" :value="optional($exam->closes_at ?? null)->format('Y-m-d\TH:i')" />

    <label class="label cursor-pointer justify-start gap-3">
        <input type="hidden" name="shuffle_questions" value="0">
        <input type="checkbox" name="shuffle_questions" value="1" class="checkbox checkbox-primary"
               @checked(old('shuffle_questions', $exam->shuffle_questions ?? true))>
        <span class="label-text font-semibold">ترتيب عشوائي للأسئلة</span>
    </label>

    <label class="label cursor-pointer justify-start gap-3">
        <input type="hidden" name="preserve_time_offline" value="0">
        <input type="checkbox" name="preserve_time_offline" value="1" class="checkbox checkbox-primary"
               @checked(old('preserve_time_offline', $exam->preserve_time_offline ?? false))>
        <span class="label-text font-semibold">الاحتفاظ بالوقت أثناء الانقطاع</span>
    </label>
</div>
