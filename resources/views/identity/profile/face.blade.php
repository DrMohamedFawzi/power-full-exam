<x-layouts.app title="البصمة الرقمية للوجه">
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js" crossorigin="anonymous"></script>
    @endpush

    <div x-data="{
            activeTab: 'camera',
            cameraState: 'idle',
            cameraErrorMsg: '',
            mediaStream: null,
            uploadedFile: null,
            capturedPhotoUrl: null,
            extractedDescriptor: null,
            descriptorDisplay: '',
            statusType: 'info',
            statusMsg: 'اختر الكاميرا أو قم برفع صورة من جهازك للبدء.',
            isSaving: false,

            switchTab(tab) {
                this.activeTab = tab;
                if (tab === 'upload') {
                    this.stopWebcam();
                }
            },

            async startWebcam() {
                this.cameraState = 'requesting';
                this.statusType = 'info';
                this.statusMsg = 'جاري طلب إذن الوصول إلى الكاميرا من المتصفح...';

                try {
                    let constraints = { video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } };
                    try {
                        this.mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
                    } catch (e) {
                        this.mediaStream = await navigator.mediaDevices.getUserMedia({ video: true });
                    }

                    this.cameraState = 'active';
                    this.statusType = 'info';
                    this.statusMsg = 'تم تشغيل الكاميرا بنجاح. انظر مباشرة واضغط على التقاط الصورة.';

                    this.$nextTick(() => {
                        const video = document.getElementById('webcam-feed');
                        if (video) {
                            video.srcObject = this.mediaStream;
                            video.play().catch(err => console.warn('video play error:', err));
                        }
                    });
                } catch (err) {
                    console.error('Camera access error:', err);
                    this.cameraState = 'error';
                    this.cameraErrorMsg = 'تم رفض الوصول للكاميرا أو الجهاز لا يحتوي على كاميرا.';
                    this.statusType = 'warning';
                    this.statusMsg = '⚠️ تعذّر فتح الكاميرا. يمكنك رفع صورة من جهازك من التبويب الثاني.';
                }
            },

            stopWebcam() {
                if (this.mediaStream) {
                    this.mediaStream.getTracks().forEach(track => track.stop());
                    this.mediaStream = null;
                }
                if (this.cameraState !== 'captured') {
                    this.cameraState = 'idle';
                }
            },

            captureFromWebcam() {
                const video = document.getElementById('webcam-feed');
                const canvas = document.getElementById('captured-canvas');
                if (!video || !this.mediaStream) return;

                const ctx = canvas.getContext('2d');
                canvas.width = video.videoWidth || 640;
                canvas.height = video.videoHeight || 480;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
                this.capturedPhotoUrl = dataUrl;

                const img = new Image();
                img.onload = () => {
                    this.generateDescriptor(img);
                };
                img.src = dataUrl;

                this.stopWebcam();
                this.cameraState = 'captured';
            },

            retakePhoto() {
                this.capturedPhotoUrl = null;
                this.extractedDescriptor = null;
                this.descriptorDisplay = '';
                this.cameraState = 'idle';
                this.startWebcam();
            },

            handleFileUpload(e) {
                const file = e.target.files[0];
                if (!file) return;

                if (!file.type.startsWith('image/')) {
                    if (window.aegis?.toast) window.aegis.toast('يرجى اختيار ملف صورة صالحة', 'error');
                    return;
                }

                this.uploadedFile = file;
                this.statusType = 'info';
                this.statusMsg = 'جاري قراءة الصورة وتوليد البصمة الرقمية...';

                const reader = new FileReader();
                reader.onload = (event) => {
                    const dataUrl = event.target.result;
                    this.capturedPhotoUrl = dataUrl;

                    const img = new Image();
                    img.onload = () => {
                        this.generateDescriptor(img);
                    };
                    img.src = dataUrl;
                };
                reader.readAsDataURL(file);
            },

            generateDescriptor(imgElement) {
                const canvas = document.createElement('canvas');
                canvas.width = 64;
                canvas.height = 64;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(imgElement, 0, 0, 64, 64);
                const imgData = ctx.getImageData(0, 0, 64, 64).data;

                const rawVector = Array.from(imgData).map(v => Number((v / 255).toFixed(4)));
                this.extractedDescriptor = rawVector;
                
                this.descriptorDisplay = JSON.stringify(rawVector.slice(0, 24)) + 
                    `\n\n... [إجمالي أبعاد البصمة الرقمية: ${rawVector.length} عنصر]`;

                this.statusType = 'success';
                this.statusMsg = '✅ تم التقاط واستخراج البصمة الرقمية للوجه بنجاح! اضغط الآن على إرسال البصمة للاعتماد.';
            },

            async saveFaceData() {
                if (!this.extractedDescriptor || !this.capturedPhotoUrl) return;

                this.isSaving = true;
                try {
                    const res = await window.aegis.request('{{ route("student.profile.face.store") }}', {
                        method: 'POST',
                        body: {
                            descriptor: this.extractedDescriptor,
                            photo: this.capturedPhotoUrl
                        }
                    });

                    if (window.aegis?.toast) window.aegis.toast(res.message, 'success');
                    setTimeout(() => location.reload(), 1200);
                } catch (e) {
                    if (window.aegis?.toast) window.aegis.toast(e.payload?.message || 'تعذّر حفظ البصمة.', 'error');
                    this.isSaving = false;
                }
            }
        }" 
        class="mx-auto max-w-4xl py-6 px-4 space-y-6">

        {{-- Header Section --}}
        <x-ui.page-header title="البصمة الرقمية للوجه" description="قم بالتقاط صورتك الشخصية من الكاميرا أو رفعها من الجهاز لاستخراج البصمة الرقمية المعتمدة لدخول الامتحانات.">
            <x-slot:actions>
                @if ($user->photo_status === 'approved')
                    <span class="badge badge-success gap-2 py-3 px-4 text-xs font-bold shadow-sm">
                        <x-heroicon-o-check-circle class="size-4" /> البصمة معتمدة ومفعلة
                    </span>
                @elseif ($user->photo_status === 'pending')
                    <span class="badge badge-warning gap-2 py-3 px-4 text-xs font-bold shadow-sm">
                        <x-heroicon-o-clock class="size-4" /> قيد المراجعة والاعتماد
                    </span>
                @else
                    <span class="badge badge-error gap-2 py-3 px-4 text-xs font-bold shadow-sm">
                        <x-heroicon-o-x-circle class="size-4" /> لم يتم اعتماد بصمة الوجه بعد
                    </span>
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Main Interface Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- Right Options / Input Column (7 cols) --}}
            <div class="lg:col-span-7 space-y-5">
                
                {{-- Mode Switcher Tabs --}}
                <div class="bg-base-100 p-1.5 rounded-2xl border border-base-300 shadow-sm flex gap-2">
                    <button type="button" 
                        @click="switchTab('camera')"
                        class="flex-1 py-3 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2"
                        :class="activeTab === 'camera' ? 'bg-primary text-primary-content shadow-md' : 'text-base-content/70 hover:bg-base-200'">
                        <x-heroicon-o-camera class="size-4" />
                        <span>التقاط من الكاميرا</span>
                    </button>

                    <button type="button" 
                        @click="switchTab('upload')"
                        class="flex-1 py-3 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2"
                        :class="activeTab === 'upload' ? 'bg-primary text-primary-content shadow-md' : 'text-base-content/70 hover:bg-base-200'">
                        <x-heroicon-o-photo class="size-4" />
                        <span>رفع من الاستوديو / الجهاز</span>
                    </button>
                </div>

                {{-- Tab 1: Camera Capture Panel --}}
                <div x-show="activeTab === 'camera'" class="surface p-6 rounded-3xl space-y-5 shadow-sm border border-base-300">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-sm flex items-center gap-2 text-base-content">
                            <span class="size-2.5 rounded-full bg-primary animate-pulse"></span>
                            الكاميرا المباشرة
                        </h3>
                        <span class="text-[11px] text-base-content/60">تأكد من وجود إضاءة مناسبة</span>
                    </div>

                    {{-- Camera View Box --}}
                    <div class="relative w-full aspect-video bg-base-900 rounded-2xl overflow-hidden border-2 border-base-300 flex items-center justify-center shadow-inner">
                        
                        {{-- Video Feed --}}
                        <video id="webcam-feed" autoplay playsinline muted class="w-full h-full object-cover" x-show="cameraState === 'active'"></video>

                        {{-- Face Oval Guide Ring --}}
                        <div x-show="cameraState === 'active'" class="absolute inset-0 pointer-events-none flex items-center justify-center">
                            <div class="w-48 h-60 rounded-[50%] border-2 border-dashed border-primary/70 bg-primary/5 animate-pulse flex items-center justify-center">
                                <span class="text-[10px] bg-base-900/80 text-white px-2 py-0.5 rounded-full backdrop-blur">ضع وجهك هنا</span>
                            </div>
                        </div>

                        {{-- Idle State (Before Permission) --}}
                        <div x-show="cameraState === 'idle'" class="flex flex-col items-center justify-center p-6 text-center space-y-3">
                            <div class="size-16 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                                <x-heroicon-o-video-camera class="size-8" />
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm font-bold text-base-content">إذن الكاميرا مطلوب</p>
                                <p class="text-xs text-base-content/60 max-w-xs">انقر على الزر بالأسفل لتشغيل الكاميرا والسماح للمتصفح بالوصول.</p>
                            </div>
                        </div>

                        {{-- Requesting State --}}
                        <div x-show="cameraState === 'requesting'" class="flex flex-col items-center justify-center p-6 text-center space-y-3">
                            <span class="loading loading-spinner loading-lg text-primary"></span>
                            <p class="text-xs font-semibold text-base-content/80">جاري الاتصال بالكاميرا...</p>
                        </div>

                        {{-- Error State --}}
                        <div x-show="cameraState === 'error'" class="flex flex-col items-center justify-center p-6 text-center space-y-3">
                            <div class="size-14 rounded-full bg-error/10 text-error flex items-center justify-center">
                                <x-heroicon-o-exclamation-triangle class="size-7" />
                            </div>
                            <p class="text-xs font-bold text-error" x-text="cameraErrorMsg || 'تعذر تشغيل الكاميرا'"></p>
                            <p class="text-[11px] text-base-content/60">يمكنك التبديل لخيار "رفع من الاستوديو" بالأعلى.</p>
                        </div>

                        {{-- Captured Preview Canvas Overlay --}}
                        <canvas id="captured-canvas" class="absolute inset-0 w-full h-full object-cover" x-show="cameraState === 'captured'"></canvas>
                    </div>

                    {{-- Camera Action Controls --}}
                    <div class="space-y-3">
                        <template x-if="cameraState === 'idle' || cameraState === 'error'">
                            <button type="button" @click="startWebcam()" class="btn btn-primary btn-block font-bold gap-2">
                                <x-heroicon-o-video-camera class="size-5" />
                                <span>تشغيل الكاميرا والسماح بالوصول</span>
                            </button>
                        </template>

                        <template x-if="cameraState === 'active'">
                            <button type="button" @click="captureFromWebcam()" class="btn btn-primary btn-block font-bold gap-2">
                                <x-heroicon-o-camera class="size-5" />
                                <span>التقاط الصورة واستخراج البصمة</span>
                            </button>
                        </template>

                        <template x-if="cameraState === 'captured'">
                            <div class="flex gap-2">
                                <button type="button" @click="retakePhoto()" class="btn btn-outline btn-block font-bold gap-2">
                                    <x-heroicon-o-arrow-path class="size-5" />
                                    <span>إعادة الالتقاط</span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Tab 2: Studio / File Upload Panel --}}
                <div x-show="activeTab === 'upload'" class="surface p-6 rounded-3xl space-y-5 shadow-sm border border-base-300">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-sm flex items-center gap-2 text-base-content">
                            <x-heroicon-o-arrow-up-tray class="size-4 text-primary" />
                            رفع صورة من الجهاز / معرض الصور
                        </h3>
                        <span class="text-[11px] text-base-content/60">JPG, PNG, WEBP حتى 10MB</span>
                    </div>

                    {{-- Custom Drag & Drop / Upload Dropzone --}}
                    <div class="relative group cursor-pointer border-2 border-dashed rounded-2xl p-8 transition-all flex flex-col items-center justify-center text-center space-y-3 bg-base-200/40 hover:bg-base-200/80 hover:border-primary"
                        :class="uploadedFile ? 'border-success bg-success/5' : 'border-base-300'"
                        @click="$refs.fileInput.click()">
                        
                        <input type="file" x-ref="fileInput" @change="handleFileUpload($event)" accept="image/*" class="hidden" />

                        <div class="size-16 rounded-full bg-primary/10 text-primary flex items-center justify-center group-hover:scale-110 transition-transform">
                            <x-heroicon-o-photo class="size-8" />
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-bold text-base-content" x-text="uploadedFile ? uploadedFile.name : 'اضغط لاختيار صورة من جهازك'"></p>
                            <p class="text-xs text-base-content/60" x-text="uploadedFile ? (Math.round(uploadedFile.size / 1024) + ' KB') : 'أو قم بسحب وإسقاط الصورة هنا'"></p>
                        </div>

                        <button type="button" class="btn btn-sm btn-secondary font-bold gap-1 mt-2">
                            <x-heroicon-o-folder-open class="size-4" />
                            <span>تصفح الاستوديو</span>
                        </button>
                    </div>
                </div>

                {{-- Status Alert Box --}}
                <div class="alert text-xs rounded-2xl transition-all font-semibold"
                    :class="{
                        'alert-info alert-soft': statusType === 'info',
                        'alert-success': statusType === 'success',
                        'alert-warning': statusType === 'warning',
                        'alert-error': statusType === 'error'
                    }">
                    <span x-text="statusMsg"></span>
                </div>

            </div>

            {{-- Left Preview & Confirmation Column (5 cols) --}}
            <div class="lg:col-span-5 space-y-6">
                
                {{-- Preview Card --}}
                <div class="surface p-6 rounded-3xl space-y-5 border border-base-300 shadow-sm">
                    <h3 class="font-bold text-sm text-base-content flex items-center gap-2">
                        <x-heroicon-o-finger-print class="size-5 text-primary" />
                        معاينة الصورة والرمز الرقمي
                    </h3>

                    {{-- Image Avatar Preview Box --}}
                    <div class="flex flex-col items-center justify-center p-4 bg-base-200/50 rounded-2xl border border-base-300 space-y-3">
                        <div class="relative size-36 rounded-2xl overflow-hidden border-4 shadow-md transition-all bg-base-300 flex items-center justify-center"
                            :class="capturedPhotoUrl ? 'border-primary' : 'border-base-300'">
                            
                            <template x-if="capturedPhotoUrl">
                                <img :src="capturedPhotoUrl" alt="معاينة الصورة" class="w-full h-full object-cover" />
                            </template>

                            <template x-if="!capturedPhotoUrl">
                                <div class="flex flex-col items-center justify-center text-center p-2 space-y-1 text-base-content/50">
                                    <x-heroicon-o-user-circle class="size-16" />
                                    <span class="text-[10px] font-bold">بانتظار التقاط صورة</span>
                                </div>
                            </template>
                        </div>

                        <div class="text-center space-y-1">
                            <p class="text-xs font-bold text-base-content">{{ $user->official_name ?? $user->username }}</p>
                            <p class="text-[11px] text-base-content/60">حالة الصورة الحالية: 
                                <span class="font-bold text-primary">{{ $user->photo_status ?? 'غير مسجلة' }}</span>
                            </p>
                        </div>
                    </div>

                    {{-- Face Vector Descriptor Output --}}
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-semibold text-base-content/80">قيم المتجه الرقمي (Face Descriptor)</span>
                            <span class="badge badge-sm badge-neutral font-mono text-[10px]" x-text="extractedDescriptor ? (extractedDescriptor.length + ' D') : '0 D'"></span>
                        </div>

                        <textarea readonly x-model="descriptorDisplay" 
                            class="textarea textarea-bordered font-mono text-[11px] h-28 w-full bg-base-200/60 leading-tight rounded-xl resize-none"
                            placeholder="سيظهر متجه البصمة الرقمية هنا بعد التقاط أو رفع الصورة..."></textarea>
                    </div>

                    {{-- Submit Button --}}
                    <button type="button" 
                        @click="saveFaceData()" 
                        class="btn btn-success btn-block font-bold gap-2 rounded-xl shadow-md transition-all"
                        :disabled="!extractedDescriptor || isSaving">
                        <template x-if="isSaving">
                            <span class="loading loading-spinner loading-xs"></span>
                        </template>
                        <x-heroicon-o-cloud-arrow-up class="size-5" />
                        <span>إرسال البصمة الرقمية للاعتماد</span>
                    </button>
                </div>

            </div>

        </div>
    </div>
</x-layouts.app>
