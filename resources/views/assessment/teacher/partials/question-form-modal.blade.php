{{-- Included inside questionBuilder's x-data scope. --}}
<x-ui.modal id="question-modal" x-ref="modal" size="max-w-2xl">
    <x-slot:title>
        <span x-text="isEditing ? 'تعديل سؤال' : 'سؤال جديد'"></span>
    </x-slot:title>

    <div class="flex flex-col gap-4">
        <div x-show="error" class="alert alert-error alert-soft text-sm" x-text="error"></div>

        <div class="form-control w-full">
            <label class="label"><span class="label-text font-semibold">نوع السؤال</span></label>
            <select class="select select-bordered w-full" x-model="form.type" @change="onTypeChange()">
                <option value="multiple_choice">اختيار من متعدد</option>
                <option value="multiple_select">اختيار متعدد الإجابات</option>
                <option value="true_false">صح أو خطأ</option>
                <option value="short_answer">إجابة قصيرة</option>
            </select>
        </div>

        <div class="form-control w-full">
            <label class="label"><span class="label-text font-semibold">نص السؤال</span></label>
            <textarea class="textarea textarea-bordered w-full" rows="3" x-model="form.prompt"></textarea>
        </div>

        {{-- multiple_choice / multiple_select options --}}
        <div x-show="form.type === 'multiple_choice' || form.type === 'multiple_select'" class="flex flex-col gap-2">
            <label class="label"><span class="label-text font-semibold">الخيارات والإجابة الصحيحة</span></label>
            <template x-for="(option, index) in form.options" :key="index">
                <div class="flex items-center gap-2">
                    <input
                        type="radio"
                        class="radio radio-primary"
                        x-show="form.type === 'multiple_choice'"
                        :checked="isChecked(index)"
                        @change="setSingleCorrect(index)"
                    >
                    <input
                        type="checkbox"
                        class="checkbox checkbox-primary"
                        x-show="form.type === 'multiple_select'"
                        :checked="isChecked(index)"
                        @change="toggleMultiSelect(index)"
                    >
                    <input type="text" class="input input-bordered flex-1" x-model="form.options[index]" placeholder="نص الخيار">
                    <button type="button" class="btn btn-ghost btn-xs text-error" @click="removeOption(index)" aria-label="حذف الخيار">
                        <x-heroicon-o-x-mark class="size-4" />
                    </button>
                </div>
            </template>
            <button type="button" class="btn btn-ghost btn-xs self-start" @click="addOption()">
                <x-heroicon-o-plus class="size-4" /> إضافة خيار
            </button>
        </div>

        {{-- true_false --}}
        <div x-show="form.type === 'true_false'" class="flex gap-4">
            <label class="label cursor-pointer gap-2">
                <input type="radio" class="radio radio-primary" :checked="isChecked(0)" @change="setSingleCorrect(0)">
                <span class="label-text">صحيح</span>
            </label>
            <label class="label cursor-pointer gap-2">
                <input type="radio" class="radio radio-primary" :checked="isChecked(1)" @change="setSingleCorrect(1)">
                <span class="label-text">خطأ</span>
            </label>
        </div>

        {{-- short_answer --}}
        <div x-show="form.type === 'short_answer'" class="form-control w-full">
            <label class="label"><span class="label-text font-semibold">إجابة نموذجية (اختياري، للرجوع إليها عند التصحيح اليدوي)</span></label>
            <input type="text" class="input input-bordered w-full" x-model="form.correct_answer[0]">
        </div>

        <div class="form-control w-full">
            <label class="label"><span class="label-text font-semibold">الشرح (اختياري)</span></label>
            <textarea class="textarea textarea-bordered w-full" rows="2" x-model="form.explanation"></textarea>
        </div>

        <div class="form-control w-full max-w-40">
            <label class="label"><span class="label-text font-semibold">الدرجة</span></label>
            <input type="number" min="0.1" step="0.5" class="input input-bordered w-full" x-model.number="form.points">
        </div>
    </div>

    <x-slot:actions>
        <button type="button" class="btn btn-primary" @click="save()" :disabled="saving">
            <span x-show="!saving">حفظ السؤال</span>
            <span x-show="saving">جارٍ الحفظ...</span>
        </button>
    </x-slot:actions>
</x-ui.modal>
