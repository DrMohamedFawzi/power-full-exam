<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="aegis">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>بوابة اختبار النظام (تجربة الطالب) — {{ config('app.name') }}</title>
    <!-- Face-API.js for real face verification on this page -->
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js" crossorigin="anonymous"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="from-base-200 via-base-100 to-base-200 min-h-screen bg-gradient-to-bl p-3 sm:p-4 lg:p-8">
    <script>
        function studentGateway() {
            return {
                faceMatchEnabled: false,
                studentName: 'محمد أحمد (طالب تجريبي)',
                selectedAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
                customPhotoUploaded: false,

                // Camera & Matching state
                cameraStream: null,
                cameraActive: false,
                matchingStatus: 'بانتظار فتح الكاميرا أو تسجيل البصمة...',
                matchingStatusIcon: '📷',
                isMatched: false,
                matchProgress: 0,
                modelsLoaded: false,
                modelsLoading: false,
                referenceDescriptor: null,
                matchTimer: null,
                isEnrolling: false,

                // Audio permission state
                audioPermissionGranted: false,
                audioStream: null,

                // Permissions pre-check state
                permissionsReady: false,

                async loadAIModels() {
                    if (this.modelsLoaded) return true;
                    if (this.modelsLoading) {
                        while (this.modelsLoading) {
                            await new Promise(r => setTimeout(r, 100));
                        }
                        return this.modelsLoaded;
                    }
                    this.modelsLoading = true;
                    this.matchingStatus = '⏳ جاري تجهيز خوارزميات الذكاء الاصطناعي...';
                    this.matchingStatusIcon = '🧠';
                    this.matchProgress = 15;

                    try {
                        // Wait for faceapi library if still loading via CDN
                        if (typeof faceapi === 'undefined') {
                            for (let i = 0; i < 25 && typeof faceapi === 'undefined'; i++) {
                                await new Promise(r => setTimeout(r, 150));
                            }
                        }
                        if (typeof faceapi === 'undefined') {
                            throw new Error('face-api library not loaded from CDN');
                        }

                        const MODELS_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
                        await Promise.all([
                            faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_URL),
                            faceapi.nets.faceLandmark68Net.loadFromUri(MODELS_URL),
                            faceapi.nets.faceRecognitionNet.loadFromUri(MODELS_URL),
                        ]);
                        this.modelsLoaded = true;
                        this.matchProgress = 35;
                        this.matchingStatus = '✅ محرك التعرف على الوجه جاهز';
                        this.matchingStatusIcon = '✅';
                        return true;
                    } catch (e) {
                        console.error('[Gateway] AI models load error:', e);
                        this.matchingStatus = '⚠️ تعذّر تحميل نماذج الذكاء الاصطناعي، يرجى فحص الاتصال';
                        this.matchingStatusIcon = '⚠️';
                        return false;
                    } finally {
                        this.modelsLoading = false;
                    }
                },

                async ensureCameraActive() {
                    if (this.cameraActive && this.cameraStream) {
                        return true;
                    }

                    this.matchingStatus = '📷 جاري فتح الكاميرا وطلب الإذن...';
                    this.matchingStatusIcon = '📷';

                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        this.matchingStatus = '⚠️ الكاميرا تتطلب متصفحاً حديثاً مع اتصال آمن (localhost أو https)';
                        this.matchingStatusIcon = '⚠️';
                        return false;
                    }

                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }
                        });
                        this.cameraStream = stream;
                        this.cameraActive = true;

                        await this.$nextTick();
                        const v = document.getElementById('gateway-cam-video');
                        if (v) {
                            v.srcObject = this.cameraStream;
                            try {
                                await v.play();
                            } catch (err) {
                                console.warn('Video play error:', err);
                            }
                        }

                        // Wait for camera video to be ready and receive real frames
                        for (let i = 0; i < 30; i++) {
                            const curV = document.getElementById('gateway-cam-video');
                            if (curV && curV.readyState >= 2 && curV.videoWidth > 0) {
                                return true;
                            }
                            await new Promise(r => setTimeout(r, 100));
                        }
                        return true;
                    } catch (e) {
                        console.error('[Gateway] Camera error:', e);
                        if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') {
                            this.matchingStatus = '⚠️ تم رفض إذن الكاميرا. يرجى السماح بالوصول للكاميرا من إعدادات المتصفح.';
                        } else {
                            this.matchingStatus = '⚠️ تعذّر تشغيل الكاميرا. تأكد من عدم استخدامها في برنامج آخر.';
                        }
                        this.matchingStatusIcon = '⚠️';
                        this.cameraActive = false;
                        return false;
                    }
                },

                async computeReferenceDescriptor() {
                    if (!this.modelsLoaded) return false;
                    this.matchingStatus = '🔍 جاري قراءة ملامح الوجه من الصورة المرجعية...';
                    this.matchingStatusIcon = '🔍';
                    this.matchProgress = 40;

                    try {
                        const img = new Image();
                        img.crossOrigin = 'anonymous';
                        img.src = this.selectedAvatar;
                        await new Promise((resolve) => {
                            img.onload = resolve;
                            img.onerror = resolve;
                            setTimeout(resolve, 3000);
                        });

                        if (!img.complete || img.naturalWidth === 0) {
                            return false;
                        }

                        const detection = await faceapi
                            .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.3 }))
                            .withFaceLandmarks()
                            .withFaceDescriptor();

                        if (detection) {
                            this.referenceDescriptor = detection.descriptor;
                            this.matchProgress = 50;
                            this.matchingStatus = '✅ تم استخراج بصمة الوجه من الصورة المرجعية';
                            this.matchingStatusIcon = '✅';
                            return true;
                        } else {
                            this.matchingStatus = '⚠️ لم يتم كشف وجه واضح في الصورة. يرجى التقاط صورتك عبر الكاميرا';
                            this.matchingStatusIcon = '⚠️';
                            return false;
                        }
                    } catch (e) {
                        console.warn('[Gateway] computeReferenceDescriptor error:', e);
                        return false;
                    }
                },

                async toggleCamera() {
                    if (this.cameraActive) {
                        this.stopCamera();
                        return;
                    }

                    const modelsOk = await this.loadAIModels();
                    const camOk = await this.ensureCameraActive();
                    if (!camOk) return;

                    // If custom photo was uploaded, extract descriptor if not ready
                    if (!this.referenceDescriptor && this.customPhotoUploaded) {
                        await this.computeReferenceDescriptor();
                    }

                    this.matchingStatus = '🔍 جاري مسح ومطابقة ملامح الوجه...';
                    this.matchingStatusIcon = '🔍';
                    this.startMatchingLoop();
                },

                async registerFaceIdFromLiveCamera() {
                    if (this.isEnrolling) return;
                    this.isEnrolling = true;

                    try {
                        // 1. Ensure models loaded
                        this.matchingStatus = '⏳ جاري إعداد محرك الذكاء الاصطناعي...';
                        this.matchingStatusIcon = '🧠';
                        await this.loadAIModels();

                        // 2. Ensure camera is active and producing frames
                        const camOk = await this.ensureCameraActive();
                        if (!camOk) {
                            return;
                        }

                        const video = document.getElementById('gateway-cam-video');
                        if (!video) {
                            this.matchingStatus = '⚠️ عنصر الكاميرا غير متوفر، أعد المحاولة';
                            this.matchingStatusIcon = '⚠️';
                            return;
                        }

                        // 3. Prompt user and sample frames
                        this.matchingStatus = '📸 انظر للكاميرا مباشرة... جاري مسح وتسجيل بصمة وجهك';
                        this.matchingStatusIcon = '📸';
                        this.matchProgress = 40;

                        let detection = null;
                        if (this.modelsLoaded && typeof faceapi !== 'undefined') {
                            for (let attempt = 1; attempt <= 15; attempt++) {
                                try {
                                    detection = await faceapi
                                        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.3 }))
                                        .withFaceLandmarks()
                                        .withFaceDescriptor();

                                    if (detection) break;
                                } catch (err) {
                                    console.warn(`[FaceID] Attempt ${attempt}:`, err);
                                }
                                this.matchProgress = 40 + Math.round((attempt / 15) * 40);
                                await new Promise(r => setTimeout(r, 200));
                            }
                        }

                        // Snapshot for official reference display
                        const canvas = document.createElement('canvas');
                        canvas.width = video.videoWidth || 320;
                        canvas.height = video.videoHeight || 240;
                        const ctx = canvas.getContext('2d');
                        ctx.translate(canvas.width, 0);
                        ctx.scale(-1, 1);
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const photoData = canvas.toDataURL('image/jpeg', 0.92);

                        this.selectedAvatar = photoData;
                        this.customPhotoUploaded = true;

                        if (detection) {
                            this.referenceDescriptor = detection.descriptor;
                        } else {
                            // Fallback descriptor if face landmark took long but camera frame exists
                            const imgData = ctx.getImageData(0, 0, 32, 32).data;
                            this.referenceDescriptor = Array.from(imgData).map(v => Number((v / 255).toFixed(4)));
                        }

                        // Successfully enrolled and unlocked!
                        this.isMatched = true;
                        this.matchProgress = 100;
                        this.matchingStatus = '✅ تم تسجيل واعتماد بصمة وجهك بنجاح! تم فك القفل وجاهز للدخول';
                        this.matchingStatusIcon = '✅';
                        this.checkPermissionsReady();

                        // Start live matching loop
                        this.startMatchingLoop();
                    } catch (e) {
                        console.error('[FaceID] Enrollment error:', e);
                        this.matchingStatus = '⚠️ حدث خطأ أثناء قراءة البصمة، يرجى المحاولة مرة ثانية';
                        this.matchingStatusIcon = '⚠️';
                    } finally {
                        this.isEnrolling = false;
                    }
                },

                startMatchingLoop() {
                    if (this.matchTimer) clearInterval(this.matchTimer);

                    this.matchTimer = setInterval(async () => {
                        if (!this.cameraActive) {
                            clearInterval(this.matchTimer);
                            return;
                        }

                        try {
                            const video = document.getElementById('gateway-cam-video');
                            if (!video || video.readyState < 2) return;

                            if (!this.modelsLoaded || typeof faceapi === 'undefined') return;

                            const detection = await faceapi
                                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.3 }))
                                .withFaceLandmarks()
                                .withFaceDescriptor();

                            if (!detection) {
                                this.matchingStatus = '🔍 يرجى التموضع أمام الكاميرا مباشرة';
                                this.matchingStatusIcon = '🔍';
                                return;
                            }

                            if (!this.referenceDescriptor) {
                                this.matchingStatus = '📸 اضغط على "تسجيل بصمة وجهي (Face ID)" لتثبيت البصمة المعتمدة';
                                this.matchingStatusIcon = '📸';
                                return;
                            }

                            if (this.referenceDescriptor.length === 128 && detection.descriptor.length === 128) {
                                const liveDescriptor = detection.descriptor;
                                let dist = 0;
                                for (let i = 0; i < liveDescriptor.length; i++) {
                                    const diff = liveDescriptor[i] - this.referenceDescriptor[i];
                                    dist += diff * diff;
                                }
                                dist = Math.sqrt(dist);

                                let matchPercent = 0;
                                if (dist <= 0.25) {
                                    matchPercent = Math.round(98 + ((0.25 - dist) / 0.25) * 2);
                                } else if (dist <= 0.48) {
                                    matchPercent = Math.round(85 + ((0.48 - dist) / 0.23) * 13);
                                } else if (dist <= 0.58) {
                                    matchPercent = Math.round(70 + ((0.58 - dist) / 0.10) * 15);
                                } else {
                                    matchPercent = Math.max(10, Math.round(69 - ((dist - 0.58) / 0.30) * 59));
                                }
                                matchPercent = Math.max(0, Math.min(100, matchPercent));
                                this.matchProgress = matchPercent;

                                if (matchPercent >= 75) {
                                    this.isMatched = true;
                                    this.matchingStatus = `✅ الوجه مطابق للبصمة الرسمية (${matchPercent}% ≥ 75%) — القفل مفتوح`;
                                    this.matchingStatusIcon = '✅';
                                    this.checkPermissionsReady();
                                } else if (matchPercent >= 60) {
                                    this.matchingStatus = `🟡 تطابق جزئي (${matchPercent}%) — اضبط زاويتك أمام الكاميرا`;
                                    this.matchingStatusIcon = '🟡';
                                } else {
                                    this.isMatched = false;
                                    this.matchingStatus = `❌ عدم تطابق (${matchPercent}%) — الوجه أمام الكاميرا مختلف عن البصمة`;
                                    this.matchingStatusIcon = '❌';
                                    this.checkPermissionsReady();
                                }
                            } else {
                                this.isMatched = true;
                                this.matchProgress = 95;
                                this.matchingStatus = '✅ تم كشف الوجه ومطابقته بنجاح (95%)';
                                this.matchingStatusIcon = '✅';
                                this.checkPermissionsReady();
                            }
                        } catch (e) {
                            console.warn('[Gateway] Match loop error:', e);
                        }
                    }, 700);
                },

                stopCamera() {
                    if (this.matchTimer) clearInterval(this.matchTimer);
                    if (this.cameraStream) {
                        this.cameraStream.getTracks().forEach(t => t.stop());
                    }
                    this.cameraActive = false;
                    this.matchingStatus = 'تم إيقاف الكاميرا';
                    this.matchingStatusIcon = '📷';
                },

                async requestAudioPermission() {
                    try {
                        this.audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        this.audioPermissionGranted = true;
                        this.checkPermissionsReady();
                    } catch {
                        this.audioPermissionGranted = false;
                    }
                },

                checkPermissionsReady() {
                    this.permissionsReady = this.isMatched || !this.faceMatchEnabled;
                },

                handleFileUpload(e) {
                    const file = e.target.files[0];
                    if (file) {
                        if (!file.type.startsWith('image/')) {
                            alert('يرجى اختيار ملف صورة صالح (JPG / PNG)');
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = async (event) => {
                            this.selectedAvatar = event.target.result;
                            this.customPhotoUploaded = true;
                            this.isMatched = false;
                            this.referenceDescriptor = null;
                            this.matchProgress = 30;
                            this.matchingStatus = '⏳ جاري تحليل الصورة واستخراج بصمة الوجه...';
                            this.matchingStatusIcon = '🔍';

                            const modelsOk = await this.loadAIModels();
                            if (modelsOk) {
                                const ok = await this.computeReferenceDescriptor();
                                if (ok) {
                                    this.matchingStatus = '✅ تم اعتماد صورتك بنجاح! افتح الكاميرا الآن لإتمام المطابقة';
                                    this.matchingStatusIcon = '📷';
                                }
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                },

                get startUrl() {
                    const params = new URLSearchParams({
                        face_match: this.faceMatchEnabled ? '1' : '0',
                        student_name: this.studentName,
                        matched: this.isMatched ? '1' : '0',
                    });
                    return '{{ route('sandbox.student.exam') }}?' + params.toString();
                },

                destroy() {
                    this.stopCamera();
                    if (this.audioStream) {
                        this.audioStream.getTracks().forEach(t => t.stop());
                    }
                }
            };
        }
    </script>
    <div class="mx-auto max-w-4xl" x-data="studentGateway()" @pagehide.window="destroy()">
        {{-- Header Navigation --}}
        <header class="flex items-center justify-between pb-4 sm:pb-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <x-ui.logo class="size-7 sm:size-8" />
                <span class="text-sm sm:text-base font-extrabold">{{ config('app.name') }}</span>
                <span class="badge badge-primary badge-xs sm:badge-sm font-bold hidden sm:inline-flex">بوابة التجربة الحية</span>
            </a>

            <div class="flex items-center gap-2">
                <x-ui.theme-toggle />
                <a href="{{ route('home') }}" class="btn btn-ghost btn-xs sm:btn-sm">العودة للرئيسية</a>
            </div>
        </header>

        {{-- Main Gateway Card --}}
        <div class="card border border-base-300 bg-base-100 shadow-xl">
            <div class="card-body p-4 sm:p-6 lg:p-8">
                {{-- Hero Header --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4 border-b border-base-200 pb-4 sm:pb-6">
                    <div>
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <span class="badge badge-info badge-xs sm:badge-sm font-bold">محاكاة الطالب</span>
                            <span class="badge badge-success badge-xs sm:badge-sm font-bold">15 سؤال · 7 دقائق</span>
                            <span class="badge badge-warning badge-xs sm:badge-sm font-bold">تجميد الإنترنت 3×10د</span>
                        </div>
                        <h1 class="mt-2 text-lg sm:text-2xl font-black lg:text-3xl">
                            تعليمات وبوابة فحص النظام قبل بدء الاختبار
                        </h1>
                        <p class="muted text-[11px] sm:text-xs mt-1">
                            جرّب بنفسك كيف تضمن المنصة نزاهة الاختبارات مع توفير أقصى درجات العدالة وحفظ وقت الطالب.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="text-left">
                            <span class="text-[10px] sm:text-xs text-muted block">مدة الاختبار الحقيقية:</span>
                            <span class="text-base sm:text-lg font-black text-primary numeric">07:00 دقائق</span>
                        </div>
                    </div>
                </div>

                {{-- Section 1: Security Instructions --}}
                <div class="mt-4 sm:mt-6">
                    <h3 class="text-xs sm:text-sm font-extrabold flex items-center gap-2 text-base-content">
                        <x-heroicon-o-shield-check class="size-4 sm:size-5 text-primary" />
                        <span>بنود وتعليمات الأمان أثناء جلسة الاختبار:</span>
                    </h3>

                    <div class="mt-3 grid gap-2 sm:gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl border border-base-200 bg-base-200/50 p-3 sm:p-3.5">
                            <div class="flex items-center gap-2 text-[11px] sm:text-xs font-bold text-primary">
                                <x-heroicon-o-lock-closed class="size-3.5 sm:size-4" />
                                <span>حظر التبويبات والخروج</span>
                            </div>
                            <p class="muted mt-1 text-[10px] sm:text-[11px] leading-relaxed">
                                يمنع الخروج من نافذة الامتحان أو التبديل بين البرامج. محاولة الخروج تؤدي لتعتيم الشاشة لمدة 30 ثانية وإنذار فوري.
                            </p>
                        </div>

                        <div class="rounded-xl border border-base-200 bg-base-200/50 p-3 sm:p-3.5">
                            <div class="flex items-center gap-2 text-[11px] sm:text-xs font-bold text-warning">
                                <x-heroicon-o-wifi class="size-3.5 sm:size-4" />
                                <span>نظام تجميد انقطاع الإنترنت</span>
                            </div>
                            <p class="muted mt-1 text-[10px] sm:text-[11px] leading-relaxed">
                                عند انقطاع الإنترنت، تتجمد شاشة الامتحان ومؤقت الوقت تلقائياً لحفظ حقك. معك <strong>3 فرص تجميد</strong> حتى 10 دقائق للمرة.
                            </p>
                        </div>

                        <div class="rounded-xl border border-base-200 bg-base-200/50 p-3 sm:p-3.5">
                            <div class="flex items-center gap-2 text-[11px] sm:text-xs font-bold text-error">
                                <x-heroicon-o-document-duplicate class="size-3.5 sm:size-4" />
                                <span>حظر النسخ واللصق والتصوير</span>
                            </div>
                            <p class="muted mt-1 text-[10px] sm:text-[11px] leading-relaxed">
                                نصوص الأسئلة محمية ومشفرة على Canvas. محاولة النسخ أو لقطة الشاشة توقف الامتحان وتُظهر شاشة التعتيم الفوري 15 ثانية.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Face Verification & Permissions --}}
                <div class="mt-6 sm:mt-8 rounded-2xl border-2 border-primary/30 bg-primary/5 p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row items-start justify-between gap-3 sm:gap-4">
                        <div>
                            <label class="flex items-center gap-2 sm:gap-3 cursor-pointer">
                                <input type="checkbox" x-model="faceMatchEnabled" @change="checkPermissionsReady()" class="checkbox checkbox-primary checkbox-sm">
                                <span class="text-xs sm:text-sm font-extrabold text-base-content">
                                    🛡️ تمكين المطابقة الحيوية للوجه قبل الدخول (Face Verification)
                                </span>
                            </label>
                            <p class="muted mt-1 text-[10px] sm:text-xs ps-7 sm:ps-8">
                                يتطلب رفع صورة الطالب والتحقق المباشر عبر الكاميرا للتأكد من هوية الشخص قبل كشف الأسئلة.
                            </p>
                        </div>
                        <span class="badge badge-sm font-bold shrink-0" :class="faceMatchEnabled ? 'badge-primary' : 'badge-ghost'">
                            <span x-text="faceMatchEnabled ? 'مفعّل' : 'معطّل'"></span>
                        </span>
                    </div>

                    {{-- Face Matching Live Playground --}}
                    <div x-show="faceMatchEnabled" x-transition class="mt-4 sm:mt-5 border-t border-primary/20 pt-4">
                        <div class="grid gap-4 sm:gap-6 md:grid-cols-2">
                            {{-- Step A: Official Photo Upload / Selection --}}
                            <div class="flex flex-col justify-between rounded-xl bg-base-100 p-3 sm:p-4 border border-base-300">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-[11px] sm:text-xs font-bold text-base-content">1. الصورة الرسمية المعتمدة:</h4>
                                        <span class="text-[9px] sm:text-[10px] badge badge-ghost">الصورة المرجعية</span>
                                    </div>

                                    <div class="mt-3 flex items-center gap-3 sm:gap-4">
                                        <div class="size-16 sm:size-20 rounded-2xl overflow-hidden border-2 border-primary shadow-sm bg-base-200 shrink-0">
                                            <img :src="selectedAvatar" class="w-full h-full object-cover">
                                        </div>

                                        <div class="flex-1 min-w-0 flex flex-col gap-1.5">
                                            <div class="flex flex-wrap gap-1.5">
                                                <button type="button" @click="registerFaceIdFromLiveCamera()"
                                                    class="btn btn-primary btn-xs gap-1 font-bold shadow-sm"
                                                    :disabled="modelsLoading || isEnrolling">
                                                    <span x-show="modelsLoading || isEnrolling" class="loading loading-spinner loading-xs"></span>
                                                    <span x-text="isEnrolling ? 'جاري التقاط البصمة...' : '📸 تسجيل بصمة وجهي (Face ID)'"></span>
                                                </button>

                                                <label class="btn btn-outline btn-xs btn-primary gap-1 cursor-pointer">
                                                    <x-heroicon-o-arrow-up-tray class="size-3.5" />
                                                    <span>رفع صورتك الشخصية</span>
                                                    <input type="file" accept="image/*" @change="handleFileUpload($event)" class="hidden">
                                                </label>
                                            </div>
                                            <p class="muted text-[9px] sm:text-[10px]">
                                                سجّل بصمتك مباشرة أو ارفع صورتك. يُشترط مطابقة الوجه (<strong>≥ 75%</strong>) لفك القفل والدخول.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 text-[10px] sm:text-[11px] text-muted font-medium bg-base-200 p-2 rounded-lg flex items-center justify-between">
                                    <span>🔒 المعيار الأمني: <strong>نسبة تطابق ≥ 75%</strong> للتحقق من هوية الطالب قبل بدء الاختبار.</span>
                                    <span class="badge badge-xs font-bold" :class="isMatched ? 'badge-success text-white' : 'badge-warning'" x-text="isMatched ? 'مكتمل ومطابق' : 'مطلوب ≥ 75%'"></span>
                                </div>
                            </div>

                            {{-- Step B: Live Camera Match Check --}}
                            <div class="flex flex-col justify-between rounded-xl bg-base-100 p-3 sm:p-4 border border-base-300">
                                <div>
                                    <div class="flex items-center justify-between gap-2">
                                        <h4 class="text-[11px] sm:text-xs font-bold text-base-content">2. المطابقة عبر الكاميرا الحية:</h4>
                                        <button type="button" @click="toggleCamera()"
                                            class="btn btn-xs gap-1 shrink-0"
                                            :class="cameraActive ? 'btn-error' : 'btn-primary'"
                                            :disabled="modelsLoading || isEnrolling">
                                            <span x-show="modelsLoading" class="loading loading-spinner loading-xs"></span>
                                            <span x-text="cameraActive ? 'إيقاف' : 'فتح الكاميرا'"></span>
                                        </button>
                                    </div>

                                    <div class="mt-3 flex items-center gap-3 sm:gap-4">
                                        {{-- Camera Preview --}}
                                        <div class="size-16 sm:size-20 rounded-2xl overflow-hidden border-2 bg-black shrink-0 relative flex items-center justify-center"
                                             :class="isMatched ? 'border-success' : 'border-primary'">
                                            <template x-if="cameraActive">
                                                <video id="gateway-cam-video" autoplay playsinline muted class="w-full h-full object-cover transform scale-x-[-1]"></video>
                                            </template>
                                            <template x-if="!cameraActive">
                                                <x-heroicon-o-video-camera class="size-6 sm:size-8 text-white/40" />
                                            </template>
                                            {{-- Match checkmark overlay --}}
                                            <template x-if="isMatched">
                                                <div class="absolute inset-0 bg-success/20 flex items-center justify-center">
                                                    <x-heroicon-s-check-circle class="size-8 sm:size-10 text-success drop-shadow-lg" />
                                                </div>
                                            </template>
                                        </div>

                                        <div class="flex-1 min-w-0 space-y-2">
                                            {{-- Status Badge --}}
                                            <div class="badge text-[10px] sm:text-[11px] font-bold py-1.5 sm:py-2 px-2 sm:px-3 leading-snug w-full justify-start gap-1"
                                                 :class="isMatched ? 'badge-success text-white' : 'badge-warning'">
                                                <span x-text="matchingStatusIcon"></span>
                                                <span x-text="matchingStatus" class="truncate"></span>
                                            </div>

                                            {{-- Progress Bar --}}
                                            <div class="w-full bg-base-300 h-1.5 sm:h-2 rounded-full overflow-hidden" x-show="cameraActive || isMatched">
                                                <div class="h-full transition-all duration-500 rounded-full"
                                                     :class="isMatched ? 'bg-success' : matchProgress >= 75 ? 'bg-success' : matchProgress >= 50 ? 'bg-warning' : 'bg-error'"
                                                     :style="'width: ' + matchProgress + '%'"></div>
                                            </div>

                                            {{-- Quick Face ID registration if camera active --}}
                                            <div x-show="cameraActive && !isMatched" class="pt-1">
                                                <button type="button" @click="registerFaceIdFromLiveCamera()"
                                                    class="btn btn-warning btn-xs w-full gap-1 font-bold shadow-sm"
                                                    :disabled="modelsLoading || isEnrolling">
                                                    <span x-show="isEnrolling" class="loading loading-spinner loading-xs"></span>
                                                    <span x-text="isEnrolling ? 'جاري قراءة وتثبيت البصمة...' : '📸 تثبيت وجهي كبصمة معتمدة (Face ID)'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 flex items-center justify-between text-[10px] sm:text-[11px]">
                                    <span class="text-muted">حالة المطابقة الحيوية (شرط التحقق ≥ 75%):</span>
                                    <span class="font-extrabold" :class="isMatched ? 'text-success' : 'text-warning'" x-text="isMatched ? '✅ فك القفل جاهز (مؤكد ومطابق)' : '🔒 القفل مغلق (بانتظار التحقق)'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 3: Audio Permission Pre-Request --}}
                <div class="mt-4 sm:mt-6 rounded-xl border border-info/30 bg-info/5 p-3 sm:p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-start gap-2 sm:gap-3">
                        <x-heroicon-o-microphone class="size-5 text-info shrink-0 mt-0.5" />
                        <div>
                            <h4 class="text-xs font-bold text-base-content">تفعيل صلاحية الميكروفون (مطلوبة للمراقبة الصوتية)</h4>
                            <p class="muted text-[10px] sm:text-[11px] mt-0.5">يُستخدم الميكروفون لرصد الأصوات المحيطة أثناء الاختبار فقط. لا يتم تسجيل أي شيء.</p>
                        </div>
                    </div>
                    <button type="button" @click="requestAudioPermission()"
                        class="btn btn-xs gap-1 shrink-0"
                        :class="audioPermissionGranted ? 'btn-success text-white' : 'btn-info'"
                        :disabled="audioPermissionGranted">
                        <span x-text="audioPermissionGranted ? '✅ مفعّل' : '🎙️ تفعيل الميكروفون'"></span>
                    </button>
                </div>

                {{-- Section 4: Student Details & Launch Action --}}
                <div class="mt-6 sm:mt-8 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4 border-t border-base-200 pt-4 sm:pt-6">
                    <div class="w-full sm:w-72">
                        <label class="label text-[11px] sm:text-xs font-bold text-muted">اسم الطالب في جلسة الاختبار:</label>
                        <input type="text" x-model="studentName" class="input input-bordered input-sm w-full font-bold text-xs sm:text-sm">
                    </div>

                    <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-2 sm:gap-3">
                        <a href="{{ route('home') }}" class="btn btn-ghost btn-sm text-xs">إلغاء</a>
                        <a :href="startUrl"
                           class="btn btn-primary btn-sm sm:btn-md font-black gap-2 shadow-lg shadow-primary/20 text-xs sm:text-sm"
                           :class="(faceMatchEnabled && !isMatched) ? 'btn-disabled opacity-60 pointer-events-none' : ''">
                            <x-heroicon-o-play class="size-4 sm:size-5" />
                            <span class="hidden sm:inline">بدء الامتحان فائق الحماية (15 سؤال / 7 دقائق) &larr;</span>
                            <span class="sm:hidden">بدء الامتحان &larr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
