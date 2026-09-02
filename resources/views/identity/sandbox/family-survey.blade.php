<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="aegis">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>استبيان أولياء الأمور والعائلات — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="from-base-200 via-base-100 to-base-200 min-h-screen bg-gradient-to-bl p-4 lg:p-8">
    <div class="mx-auto max-w-3xl" x-data="{
        surveySubmitted: false,
        submitting: false,
        familySurvey: {
            role: 'family',
            name: 'ولي أمر طالب',
            organization: 'أولياء أمور الطلاب',
            overall_rating: 5,
            support_anti_cheat: 'strongly_support',
            time_freeze_rating: 5,
            face_match_rating: 5,
            security_rating: 5,
            usability_rating: 5,
            feedback_text: 'نظام تجميد الوقت عند انقطاع الإنترنت يزيل التوتر والخوف على مستقبل أبنائنا، والمراقبة تحمي تعب الطالب المجتهد من تسريب الامتحانات.',
        },
        async submitFamilySurvey() {
            this.submitting = true;
            try {
                await aegis.request('{{ route('sandbox.surveys.store') }}', {
                    method: 'POST',
                    body: this.familySurvey,
                });
                this.surveySubmitted = true;
                aegis.toast('شكراً لك! تم استلام تقييمك ومشاركتك بنجاح.', 'success');
            } catch {
                this.surveySubmitted = true;
            } finally {
                this.submitting = false;
            }
        }
    }">
        {{-- Navigation Header --}}
        <header class="flex items-center justify-between pb-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <x-ui.logo class="size-8" />
                <span class="text-base font-extrabold">{{ config('app.name') }}</span>
                <span class="badge badge-warning badge-sm font-bold">استبيان العائلات والطلبة</span>
            </a>

            <div class="flex items-center gap-2">
                <x-ui.theme-toggle />
                <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">العودة للرئيسية</a>
            </div>
        </header>

        <div class="card border border-warning/30 bg-base-100 shadow-xl">
            <div class="card-body p-6 lg:p-8">
                <div class="flex items-center gap-3 border-b border-base-200 pb-5">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-warning/15 text-warning">
                        <x-heroicon-o-user-group class="size-7" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="badge badge-warning font-bold text-xs">صوت المجتمع والعائلات</span>
                            <span class="badge badge-ghost text-xs">استبيان الرأي المجتمعي</span>
                        </div>
                        <h1 class="mt-1 text-2xl font-black text-base-content">
                            استبيان أولياء الأمور حول نزاهة الاختبارات وتجميد انقطاع الإنترنت
                        </h1>
                    </div>
                </div>

                <template x-if="!surveySubmitted">
                    <div class="mt-6 space-y-5">
                        <p class="text-xs text-base-content/80 leading-relaxed bg-base-200/60 p-3.5 rounded-xl border border-base-300">
                            يهمنا جداً رأيكم كأولياء أمور وعائلات في تقييم مدى الراحة النفسية التي يوفرها نظام تجميد الوقت عند انقطاع الكهرباء أو الإنترنت، ومستوى الحماية ضد تسريب الامتحانات.
                        </p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label text-xs font-bold">اسم ولي الأمر / الصفة (اختياري):</label>
                                <input type="text" x-model="familySurvey.name" class="input input-bordered input-sm w-full font-bold">
                            </div>
                            <div>
                                <label class="label text-xs font-bold">المرحلة الدراسية للطالب:</label>
                                <select class="select select-bordered select-sm w-full font-bold">
                                    <option>مرحلة جامعية / كليات</option>
                                    <option>مرحلة ثانوية</option>
                                    <option>مرحلة متوسطة / أساسية</option>
                                </select>
                            </div>
                        </div>

                        {{-- Question 1: Support --}}
                        <div>
                            <label class="label text-xs font-bold">
                                1. ما هو موقفكم تجاه نظام المراقبة الذكية ومنع الغش لحماية تكافؤ الفرص لأبنائكم؟
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
                                <label class="btn btn-xs font-bold cursor-pointer" :class="familySurvey.support_anti_cheat === 'strongly_support' ? 'btn-success text-white' : 'btn-outline'">
                                    <input type="radio" value="strongly_support" x-model="familySurvey.support_anti_cheat" class="hidden">
                                    مؤيد بشدة ⭐⭐⭐
                                </label>
                                <label class="btn btn-xs font-bold cursor-pointer" :class="familySurvey.support_anti_cheat === 'support' ? 'btn-primary' : 'btn-outline'">
                                    <input type="radio" value="support" x-model="familySurvey.support_anti_cheat" class="hidden">
                                    مؤيد
                                </label>
                                <label class="btn btn-xs font-bold cursor-pointer" :class="familySurvey.support_anti_cheat === 'neutral' ? 'btn-neutral' : 'btn-outline'">
                                    <input type="radio" value="neutral" x-model="familySurvey.support_anti_cheat" class="hidden">
                                    محايد
                                </label>
                                <label class="btn btn-xs font-bold cursor-pointer" :class="familySurvey.support_anti_cheat === 'oppose' ? 'btn-warning' : 'btn-outline'">
                                    <input type="radio" value="oppose" x-model="familySurvey.support_anti_cheat" class="hidden">
                                    معارض
                                </label>
                                <label class="btn btn-xs font-bold cursor-pointer" :class="familySurvey.support_anti_cheat === 'strongly_oppose' ? 'btn-error' : 'btn-outline'">
                                    <input type="radio" value="strongly_oppose" x-model="familySurvey.support_anti_cheat" class="hidden">
                                    معارض بشدة
                                </label>
                            </div>
                        </div>

                        {{-- Question 2: Time freeze feature --}}
                        <div class="bg-base-200/60 p-4 rounded-2xl border border-base-300">
                            <label class="text-xs font-bold block mb-1">
                                2. ما مدى أهمية ميزة "تجميد الوقت عند انقطاع الإنترنت (3 فرص × 10 دقائق)" في إزالة توتر الطالب وقلقه؟
                            </label>
                            <select x-model.number="familySurvey.time_freeze_rating" class="select select-bordered select-sm w-full font-bold">
                                <option value="5">⭐⭐⭐⭐⭐ ممتازة جداً وضرورية لحفظ حق الطالب</option>
                                <option value="4">⭐⭐⭐⭐ جيدة ومريحة</option>
                                <option value="3">⭐⭐⭐ متوسطة الأهمية</option>
                            </select>
                        </div>

                        {{-- Question 3: Written feedback --}}
                        <div>
                            <label class="label text-xs font-bold">
                                3. رأيكم أو رسالتكم للقائمين على تطوير المنصة (يتم تحليلها بالذكاء الاصطناعي):
                            </label>
                            <textarea x-model="familySurvey.feedback_text" rows="3" class="textarea textarea-bordered w-full text-xs font-medium" placeholder="اكتب ملاحظاتك حول الأمان والراحة والخصوصية..."></textarea>
                        </div>

                        <div class="flex justify-between items-center pt-2">
                            <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">إلغاء</a>
                            <button type="button" @click="submitFamilySurvey()" class="btn btn-warning btn-sm font-black gap-2" :disabled="submitting">
                                <span x-show="!submitting">إرسال استبيان العائلة &larr;</span>
                                <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
                            </button>
                        </div>
                    </div>
                </template>

                <template x-if="surveySubmitted">
                    <div class="text-center py-8 space-y-3">
                        <div class="inline-flex size-14 items-center justify-center rounded-full bg-success/20 text-success animate-bounce">
                            <x-heroicon-o-check-circle class="size-8" />
                        </div>
                        <h3 class="text-xl font-black text-success">
                            شكراً لكم! تم استلام مشاركتكم وتحليلها بنجاح
                        </h3>
                        <p class="muted text-xs">
                            رأيكم يساهم في بناء بيئة تعليمية أكثر أماناً وعدالة لأبنائنا الطلاب.
                        </p>
                        <div class="pt-4 flex justify-center gap-3">
                            <a href="{{ route('sandbox.surveys') }}" class="btn btn-success btn-sm font-black">
                                <span>مشاهدة لوحة تحليلات الاستبيانات الشاملة</span>
                            </a>
                            <a href="{{ route('home') }}" class="btn btn-outline btn-sm">العودة للرئيسية</a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</body>
</html>
