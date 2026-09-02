<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="aegis">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>بوابة اختبار النظام (تجربة الطالب) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="from-base-200 via-base-100 to-base-200 min-h-screen bg-gradient-to-bl p-4 lg:p-8">
    <div class="mx-auto max-w-4xl" x-data="{
        faceMatchEnabled: true,
        studentName: 'محمد أحمد (طالب تجريبي)',
        selectedAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
        customPhotoUploaded: false,
        cameraStream: null,
        cameraActive: false,
        matchingStatus: 'بانتظار فتح الكاميرا للمطابقة...',
        isMatched: false,

        toggleCamera() {
            if (this.cameraActive) {
                if (this.cameraStream) {
                    this.cameraStream.getTracks().forEach(t => t.stop());
                }
                this.cameraActive = false;
                this.matchingStatus = 'تم إيقاف الكاميرا';
                return;
            }

            this.matchingStatus = '⏳ جاري فتح الكاميرا ومسح الملامح...';
            navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240 } })
                .then(stream => {
                    this.cameraStream = stream;
                    this.cameraActive = true;
                    this.$nextTick(() => {
                        const v = document.getElementById('gateway-cam-video');
                        if (v) v.srcObject = stream;
                    });
                    setTimeout(() => {
                        this.isMatched = true;
                        this.matchingStatus = '✅ تم التحقق ومطابقة ملامح الوجه بنجاح (100% Match)';
                    }, 2000);
                })
                .catch(() => {
                    this.matchingStatus = '⚠️ الكاميرا غير متاحة، يمكنك المتابعة في وضع المحاكاة';
                    this.isMatched = true;
                });
        },

        handleFileUpload(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    this.selectedAvatar = event.target.result;
                    this.customPhotoUploaded = true;
                    this.isMatched = false;
                    this.matchingStatus = '📷 تم رفع الصورة الرسمية، يرجى مطابقتها بالكاميرا الحية';
                };
                reader.readAsDataURL(file);
            }
        },

        get startUrl() {
            const params = new URLSearchParams({
                face_match: this.faceMatchEnabled ? '1' : '0',
                student_name: this.studentName,
            });
            return '{{ route('sandbox.student.exam') }}?' + params.toString();
        }
    }">
        {{-- Header Navigation --}}
        <header class="flex items-center justify-between pb-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <x-ui.logo class="size-8" />
                <span class="text-base font-extrabold">{{ config('app.name') }}</span>
                <span class="badge badge-primary badge-sm font-bold">بوابة التجربة الحية</span>
            </a>

            <div class="flex items-center gap-2">
                <x-ui.theme-toggle />
                <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">العودة للرئيسية</a>
            </div>
        </header>

        {{-- Main Gateway Card --}}
        <div class="card border border-base-300 bg-base-100 shadow-xl">
            <div class="card-body p-6 lg:p-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-base-200 pb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="badge badge-info font-bold">محاكاة الطالب</span>
                            <span class="badge badge-success font-bold">15 سؤال · 7 دقائق</span>
                            <span class="badge badge-warning font-bold">تجميد الإنترنت 3×10د</span>
                        </div>
                        <h1 class="mt-2 text-2xl font-black lg:text-3xl">
                            تعليمات وبوابة فحص النظام قبل بدء الاختبار
                        </h1>
                        <p class="muted text-xs mt-1">
                            جرّب بنفسك كيف تضمن المنصة نزاهة الاختبارات مع توفير أقصى درجات العدالة وحفظ وقت الطالب.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="text-left">
                            <span class="text-xs text-muted block">مدة الاختبار الحقيقية:</span>
                            <span class="text-lg font-black text-primary numeric">07:00 دقائق</span>
                        </div>
                    </div>
                </div>

                {{-- Section 1: Security Instructions & Guidelines --}}
                <div class="mt-6">
                    <h3 class="text-sm font-extrabold flex items-center gap-2 text-base-content">
                        <x-heroicon-o-shield-check class="size-5 text-primary" />
                        <span>بنود وتعليمات الأمان أثناء جلسة الاختبار:</span>
                    </h3>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl border border-base-200 bg-base-200/50 p-3.5">
                            <div class="flex items-center gap-2 text-xs font-bold text-primary">
                                <x-heroicon-o-lock-closed class="size-4" />
                                <span>حظر التبويبات والخروج</span>
                            </div>
                            <p class="muted mt-1 text-[11px] leading-relaxed">
                                يمنع الخروج من نافذة الامتحان أو التبديل بين البرامج. محاولة الخروج تؤدي لتعتيم الشاشة لمدة 30 ثانية وإنذار فوري.
                            </p>
                        </div>

                        <div class="rounded-xl border border-base-200 bg-base-200/50 p-3.5">
                            <div class="flex items-center gap-2 text-xs font-bold text-warning">
                                <x-heroicon-o-wifi class="size-4" />
                                <span>نظام تجميد انقطاع الإنترنت</span>
                            </div>
                            <p class="muted mt-1 text-[11px] leading-relaxed">
                                عند انقطاع الإنترنت، تتجمد شاشة الامتحان ومؤقت الوقت تلقائياً لحفظ حقك. معك <strong>3 فرص تجميد</strong> حتى 10 دقائق للمرة.
                            </p>
                        </div>

                        <div class="rounded-xl border border-base-200 bg-base-200/50 p-3.5">
                            <div class="flex items-center gap-2 text-xs font-bold text-error">
                                <x-heroicon-o-document-duplicate class="size-4" />
                                <span>حظر النسخ واللصق والتصوير</span>
                            </div>
                            <p class="muted mt-1 text-[11px] leading-relaxed">
                                نصوص الأسئلة محمية ومشفرة على Canvas. محاولة النسخ أو لقطة الشاشة توقف الامتحان وتُظهر شاشة التعتيم الفوري 15 ثانية.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Face Verification Checkbox & Interactive Matcher --}}
                <div class="mt-8 rounded-2xl border-2 border-primary/30 bg-primary/5 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" x-model="faceMatchEnabled" class="checkbox checkbox-primary">
                                <span class="text-sm font-extrabold text-base-content">
                                    🛡️ تمكين المطابقة الحيوية للوجه قبل الدخول (Face Verification)
                                </span>
                            </label>
                            <p class="muted mt-1 text-xs pr-7">
                                يتطلب رفع صورة الطالب والتحقق المباشر عبر الكاميرا للتأكد من هوية الشخص قبل كشف الأسئلة.
                            </p>
                        </div>
                        <span class="badge badge-sm font-bold" :class="faceMatchEnabled ? 'badge-primary' : 'badge-ghost'">
                            <span x-text="faceMatchEnabled ? 'مفعّل' : 'معطّل'"></span>
                        </span>
                    </div>

                    {{-- Face Matching Live Playground --}}
                    <div x-show="faceMatchEnabled" x-transition class="mt-5 border-t border-primary/20 pt-4">
                        <div class="grid gap-6 md:grid-cols-2">
                            {{-- Step A: Official Photo Upload / Selection --}}
                            <div class="flex flex-col justify-between rounded-xl bg-base-100 p-4 border border-base-300">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs font-bold text-base-content">1. الصورة الرسمية المعتمدة:</h4>
                                        <span class="text-[10px] badge badge-ghost">الصورة المرجعية</span>
                                    </div>

                                    <div class="mt-3 flex items-center gap-4">
                                        <div class="size-20 rounded-2xl overflow-hidden border-2 border-primary shadow-sm bg-base-200 shrink-0">
                                            <img :src="selectedAvatar" class="w-full h-full object-cover">
                                        </div>

                                        <div class="flex-1">
                                            <label class="btn btn-outline btn-xs btn-primary gap-1 cursor-pointer">
                                                <x-heroicon-o-arrow-up-tray class="size-3.5" />
                                                <span>رفع صورتك الشخصية</span>
                                                <input type="file" accept="image/*" @change="handleFileUpload($event)" class="hidden">
                                            </label>
                                            <p class="muted text-[10px] mt-1">أو يمكنك استخدام الصورة الافتراضية للتجربة السريعة.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 text-[11px] text-muted font-medium bg-base-200 p-2 rounded-lg">
                                    💡 يتم تخزين بصمة الملامح الرقمية محلياً في متصفحك لضمان أعلى معايير الخصوصية.
                                </div>
                            </div>

                            {{-- Step B: Live Camera Match Check --}}
                            <div class="flex flex-col justify-between rounded-xl bg-base-100 p-4 border border-base-300">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs font-bold text-base-content">2. المطابقة عبر الكاميرا الحية:</h4>
                                        <button type="button" @click="toggleCamera()" class="btn btn-xs" :class="cameraActive ? 'btn-error' : 'btn-primary'">
                                            <span x-text="cameraActive ? 'إيقاف الكاميرا' : 'فتح الكاميرا للمطابقة'"></span>
                                        </button>
                                    </div>

                                    <div class="mt-3 flex items-center gap-4">
                                        <div class="size-20 rounded-2xl overflow-hidden border-2 border-primary bg-black shrink-0 relative flex items-center justify-center">
                                            <template x-if="cameraActive">
                                                <video id="gateway-cam-video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                                            </template>
                                            <template x-if="!cameraActive">
                                                <x-heroicon-o-video-camera class="size-8 text-white/40" />
                                            </template>
                                        </div>

                                        <div class="flex-1">
                                            <div class="badge text-[11px] font-bold py-2 px-3 leading-snug w-full justify-start"
                                                 :class="isMatched ? 'badge-success text-white' : 'badge-warning'">
                                                <span x-text="matchingStatus"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 flex items-center justify-between text-[11px]">
                                    <span class="text-muted">حالة المطابقة:</span>
                                    <span class="font-extrabold" :class="isMatched ? 'text-success' : 'text-warning'" x-text="isMatched ? 'جاهز ومتطابق 100%' : 'بانتظار التحقق'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 3: Student Details & Launch Action --}}
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-base-200 pt-6">
                    <div class="w-full sm:w-72">
                        <label class="label text-xs font-bold text-muted">اسم الطالب في جلسة الاختبار:</label>
                        <input type="text" x-model="studentName" class="input input-bordered input-sm w-full font-bold">
                    </div>

                    <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">إلغاء</a>
                        <a :href="startUrl" 
                           class="btn btn-primary btn-md font-black gap-2 shadow-lg shadow-primary/20"
                           :class="(faceMatchEnabled && !isMatched) ? 'opacity-80' : ''">
                            <x-heroicon-o-play class="size-5" />
                            <span>بدء الامتحان فائق الحماية (15 سؤال / 7 دقائق) &larr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
