<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="aegis">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تجربة إدارة المؤسسة (Sandbox) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="from-base-200 via-base-100 to-base-200 min-h-screen bg-gradient-to-bl p-4 lg:p-8">
    <div class="mx-auto max-w-5xl" x-data="{
        surveySubmitted: false,
        submitting: false,
        institutionSurvey: {
            role: 'institution',
            name: 'عميد القبول والتسجيل / مدير تقنية المعلومات',
            organization: 'جامعة المستقبل للعلوم والتكنولوجيا',
            overall_rating: 5,
            support_anti_cheat: 'strongly_support',
            security_rating: 5,
            usability_rating: 5,
            feedback_text: 'المنصة تقدم حلاً تقنياً متكاملاً يعالج مخاوف انتحال الشخصية وتسريب الأسئلة، وتراعي في الوقت نفسه استقرار الشبكة عبر تجميد الوقت.',
        },
        async submitInstitutionSurvey() {
            this.submitting = true;
            try {
                await aegis.request('{{ route('sandbox.surveys.store') }}', {
                    method: 'POST',
                    body: this.institutionSurvey,
                });
                this.surveySubmitted = true;
                aegis.toast('تم إرسال استبيان المؤسسة بنجاح!', 'success');
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
                <span class="badge badge-accent badge-sm font-bold">بوابة قيادة المؤسسة</span>
            </a>

            <div class="flex items-center gap-2">
                <x-ui.theme-toggle />
                <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">العودة للرئيسية</a>
            </div>
        </header>

        {{-- Intro Banner --}}
        <div class="rounded-3xl border border-accent/30 bg-gradient-to-r from-accent/10 via-base-100 to-base-100 p-6 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="badge badge-accent font-bold">لوحة تحكم المؤسسة التعليمية (Sandbox)</span>
                    <span class="badge badge-ghost text-xs font-semibold">استعراض لصناع القرار</span>
                </div>
                <h1 class="mt-2 text-2xl font-black lg:text-3xl text-base-content">
                    مركز الرصد الأمني والمؤشرات المؤسسية
                </h1>
                <p class="muted text-xs mt-1 max-w-xl">
                    نظرة شاملة على مؤشرات النزاهة وحماية الاختبارات وتقارير رصد التهديدات عبر كليات وأقسام المؤسسة.
                </p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('sandbox.surveys') }}" class="btn btn-sm btn-accent font-black">
                    <x-heroicon-o-chart-bar-square class="size-4" />
                    <span>مركز التحليلات والمشاعر</span>
                </a>
            </div>
        </div>

        {{-- Institutional Key Metrics --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-ui.stat label="مؤشر النزاهة المؤسسي" value="98.4%" icon="shield-check" color="success" />
            <x-ui.stat label="محاولات انتحال محجوبة" value="142" icon="finger-print" color="error" />
            <x-ui.stat label="أحداث تجميد إنترنت ناجحة" value="389" icon="wifi" color="warning" />
            <x-ui.stat label="جلسات اختبار مؤمنة" value="12,450" icon="academic-cap" color="primary" />
        </div>

        {{-- Section: Institution Survey --}}
        <div class="card bg-base-100 border border-base-300 shadow-md">
            <div class="card-body p-6 lg:p-8">
                <template x-if="!surveySubmitted">
                    <div class="space-y-5">
                        <div>
                            <span class="badge badge-accent font-bold text-xs mb-1">استبيان تقييم المؤسسات والعمادات</span>
                            <h2 class="text-xl font-black text-base-content">
                                ما هو تقييمك لملاءمة المنصة للجامعات والمؤسسات التعليمية؟
                            </h2>
                            <p class="muted text-xs mt-1">
                                رأيك كصانع قرار يسهم في تلبية المعايير الأكاديمية والاعتماد المؤسسي.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label text-xs font-bold">اسم المؤسسة أو الجامعة:</label>
                                <input type="text" x-model="institutionSurvey.organization" class="input input-bordered input-sm w-full font-bold">
                            </div>
                            <div>
                                <label class="label text-xs font-bold">المنصب أو الصفة:</label>
                                <input type="text" x-model="institutionSurvey.name" class="input input-bordered input-sm w-full font-bold">
                            </div>
                        </div>

                        <div>
                            <label class="label text-xs font-bold">ما هو رأيك في فكرة مكافحة الغش المدمجة مع تجميد الوقت عند انقطاع الإنترنت؟</label>
                            <select x-model="institutionSurvey.support_anti_cheat" class="select select-bordered select-sm w-full font-bold">
                                <option value="strongly_support">مؤيد بشدة ⭐⭐⭐ (حل نموذجي للاختبارات الرسمية عن بعد)</option>
                                <option value="support">مؤيد</option>
                                <option value="neutral">محايد</option>
                                <option value="oppose">معارض</option>
                            </select>
                        </div>

                        <div>
                            <label class="label text-xs font-bold">رأيك واقتراحاتك المؤسسية:</label>
                            <textarea x-model="institutionSurvey.feedback_text" rows="3" class="textarea textarea-bordered w-full text-xs font-medium"></textarea>
                        </div>

                        <div class="flex justify-between items-center pt-2">
                            <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">إلغاء</a>
                            <button type="button" @click="submitInstitutionSurvey()" class="btn btn-accent btn-sm font-black gap-2" :disabled="submitting">
                                <span x-show="!submitting">إرسال تقييم المؤسسة &larr;</span>
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
                            تم حفظ استبيان المؤسسة بنجاح!
                        </h3>
                        <div class="pt-4 flex justify-center gap-3">
                            <a href="{{ route('sandbox.surveys') }}" class="btn btn-accent btn-sm font-black">
                                <span>الانتقال لمركز تحليلات المشاعر والتقارير</span>
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</body>
</html>
