import { request, beacon } from '../lib/http';
import { toast } from '../lib/toast';
import { createVisibilityMonitor } from '../proctoring/visibility';
import { createFullscreenMonitor } from '../proctoring/fullscreen';
import { createConnectivityMonitor } from '../proctoring/connectivity';
import { createClipboardMonitor } from '../proctoring/clipboard';
import { createDevtoolsMonitor } from '../proctoring/devtools';
import { createMultiDisplayMonitor } from '../proctoring/multi-display';
import { createKeystrokeMonitor } from '../proctoring/keystroke';
import { createVisionMonitor } from '../proctoring/vision';
import { createAudioMonitor } from '../proctoring/audio';
import { createIosBlockMonitor } from '../proctoring/ios-block';
import { renderQuestionCanvas, playViolationBeep } from './secure-question-canvas';

const SIGNAL_FLUSH_MS = 12_000;
const SIGNAL_BATCH_LIMIT = 20;

/**
 * Wires the client proctoring engine to the runner UI. Arms only the
 * monitors the exam's security level calls for (`SecurityLevel::monitors()`),
 * batches violation signals and flushes on an interval (plus on unload via
 * `beacon()`), and drives the countdown from the server's clock — never the
 * browser's.
 */
export default (payload) => ({
    session: payload.session,
    exam: payload.exam,
    config: payload.config,
    questions: payload.questions,
    currentIndex: 0,
    remainingSeconds: 0,
    online: navigator.onLine,
    submitting: false,
    clockOffsetMs: 0,
    signalQueue: [],
    monitorInstances: [],
    timers: [],
    questionEnteredAt: performance.now(),
    preflightModalOpen: true,
    faceMatched: false,
    preflightStatusText: 'جاري التحقق ومطابقة الهوية البصرية...',
    gracePeriodActive: true,
    blackoutActive: false,
    blackoutTimer: 30,
    exitViolationsCount: 0,
    blurOverlayOpen: false,
    bookmarkedIndices: [],
    violationCount: 0,

    // Offline Time Freeze Lock System (3 chances x 10 minutes)
    isOfflineFrozen: false,
    freezeCount: 0,
    maxFreezeChances: payload.config?.max_freeze_chances || 3,
    freezeRemainingSeconds: 600,
    freezeTimerId: null,

    handleOfflineFreeze() {
        if (this.submitting || this.isOfflineFrozen) return;

        if (this.freezeCount >= this.maxFreezeChances) {
            toast('⚠️ تم استنفاد الحد الأقصى لفرص تجميد انقطاع الإنترنت (3 مرات)!', 'error');
            return;
        }

        this.freezeCount += 1;
        this.isOfflineFrozen = true;
        this.freezeRemainingSeconds = 600; // 10 minutes

        clearInterval(this.freezeTimerId);
        this.freezeTimerId = setInterval(() => {
            this.freezeRemainingSeconds -= 1;
            if (this.freezeRemainingSeconds <= 0) {
                clearInterval(this.freezeTimerId);
                this.isOfflineFrozen = false;
                toast('⚠️ انتهت مهلة الـ 10 دقائق للتجميد، تم استئناف مؤقت الاختبار.', 'warning');
            }
        }, 1000);

        toast(`🔌 انقطع الاتصال بالإنترنت! تم قفل الشاشة وتجميد وقت الامتحان (الفرصة ${this.freezeCount} من ${this.maxFreezeChances}).`, 'warning');
    },

    handleOnlineResume() {
        if (!this.isOfflineFrozen) return;

        this.isOfflineFrozen = false;
        clearInterval(this.freezeTimerId);
        toast('⚡ تم استعادة الاتصال بالإنترنت بنجاح! تم استئناف الاختبار وحفظ إجاباتك.', 'success');
        this.renderCanvas();
    },

    get formattedFreezeRemaining() {
        const m = String(Math.floor(this.freezeRemainingSeconds / 60)).padStart(2, '0');
        const s = String(this.freezeRemainingSeconds % 60).padStart(2, '0');
        return `${m}:${s}`;
    },

    startExam() {
        if (!this.faceMatched) return;

        this.preflightModalOpen = false;
        this.gracePeriodActive = true;

        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(() => {});
        }

        this.armMonitors();

        // 5-Second Grace Period for Camera & Microphone Stabilization
        setTimeout(() => {
            this.gracePeriodActive = false;
        }, 5000);

        // Lock Browser History Back Button
        try {
            history.pushState(null, null, location.href);
            window.onpopstate = () => {
                history.pushState(null, null, location.href);
                this.pushSignal('tab_switched', 'محاولة الرجوع للخلف بالمتصفح محظورة أثناء الاختبار!');
            };
        } catch {
            // Safe fallback
        }
    },

    triggerExitBlackout(reason) {
        if (this.submitting) return;

        this.exitViolationsCount += 1;
        this.blackoutActive = true;

        if (this.exitViolationsCount === 1) {
            // First Exit Violation: 30-Second Lockout Timer
            this.blackoutTimer = 30;
            const lockoutInterval = setInterval(() => {
                this.blackoutTimer -= 1;
                if (this.blackoutTimer <= 0) {
                    clearInterval(lockoutInterval);
                    this.blackoutActive = false;
                    if (document.documentElement.requestFullscreen) {
                        document.documentElement.requestFullscreen().catch(() => {});
                    }
                }
            }, 1000);
        } else {
            // Second Exit Violation: Immediate Exam Termination & Fail Submit
            toast('🚨 المخالفة الثانية: تم إنهاء الاختبار نهائياً بسبب محاولة الخروج أو تصوير الشاشة.', 'error');
            this.submitting = true;
            setTimeout(() => this.submit(), 1000);
        }
    },

    triggerCopyBlackout(reason) {
        if (this.submitting) return;

        this.blackoutActive = true;
        this.blackoutReason = reason;
        this.blackoutTimer = 15;

        const lockoutInterval = setInterval(() => {
            this.blackoutTimer -= 1;
            if (this.blackoutTimer <= 0) {
                clearInterval(lockoutInterval);
                this.blackoutActive = false;
                this.blackoutReason = null;
                this.resumeFocus();
            }
        }, 1000);
    },

    resumeFocus() {
        this.blurOverlayOpen = false;
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(() => {});
        }
    },

    toggleBookmark(idx = null) {
        const targetIdx = idx ?? this.currentIndex;
        const pos = this.bookmarkedIndices.indexOf(targetIdx);
        if (pos === -1) {
            this.bookmarkedIndices.push(targetIdx);
        } else {
            this.bookmarkedIndices.splice(pos, 1);
        }
    },

    isBookmarked(idx) {
        return this.bookmarkedIndices.includes(idx);
    },

    clearAnswer() {
        if (this.current) {
            this.current.answer = [];
            this.autosaveCurrent();
        }
    },

    renderCanvas() {
        this.$nextTick(() => {
            const canvas = document.getElementById('question-canvas');
            if (canvas && this.current) {
                renderQuestionCanvas(canvas, this.current.prompt, this.session.student_name, this.session.id);
            }
        });
    },

    initPreflightFaceCheck() {
        this.faceMatched = false;

        // ── حالة 1: لا توجد بصمة مسجلة للطالب → تخطي المطابقة مباشرة ──
        const hasDescriptor = this.session.face_descriptor && this.session.face_descriptor.length > 0;
        if (!hasDescriptor) {
            this.faceMatched = true;
            this.preflightStatusText = '✅ لا توجد بصمة وجه مسجلة — يمكن بدء الامتحان مباشرة';
            return;
        }

        this.preflightStatusText = '⏳ جاري تحميل نماذج الذكاء الاصطناعي للمطابقة...';

        // ── حالة 2: مهلة 30 ثانية — إذا لم تنجح المطابقة يُفتح الزر ──
        const bypassTimer = setTimeout(() => {
            if (!this.faceMatched && this.preflightModalOpen) {
                this.faceMatched = true;
                this.preflightStatusText = '⚠️ تعذّرت مطابقة الوجه (انتهت المهلة) — تم السماح بالدخول';
            }
        }, 30_000);

        const visionMonitor = createVisionMonitor({
            onModelsLoaded: () => {
                if (this.preflightModalOpen && !this.faceMatched) {
                    this.preflightStatusText = '🔍 جاري مسح ومطابقة الوجه أمام الكاميرا...';
                }
            },
            onViolation: (type, details) => {
                if (this.preflightModalOpen) {
                    this.faceMatched = false;
                    if (type === 'camera_denied') {
                        // ── حالة 3: الكاميرا مرفوضة → تخطي بعد 3 ثوانٍ ──
                        this.preflightStatusText = '⚠️ تعذّر الوصول للكاميرا — سيتم السماح بالدخول خلال 5 ثوانٍ...';
                        setTimeout(() => {
                            clearTimeout(bypassTimer);
                            if (this.preflightModalOpen) {
                                this.faceMatched = true;
                                this.preflightStatusText = '⚠️ تم الدخول بدون مطابقة الكاميرا (الكاميرا غير متاحة)';
                            }
                        }, 5_000);
                    } else if (type === 'identity_mismatch') {
                        this.preflightStatusText = '❌ تعذرت مطابقة الهوية، الشخص المتواجد غير مطابق للبصمة الرقمية!';
                    } else if (type === 'face_missing') {
                        this.preflightStatusText = '🔍 لم يتم العثور على وجه، يرجى التموضع مباشرة أمام الكاميرا';
                    } else if (type === 'multiple_faces') {
                        this.preflightStatusText = '⚠️ تم كشف أكثر من شخص أمام الكاميرا!';
                    }
                } else {
                    this.pushSignal(type, details);
                }
            },
            onFaceMatch: (matched) => {
                if (this.preflightModalOpen) {
                    this.faceMatched = Boolean(matched);
                    if (matched) {
                        clearTimeout(bypassTimer);
                        this.preflightStatusText = '✅ تم التحقق وتطابق الهوية بنجاح (100% Match)';
                    } else {
                        this.preflightStatusText = '🔍 لم يتم العثور على وجه، يرجى التموضع مباشرة أمام الكاميرا';
                    }
                }
            },
            approvedDescriptor: this.session.face_descriptor || null,
        });

        visionMonitor.start();
        this.monitorInstances.push(visionMonitor);
    },

    init() {
        this.clockOffsetMs = Date.parse(this.session.server_time) - Date.now();
        this.updateRemaining();
        this.questionEnteredAt = performance.now();

        this.timers.push(setInterval(() => this.updateRemaining(), 1000));
        this.timers.push(setInterval(() => this.autosaveCurrent(), this.config.autosave_interval_seconds * 1000));
        this.timers.push(setInterval(() => this.sendHeartbeat(), this.config.heartbeat_interval_seconds * 1000));
        this.timers.push(setInterval(() => this.flushSignals(), SIGNAL_FLUSH_MS));

        this.initPreflightFaceCheck();
        this.armMonitors();
        this.renderCanvas();

        window.addEventListener('offline', () => this.handleOfflineFreeze());
        window.addEventListener('online', () => this.handleOnlineResume());

        window.addEventListener('devtools-detected', () => {
            this.pushSignal('devtools_opened', 'فتح أدوات المطورين (DevTools / Inspect Element) محظور!');
        });

        window.addEventListener('beforeunload', () => this.flushSignals(true));
        window.addEventListener('pagehide', () => this.flushSignals(true));
    },

    destroy() {
        this.timers.forEach((id) => clearInterval(id));
        if (this.freezeTimerId) clearInterval(this.freezeTimerId);
        this.monitorInstances.forEach((monitor) => monitor.stop?.());
    },

    get current() {
        return this.questions[this.currentIndex];
    },

    get answeredCount() {
        return this.questions.filter((q) => this.isAnswered(q)).length;
    },

    isAnswered(question) {
        return Array.isArray(question.answer) ? question.answer.length > 0 : Boolean(question.answer);
    },

    goto(index) {
        this.autosaveCurrent();
        this.currentIndex = Math.max(0, Math.min(index, this.questions.length - 1));
        this.questionEnteredAt = performance.now();
        this.renderCanvas();
    },

    next() {
        this.goto(this.currentIndex + 1);
    },

    previous() {
        this.goto(this.currentIndex - 1);
    },

    selectSingle(value) {
        this.current.answer = [value];
    },

    toggleMulti(value) {
        const answer = Array.isArray(this.current.answer) ? [...this.current.answer] : [];
        const index = answer.indexOf(value);

        if (index === -1) {
            answer.push(value);
        } else {
            answer.splice(index, 1);
        }

        this.current.answer = answer;
    },

    setText(value) {
        this.current.answer = value ? [value] : [];
    },

    async autosaveCurrent() {
        const question = this.current;

        if (!question) {
            return;
        }

        const spent = Math.round((performance.now() - this.questionEnteredAt) / 1000);
        question.time_spent_seconds = (question.time_spent_seconds ?? 0) + Math.max(0, spent);
        this.questionEnteredAt = performance.now();

        try {
            await request(`/exam-sessions/${this.session.id}/answer`, {
                method: 'POST',
                body: {
                    question_id: question.id,
                    answer: question.answer ?? [],
                    time_spent_seconds: question.time_spent_seconds,
                },
            });
        } catch {
            toast('تعذّر حفظ الإجابة تلقائياً، سيُعاد المحاولة.', 'warning');
        }
    },

    updateRemaining() {
        if (!this.session.expires_at || this.isOfflineFrozen) {
            return;
        }

        const expiresAtMs = Date.parse(this.session.expires_at);
        const now = Date.now() + this.clockOffsetMs;
        this.remainingSeconds = Math.max(0, Math.floor((expiresAtMs - now) / 1000));

        if (this.remainingSeconds === 0 && !this.submitting) {
            this.autoSubmit();
        }
    },

    get formattedRemaining() {
        const total = this.remainingSeconds;
        const h = String(Math.floor(total / 3600)).padStart(2, '0');
        const m = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
        const s = String(total % 60).padStart(2, '0');

        return `${h}:${m}:${s}`;
    },

    async autoSubmit() {
        this.submitting = true;
        await this.autosaveCurrent();
        this.$refs.submitForm?.requestSubmit();
    },

    confirmSubmit() {
        this.autosaveCurrent().finally(() => {
            document.getElementById('submit-confirm-modal')?.showModal();
        });
    },

    submit() {
        this.submitting = true;
        this.$refs.submitForm?.requestSubmit();
    },

    async sendHeartbeat() {
        const wasOnline = this.online;
        this.online = navigator.onLine;

        try {
            await request(`/exam-sessions/${this.session.id}/heartbeat`, {
                method: 'POST',
                body: {
                    status: this.online ? 'online' : 'offline',
                    duration_seconds: this.online ? 0 : this.config.heartbeat_interval_seconds,
                    client_time: Date.now(),
                },
            });
        } catch {
            // A missed heartbeat is itself informative to the server via silence; never block the UI.
        }

        if (!wasOnline && this.online) {
            toast('تم استعادة الاتصال.', 'success');
        }
    },

    pushSignal(type, details = null, metadata = null) {
        if (this.submitting || this.violationCount >= 5) return;

        // Skip audio/motion violations during 5-second Grace Period
        if (this.gracePeriodActive && (type === 'audio_violation' || type === 'head_motion' || type === 'face_missing')) {
            return;
        }

        this.violationCount += 1;
        this.signalQueue.push({ type, details, metadata });
        playViolationBeep();

        if (type === 'identity_mismatch') {
            this.submitting = true;
            this.blackoutActive = true;
            toast('🚨 تم إغلاق الامتحان فوراً بسبب كشف انتحال شخصية أو تبديل الشخص!', 'error');
            this.submit();
            return;
        }

        if (type === 'visibility_lost' || type === 'tab_switched' || type === 'screenshot_attempt' || type === 'fullscreen_exit') {
            this.triggerExitBlackout(details || 'محاولة الخروج أو تصوير الشاشة محظورة');
            return;
        }

        if (type === 'copy_attempt' || type === 'paste_attempt') {
            this.triggerCopyBlackout(details || 'محاولة نسخ أو إلصاق النص محظورة');
            return;
        }

        const messages = {
            audio_violation: '⚠️ تنبيه مراقبة: تم كشف صوت حديث بشري مرتفع أثناء الاختبار!',
            face_missing: '⚠️ تنبيه مراقبة: لم يتم العثور على وجهك أمام الكاميرا! يرجى النظر باتجاه الشاشة.',
            multiple_faces: '⚠️ تنبيه مراقبة: تم كشف وجود أكثر من شخص أمام الكاميرا!',
            identity_mismatch: '❌ تنبيه مراقبة صارم: الشخص المتواجد أمام الكاميرا غير مطابق للبصمة الرقمية المعتمدة للطالب!',
            gaze_away: '⚠️ تنبيه مراقبة: يرجى النظر مباشرة إلى الشاشة وعدم الالتفات جانبياً.',
            head_motion: '⚠️ تنبيه مراقبة: تدوير أو تحريك الرأس بعيداً عن الكاميرا غير مسموح به.',
            visibility_lost: '⚠️ تنبيه مراقبة: تم كشف الخروج من تبويب الاختبار! تم تسجيل المخالفة.',
            tab_switched: '⚠️ تنبيه مراقبة: يمنع التبديل بين النوافذ والتبويبات أثناء الاختبار.',
            copy_attempt: '⚠️ تنبيه مراقبة: محاولة نسخ المحتوى محظورة!',
            paste_attempt: '⚠️ تنبيه مراقبة: محاولة إلصاق النص محظورة!',
            screenshot_attempt: '⚠️ تنبيه مراقبة: محاولة التقاط صورة لشاشة الامتحان محظورة!',
            devtools_opened: '⚠️ تنبيه مراقبة: يمنع فتح أدوات المطورين (DevTools).',
            devtools_shortcut: '⚠️ تنبيه مراقبة: استخدام اختصارات أدوات الفحص محظور!',
            microphone_denied: '⚠️ تنبيه مراقبة: تعذّر الوصول للميكروفون.',
            ios_device_blocked: '❌ خطأ أمني: الأجهزة المحمولة من نوع iOS غير مدعومة لأداء هذا الاختبار.',
        };

        const msg = details || messages[type];
        if (msg) {
            toast(`${msg} (الإنذارات: ${this.violationCount} / 3)`, type === 'ios_device_blocked' || this.violationCount >= 3 ? 'error' : 'warning');
        }

        if (this.violationCount >= 3) {
            this.submitting = true;
            this.blackoutActive = true;
            toast('🚨 تم تجاوز الحد الأقصى للمخالفات (3 إنذارات)! تم إغلاق وتسليم الاختبار فوراً.', 'error');
            this.submit();
        }

        if (this.signalQueue.length >= SIGNAL_BATCH_LIMIT) {
            this.flushSignals();
        }
    },

    flushSignals(useBeacon = false) {
        if (this.signalQueue.length === 0) {
            return;
        }

        const signals = this.signalQueue.splice(0, this.signalQueue.length);
        const url = `/exam-sessions/${this.session.id}/signal`;

        if (useBeacon) {
            beacon(url, { signals });

            return;
        }

        request(url, { method: 'POST', body: { signals } }).catch(() => {
            this.signalQueue.push(...signals);
        });
    },

    armMonitors() {
        const onViolation = (type, details = null, metadata = null) => this.pushSignal(type, details, metadata);

        const factories = {
            visibility: () => createVisibilityMonitor({ onViolation }),
            fullscreen: () => createFullscreenMonitor({ onViolation }),
            connectivity: () => createConnectivityMonitor({ onStatusChange: (isOnline) => { this.online = isOnline; } }),
            'copy-paste': () => createClipboardMonitor({ onViolation }),
            devtools: () => createDevtoolsMonitor({ onViolation }),
            'multi-display': () => createMultiDisplayMonitor({ onViolation }),
            keystroke: () => createKeystrokeMonitor({
                onSample: (intervals) => this.pushSignal('keystroke_anomaly', null, { intervals }),
            }),
            vision: () => createVisionMonitor({
                onViolation,
                approvedDescriptor: this.session.face_descriptor,
            }),
            audio: () => createAudioMonitor({ onViolation }),
            'ios-block': () => createIosBlockMonitor({ onViolation }),
        };

        this.monitorInstances = (this.exam.monitors ?? [])
            .filter((name) => factories[name])
            .map((name) => factories[name]());

        this.monitorInstances.forEach((monitor) => {
            try {
                monitor.start();
            } catch {
                // A monitor that fails to start must never break the exam.
            }
        });
    },
});
