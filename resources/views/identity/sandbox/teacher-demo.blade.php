<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="aegis">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تجربة المعلم (Sandbox) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="from-base-200 via-base-100 to-base-200 min-h-screen bg-gradient-to-bl p-4 lg:p-8">
    <div class="mx-auto max-w-5xl" x-data="{
        activeTab: 'creator', // 'creator' or 'monitor' or 'survey'
        examTitle: 'اختبار تجريبي في أمن الشبكات والذكاء الاصطناعي',
        securityLevel: 'maximum',
        faceVerification: true,
        offlineFreeze: true,
        aiGenerated: false,
        surveySubmitted: false,
        submitting: false,
        teacherSurvey: {
            role: 'teacher',
            name: 'د. عبد الله المنصور (معلم تجريبي)',
            organization: 'قسم علوم الحاسب',
            overall_rating: 5,
            support_anti_cheat: 'strongly_support',
            security_rating: 5,
            usability_rating: 5,
            time_freeze_rating: 5,
            face_match_rating: 5,
            feedback_text: 'تجربة إنشاء الاختبار الذكي والمراقبة الحية رائعة جداً، تمنحنا كمعلمين ثقة مطلقة في نزاهة التقييم الإلكتروني.',
        },
        generateQuestions() {
            this.aiGenerated = true;
            aegis.toast('تم توليد الأسئلة الذكية وضبط إعدادات الحراسة بنجاح!', 'success');
        },
        async submitTeacherSurvey() {
            this.submitting = true;
            try {
                await aegis.request('{{ route('sandbox.surveys.store') }}', {
                    method: 'POST',
                    body: this.teacherSurvey,
                });
                this.surveySubmitted = true;
                aegis.toast('تم حفظ استبيان المعلم وتحليله بنجاح!', 'success');
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
                <span class="badge badge-secondary badge-sm font-bold">حساب معلّم تجريبي</span>
            </a>

            <div class="flex items-center gap-2">
                <x-ui.theme-toggle />
                <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">العودة للرئيسية</a>
            </div>
        </header>

        {{-- Intro Banner --}}
        <div class="rounded-3xl border border-secondary/30 bg-gradient-to-r from-secondary/10 via-base-100 to-base-100 p-6 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="badge badge-secondary font-bold">بيئة المعلم التجريبية (Sandbox)</span>
                    <span class="badge badge-ghost text-xs font-semibold">حساب وهمي معزول</span>
                </div>
                <h1 class="mt-2 text-2xl font-black lg:text-3xl text-base-content">
                    لوحة إنشاء الامتحانات وإدارة المراقبة الحية
                </h1>
                <p class="muted text-xs mt-1 max-w-xl">
                    استكشف كيف يصمم المعلم اختباراً ذكياً بمستويات حراسة متقدمة، ويتابع جلسات الطلاب لحظة بلحظة لكشف أي محاولات غش.
                </p>
            </div>

            <div class="flex gap-2">
                <button type="button" @click="activeTab = 'creator'" class="btn btn-sm" :class="activeTab === 'creator' ? 'btn-secondary font-bold' : 'btn-outline'">
                    <x-heroicon-o-pencil-square class="size-4" />
                    <span>إنشاء الامتحان</span>
                </button>
                <button type="button" @click="activeTab = 'monitor'" class="btn btn-sm" :class="activeTab === 'monitor' ? 'btn-secondary font-bold' : 'btn-outline'">
                    <x-heroicon-o-chart-bar class="size-4" />
                    <span>مؤشرات النزاهة الرقمية</span>
                </button>
                <button type="button" @click="activeTab = 'survey'" class="btn btn-sm" :class="activeTab === 'survey' ? 'btn-secondary font-bold' : 'btn-outline'">
                    <x-heroicon-o-chat-bubble-left-right class="size-4" />
                    <span>استبيان المعلم</span>
                </button>
            </div>
        </div>

        {{-- Tab 1: Exam Creator Simulation --}}
        <div x-show="activeTab === 'creator'" x-transition class="space-y-6">
            <div class="card bg-base-100 border border-base-300 shadow-md">
                <div class="card-body p-6">
                    <h2 class="card-title text-lg font-black flex items-center gap-2">
                        <x-heroicon-o-adjustments-horizontal class="size-5 text-secondary" />
                        <span>1. إعدادات الاختبار ومستويات الحراسة الذكية</span>
                    </h2>

                    <div class="grid gap-4 sm:grid-cols-2 mt-3">
                        <div>
                            <label class="label text-xs font-bold">عنوان الاختبار:</label>
                            <input type="text" x-model="examTitle" class="input input-bordered input-sm w-full font-bold">
                        </div>

                        <div>
                            <label class="label text-xs font-bold">مستوى الأمان والمراقبة (Security Level):</label>
                            <select x-model="securityLevel" class="select select-bordered select-sm w-full font-bold">
                                <option value="maximum">🔒 الحراسة القصوى (Maximum) — كاميرا + صوت + تجميد + حظر نسخ</option>
                                <option value="proctored">🛡️ حراسة متقدمة (Proctored) — كاميرا + حظر تبويبات</option>
                                <option value="standard">⚡ مراقبة قياسية (Standard)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Security Toggles --}}
                    <div class="mt-4 grid gap-3 sm:grid-cols-3 bg-base-200/60 p-4 rounded-2xl border border-base-300">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="faceVerification" class="checkbox checkbox-secondary checkbox-sm" checked>
                            <span class="text-xs font-bold">إلزام مطابقة الوجه (Face Match)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="offlineFreeze" class="checkbox checkbox-secondary checkbox-sm" checked>
                            <span class="text-xs font-bold">تجميد الوقت عند انقطاع الإنترنت (3x10د)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="checkbox checkbox-secondary checkbox-sm" checked>
                            <span class="text-xs font-bold">حظر النسخ ولصق الأسئلة (Canvas Lock)</span>
                        </label>
                    </div>

                    {{-- AI Quiz Generation Demo --}}
                    <div class="mt-5 border-t border-base-200 pt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div>
                            <h4 class="text-xs font-black">توليد الأسئلة بمساعد الذكاء الاصطناعي (AI Generator)</h4>
                            <p class="muted text-[11px]">يولد بنك أسئلة مشفر ضد روبوتات الذكاء الاصطناعي مع إدراج مصائد أمنية.</p>
                        </div>

                        <button type="button" @click="generateQuestions()" class="btn btn-secondary btn-sm font-black gap-2">
                            <x-heroicon-o-sparkles class="size-4" />
                            <span>توليد الأسئلة وحفظ الاختبار</span>
                        </button>
                    </div>

                    {{-- Generated Preview --}}
                    <div x-show="aiGenerated" x-transition class="mt-4 bg-secondary/5 border border-secondary/20 p-4 rounded-2xl text-xs space-y-2">
                        <div class="flex items-center justify-between font-extrabold text-secondary">
                            <span>✅ تم إنشاء 15 سؤالاً مشفراً بنجاح</span>
                            <span>الدرجة الإجمالية: 15 / المدة: 7 دقائق</span>
                        </div>
                        <p class="text-muted leading-relaxed">
                            تم تفعيل مصيدة التسميم (AI Poisoning Protection) وحظر برامج التقاط الشاشة لجميع الأسئلة.
                        </p>
                        <div class="pt-2 flex gap-2">
                            <button type="button" @click="activeTab = 'monitor'" class="btn btn-xs btn-secondary">
                                الانتقال لشاشة المراقبة الحية &larr;
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 2: Live Proctoring Wall Demo (Privacy-Preserving Telemetry) --}}
        <div x-show="activeTab === 'monitor'" x-transition class="space-y-6">
            <div class="card bg-base-100 border border-base-300 shadow-md">
                <div class="card-body p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-200 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="size-2.5 rounded-full bg-success animate-ping"></span>
                                <h2 class="text-lg font-black">لوحة متابعة مؤشرات النزاهة الرقمية (Live Telemetry Monitor)</h2>
                            </div>
                            <p class="muted text-xs mt-0.5">متابعة لحظية لمؤشرات النزاهة والأحداث المشفرة دون أي انتهاك لخصوصية الطالب</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="badge badge-success font-bold text-xs">4 جلسات نشطة</span>
                            <span class="badge badge-warning font-bold text-xs">1 تجميد إنترنت</span>
                        </div>
                    </div>

                    {{-- Privacy Guarantee Banner --}}
                    <div class="mt-4 rounded-2xl bg-info/10 border border-info/30 p-4 text-xs text-info-content flex items-start gap-3">
                        <x-heroicon-o-shield-check class="size-6 text-info shrink-0 mt-0.5" />
                        <div>
                            <h4 class="font-extrabold text-info">🛡️ نظام حماية الخصوصية الصارم (Zero-Video Transmission):</h4>
                            <p class="mt-1 text-[11px] leading-relaxed text-base-content/80">
                                المنصة لا تبث ولا تعرض أي صور أو كاميرات للطلاب للمعلم احتراماً لخصوصيتهم التامة.
                                تتم معالجة الذكاء الاصطناعي والمطابقة البصرية محلياً بالكامل داخل متصفح الطالب (Client-Side Edge AI)،
                                وتصل للمعلم فقط مؤشرات النزاهة الرقمية والإشارات الإحصائية المشفرة.
                            </p>
                        </div>
                    </div>

                    {{-- 4 Live Student Telemetry Cards (Pure Data - Zero Video) --}}
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        {{-- Student 1: Normal --}}
                        <div class="rounded-2xl border-2 border-success/30 bg-base-100 p-4 shadow-sm relative space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-extrabold text-sm text-base-content">أحمد الشمري</span>
                                        <span class="badge badge-ghost text-[10px] font-mono">ID: #STU-4812</span>
                                    </div>
                                    <p class="text-xs text-muted mt-0.5">
                                        أجاب عن <span class="numeric font-bold text-primary">8</span> من <span class="numeric font-bold">15</span>
                                    </p>
                                </div>
                                <span class="badge badge-success badge-sm font-bold text-white">متصل ومحمي</span>
                            </div>

                            <div class="bg-base-200/60 p-3 rounded-xl space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-bold">
                                    <span class="text-muted">مؤشر النزاهة:</span>
                                    <span class="numeric text-success font-black">98%</span>
                                </div>
                                <div class="w-full bg-base-300 h-2 rounded-full overflow-hidden">
                                    <div class="bg-success h-full" style="width: 98%"></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-muted border-t border-base-200 pt-2 font-medium">
                                <span>بصمة الجهاز: <strong class="font-mono text-base-content">Device-OK #7F2</strong></span>
                                <span class="text-success font-bold">✓ لا توجد مخالفات</span>
                            </div>
                        </div>

                        {{-- Student 2: Violation Alert --}}
                        <div class="rounded-2xl border-2 border-error/40 bg-error/5 p-4 shadow-sm relative space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-extrabold text-sm text-base-content">خالد الدوسري</span>
                                        <span class="badge badge-ghost text-[10px] font-mono">ID: #STU-9920</span>
                                    </div>
                                    <p class="text-xs text-muted mt-0.5">
                                        أجاب عن <span class="numeric font-bold text-primary">5</span> من <span class="numeric font-bold">15</span>
                                    </p>
                                </div>
                                <span class="badge badge-error badge-sm font-bold text-white">إنذار نشط ⚠️</span>
                            </div>

                            <div class="bg-base-100 p-3 rounded-xl border border-error/20 space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-bold">
                                    <span class="text-error font-extrabold">مؤشر النزاهة (انخفض):</span>
                                    <span class="numeric text-error font-black">75%</span>
                                </div>
                                <div class="w-full bg-base-300 h-2 rounded-full overflow-hidden">
                                    <div class="bg-error h-full" style="width: 75%"></div>
                                </div>
                            </div>

                            <div class="bg-error/10 p-2.5 rounded-lg text-[11px] text-error font-bold flex items-center justify-between">
                                <span>🚨 رصد محاولة الخروج من التبويب</span>
                                <span>الإنذارات: 2 / 5</span>
                            </div>
                        </div>

                        {{-- Student 3: Offline Freeze Active --}}
                        <div class="rounded-2xl border-2 border-warning/40 bg-warning/5 p-4 shadow-sm relative space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-extrabold text-sm text-base-content">سارة القحطاني</span>
                                        <span class="badge badge-ghost text-[10px] font-mono">ID: #STU-6310</span>
                                    </div>
                                    <p class="text-xs text-muted mt-0.5">
                                        أجابت عن <span class="numeric font-bold text-primary">11</span> من <span class="numeric font-bold">15</span>
                                    </p>
                                </div>
                                <span class="badge badge-warning badge-sm font-bold">تجميد إنترنت 🔒</span>
                            </div>

                            <div class="bg-base-100 p-3 rounded-xl border border-warning/20 space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-bold">
                                    <span class="text-warning font-extrabold">حالة الوقت: مجمد بأمان</span>
                                    <span class="numeric text-warning font-black">متبقي: 08:45د</span>
                                </div>
                                <div class="w-full bg-base-300 h-2 rounded-full overflow-hidden">
                                    <div class="bg-warning h-full" style="width: 90%"></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-warning font-bold border-t border-warning/20 pt-2">
                                <span>الشاشة مقفلة لدى الطالبة</span>
                                <span>الفرصة: 1 من 3</span>
                            </div>
                        </div>

                        {{-- Student 4: Normal --}}
                        <div class="rounded-2xl border-2 border-success/30 bg-base-100 p-4 shadow-sm relative space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-extrabold text-sm text-base-content">فاطمة الحربي</span>
                                        <span class="badge badge-ghost text-[10px] font-mono">ID: #STU-2189</span>
                                    </div>
                                    <p class="text-xs text-muted mt-0.5">
                                        أجابت عن <span class="numeric font-bold text-primary">14</span> من <span class="numeric font-bold">15</span>
                                    </p>
                                </div>
                                <span class="badge badge-success badge-sm font-bold text-white">متصل ومحمي</span>
                            </div>

                            <div class="bg-base-200/60 p-3 rounded-xl space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-bold">
                                    <span class="text-muted">مؤشر النزاهة:</span>
                                    <span class="numeric text-success font-black">100%</span>
                                </div>
                                <div class="w-full bg-base-300 h-2 rounded-full overflow-hidden">
                                    <div class="bg-success h-full" style="width: 100%"></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-muted border-t border-base-200 pt-2 font-medium">
                                <span>بصمة الجهاز: <strong class="font-mono text-base-content">Device-OK #4B1</strong></span>
                                <span class="text-success font-bold">✓ لا توجد مخالفات</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-base-200 flex justify-end">
                        <button type="button" @click="activeTab = 'survey'" class="btn btn-secondary btn-sm font-black gap-2">
                            <span>تعبئة استبيان المعلم ومشاركة الملاحظات &larr;</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 3: Teacher Survey --}}
        <div x-show="activeTab === 'survey'" x-transition class="space-y-6">
            <div class="card bg-base-100 border border-base-300 shadow-md">
                <div class="card-body p-6 lg:p-8">
                    <template x-if="!surveySubmitted">
                        <div class="space-y-5">
                            <div>
                                <span class="badge badge-secondary font-bold text-xs mb-1">استبيان تقييم المعلّمين</span>
                                <h2 class="text-xl font-black text-base-content">
                                    ما هو رأيك كمعلم وأكاديمي في أدوات الحماية والمراقبة؟
                                </h2>
                                <p class="muted text-xs mt-1">
                                    ملاحظاتك تساعدنا في تطوير وتخصيص تجربة المعلمين ومصداقية نتائج الطلاب.
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="label text-xs font-bold">اسم المعلّم / الأستاذ:</label>
                                    <input type="text" x-model="teacherSurvey.name" class="input input-bordered input-sm w-full font-bold">
                                </div>
                                <div>
                                    <label class="label text-xs font-bold">القسم أو المدرسة / الجامعة:</label>
                                    <input type="text" x-model="teacherSurvey.organization" class="input input-bordered input-sm w-full font-bold">
                                </div>
                            </div>

                            <div>
                                <label class="label text-xs font-bold">ما مدى تأييدك لتطبيق نظام منع الغش هذا في مؤسستك التعليمية؟</label>
                                <select x-model="teacherSurvey.support_anti_cheat" class="select select-bordered select-sm w-full font-bold">
                                    <option value="strongly_support">مؤيد بشدة ⭐⭐⭐ (ضروري جداً لضمان مصداقية النتائج)</option>
                                    <option value="support">مؤيد</option>
                                    <option value="neutral">محايد</option>
                                    <option value="oppose">معارض</option>
                                    <option value="strongly_oppose">معارض بشدة</option>
                                </select>
                            </div>

                            <div>
                                <label class="label text-xs font-bold">ملاحظاتك واقتراحاتك حول المنصة (يتم تحليلها بمحرك المشاعر):</label>
                                <textarea x-model="teacherSurvey.feedback_text" rows="4" class="textarea textarea-bordered w-full text-xs font-medium" placeholder="اكتب رأيك حول تجربة المعلم وسهولة المراقبة..."></textarea>
                            </div>

                            <div class="flex justify-between items-center pt-2">
                                <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">إلغاء</a>
                                <button type="button" @click="submitTeacherSurvey()" class="btn btn-secondary btn-sm font-black gap-2" :disabled="submitting">
                                    <span x-show="!submitting">إرسال استبيان المعلم &larr;</span>
                                    <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="surveySubmitted">
                        <div class="text-center py-8 space-y-4">
                            <div class="inline-flex size-14 items-center justify-center rounded-full bg-success/20 text-success animate-bounce">
                                <x-heroicon-o-check-circle class="size-8" />
                            </div>
                            <h3 class="text-xl font-black text-success">
                                شكراً لك دكتور/أستاذ! تم استلام استبيانك وتحليله بنجاح
                            </h3>
                            <p class="muted text-xs max-w-md mx-auto">
                                تم تضمين رأيك وتقييمك في لوحة تحليلات الاستبيانات العامة للمنصة.
                            </p>
                            <div class="pt-4 flex justify-center gap-3">
                                <a href="{{ route('sandbox.surveys') }}" class="btn btn-secondary btn-sm font-black">
                                    <span>مشاهدة لوحة تحليلات الاستبيانات والمشاعر</span>
                                </a>
                                <a href="{{ route('home') }}" class="btn btn-outline btn-sm">العودة للرئيسية</a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
