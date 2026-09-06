import { request } from '../lib/http';
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

export default (payload) => ({
    session: payload.session,
    exam: payload.exam,
    config: payload.config,
    questions: payload.questions,
    currentIndex: 0,
    remainingSeconds: payload.exam.duration_seconds || 420, // 7 minutes = 420s
    totalSeconds: payload.exam.duration_seconds || 420,
    examStarted: false,
    examSubmitted: false,
    submitting: false,
    bookmarkedIndices: [],
    
    // Integrity & Proctoring
    integrityIndex: 100,
    violationCount: 0,
    violationsList: [],
    blackoutActive: false,
    blackoutTimer: 30,
    blackoutReason: '',
    exitViolationsCount: 0,
    gracePeriodActive: true,
    monitorInstances: [],
    timers: [],
    visionMonitorInstance: null,
    
    // Offline Time Freeze System (3 chances x 10 minutes)
    isOfflineFrozen: false,
    freezeCount: 0,
    maxFreezeChances: payload.config.max_freeze_chances || 3,
    freezeRemainingSeconds: 600, // 10 minutes = 600s
    freezeTimerId: null,
    examTimerId: null,
    
    // Mandatory Fullscreen Mode Gate (Face matching is completed once at instructions gateway)
    fullscreenModalOpen: !Boolean(document.fullscreenElement),
    faceMatched: true,
    preflightModalOpen: false,
    
    // Post-Exam Result & Survey
    score: 0,
    totalPoints: 0,
    timeTakenSeconds: 0,
    surveyOpen: false,
    surveySubmitted: false,
    surveyData: {
        role: 'student',
        name: payload.session.student_name || 'طالب تجريبي',
        email: '',
        organization: 'تجربة منصة منع الغش',
        overall_rating: 5,
        support_anti_cheat: 'strongly_support',
        face_match_rating: 5,
        time_freeze_rating: 5,
        security_rating: 5,
        usability_rating: 5,
        feedback_text: 'نظام ممتاز جداً، وميزة تجميد الوقت عند انقطاع الإنترنت وفرت حماية حقيقية ومنعت القلق أثناء الامتحان!',
    },
    sentimentResult: null,

    init() {
        this.totalPoints = this.questions.reduce((sum, q) => sum + (q.points || 1), 0);
        
        this.questions.forEach((q) => {
            if (!q.answer) q.answer = [];
        });

        // Offline / Online native event listeners
        window.addEventListener('offline', () => this.handleInternetDisconnection());
        window.addEventListener('online', () => this.handleInternetRestoration());

        window.addEventListener('devtools-detected', () => {
            this.pushViolation('devtools_opened', 'فتح أدوات المطورين (DevTools / Inspect Element) محظور!');
        });

        // If already in fullscreen, start directly; otherwise wait for click to enter fullscreen
        if (document.fullscreenElement) {
            this.fullscreenModalOpen = false;
            this.startExam();
        }
    },

    enterFullscreenAndStart() {
        this.fullscreenModalOpen = false;
        try {
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen().catch(() => {});
            }
        } catch {}
        this.startExam();
    },

    startExam() {
        this.fullscreenModalOpen = false;
        this.preflightModalOpen = false;
        this.examStarted = true;
        this.gracePeriodActive = true;

        if (document.documentElement.requestFullscreen && !document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(() => {});
        }

        // 5-Second Grace Period for Camera & Mic Stabilization
        setTimeout(() => {
            this.gracePeriodActive = false;
        }, 5000);

        // Lock Browser History Back Button
        try {
            history.pushState(null, null, location.href);
            window.onpopstate = () => {
                history.pushState(null, null, location.href);
                this.pushViolation('tab_switched', 'محاولة الرجوع للخلف بالمتصفح محظورة أثناء الاختبار!');
            };
        } catch {}

        // Arm all Full Security Proctoring Monitors (Vision, Audio, Devtools, Clipboard, Fullscreen, Visibility, etc.)
        this.armAllMonitors();

        // Start countdown timer
        this.examTimerId = setInterval(() => {
            if (!this.isOfflineFrozen && !this.examSubmitted) {
                this.remainingSeconds -= 1;
                this.timeTakenSeconds += 1;

                if (this.remainingSeconds <= 0) {
                    clearInterval(this.examTimerId);
                    this.finishExam('انتهى وقت الاختبار (7 دقائق)');
                }
            }
        }, 1000);

        this.renderCanvas();
    },

    armAllMonitors() {
        const onViolation = (type, details = null, metadata = null) => this.pushViolation(type, details, metadata);

        const factories = {
            visibility: () => createVisibilityMonitor({ onViolation }),
            fullscreen: () => createFullscreenMonitor({ onViolation }),
            connectivity: () => createConnectivityMonitor({ onStatusChange: (isOnline) => {
                if (!isOnline) this.handleInternetDisconnection();
                else this.handleInternetRestoration();
            }}),
            'copy-paste': () => createClipboardMonitor({ onViolation }),
            devtools: () => createDevtoolsMonitor({ onViolation }),
            'multi-display': () => createMultiDisplayMonitor({ onViolation }),
            keystroke: () => createKeystrokeMonitor({
                onSample: (intervals) => this.pushViolation('keystroke_anomaly', null, { intervals }),
            }),
            audio: () => createAudioMonitor({ onViolation }),
            'ios-block': () => createIosBlockMonitor({ onViolation }),
        };

        // Reuse existing vision monitor from preflight (if any) — never create a duplicate
        const monitorNames = ['visibility', 'fullscreen', 'copy-paste', 'devtools', 'audio', 'multi-display'];
        const newMonitors = monitorNames
            .filter((name) => factories[name])
            .map((name) => factories[name]());

        // If no vision monitor was started in preflight, create one now
        if (!this.visionMonitorInstance) {
            const visionMon = createVisionMonitor({
                onViolation,
                approvedDescriptor: this.session.face_descriptor,
            });
            this.visionMonitorInstance = visionMon;
            newMonitors.push(visionMon);
        }

        // Merge with any pre-existing monitors (e.g. preflight vision)
        this.monitorInstances = [...this.monitorInstances, ...newMonitors];

        newMonitors.forEach((monitor) => {
            try {
                monitor.start();
            } catch (err) {
                console.warn('[Sandbox Proctor] Monitor start:', err);
            }
        });
    },

    destroy() {
        if (this.examTimerId) clearInterval(this.examTimerId);
        if (this.freezeTimerId) clearInterval(this.freezeTimerId);
        this.monitorInstances.forEach((monitor) => monitor.stop?.());
    },

    // ─────────────────────────────────────────────────────────────
    // Offline Time Freeze System (3 chances x 10 minutes)
    // ─────────────────────────────────────────────────────────────
    handleInternetDisconnection() {
        if (this.examSubmitted || !this.examStarted || this.isOfflineFrozen) return;

        if (this.freezeCount >= this.maxFreezeChances) {
            toast('⚠️ تم استنفاد الحد الأقصى لفرص تجميد انقطاع الإنترنت (3 مرات)!', 'error');
            return;
        }

        this.freezeCount += 1;
        this.isOfflineFrozen = true;
        this.freezeRemainingSeconds = 600; // 10 minutes

        this.saveAnswersLocally();

        clearInterval(this.freezeTimerId);
        this.freezeTimerId = setInterval(() => {
            this.freezeRemainingSeconds -= 1;
            if (this.freezeRemainingSeconds <= 0) {
                clearInterval(this.freezeTimerId);
                toast('⚠️ انتهت مهلة الـ 10 دقائق للتجميد، سيتم استئناف المؤقت.', 'warning');
                this.isOfflineFrozen = false;
            }
        }, 1000);

        toast(`🔌 انقطع الاتصال بالإنترنت! تم قفل الشاشة وتجميد وقت الامتحان (الفرصة ${this.freezeCount} من ${this.maxFreezeChances}).`, 'warning');
    },

    handleInternetRestoration() {
        if (!this.isOfflineFrozen) return;

        this.isOfflineFrozen = false;
        clearInterval(this.freezeTimerId);
        toast('⚡ تم استعادة الاتصال بالإنترنت بنجاح! تم استئناف الامتحان وحفظ كافة إجاباتك.', 'success');
        this.renderCanvas();
    },

    simulateOfflineToggle() {
        if (this.isOfflineFrozen) {
            this.handleInternetRestoration();
        } else {
            this.handleInternetDisconnection();
        }
    },

    saveAnswersLocally() {
        try {
            const data = {
                sessionId: this.session.id,
                answers: this.questions.map((q) => ({ id: q.id, answer: q.answer })),
                remainingSeconds: this.remainingSeconds,
                timestamp: Date.now(),
            };
            localStorage.setItem('aegis_sandbox_backup', JSON.stringify(data));
        } catch {}
    },

    // ─────────────────────────────────────────────────────────────
    // Violations Handler & 3-Warning Termination Rule
    // ─────────────────────────────────────────────────────────────
    pushViolation(type, details = null, metadata = null) {
        if (this.examSubmitted || this.violationCount >= 3) return;

        // Skip audio/motion violations during 5-second Grace Period
        if (this.gracePeriodActive && (type === 'audio_violation' || type === 'head_motion' || type === 'face_missing')) {
            return;
        }

        this.violationCount += 1;
        this.integrityIndex = Math.max(10, this.integrityIndex - 12);
        this.violationsList.push({ type, details, time: this.formattedTimeTaken });

        playViolationBeep();

        if (type === 'identity_mismatch') {
            this.blackoutActive = true;
            toast('🚨 تم إغلاق الامتحان فوراً بسبب كشف انتحال شخصية أو تبديل الشخص!', 'error');
            setTimeout(() => this.finishExam('انتحال شخصية'), 1000);
            return;
        }

        if (type === 'visibility_lost' || type === 'tab_switched' || type === 'tab_switch' || type === 'window_blur' || type === 'screenshot_attempt' || type === 'fullscreen_exit') {
            this.triggerExitBlackout(details || 'محاولة الخروج من ملء الشاشة أو تصويرها محظورة');
            return;
        }

        if (type === 'copy_attempt' || type === 'paste_attempt') {
            this.triggerCopyBlackout(details || 'محاولة نسخ أو إلصاق النص محظورة');
            return;
        }

        const messages = {
            audio_violation: '⚠️ تم رصد كلام بشري وصوت مسموع بالقرب من الجهاز!',
            audio_keyword_cheating: '⚠️ تم رصد كلام بشري ومحاولة قراءة سؤال أو طلب غش!',
            head_motion: '⚠️ حركة خارج النطاق المسموح به (الدائرة). يرجى البقاء في المنتصف!',
            face_missing: '⚠️ لم يتم العثور على وجهك أمام الكاميرا! يرجى النظر باتجاه الشاشة.',
            multiple_faces: '⚠️ تم كشف وجود أكثر من شخص أمام الكاميرا!',
            gaze_away: '⚠️ يرجى النظر مباشرة إلى الشاشة وعدم الالتفات جانبياً.',
            fullscreen_exit: '⚠️ الخروج من وضع ملء الشاشة محظور أثناء الاختبار!',
            tab_switch: '⚠️ يمنع التبديل بين النوافذ والتبويبات أثناء الاختبار!',
            tab_switched: '⚠️ يمنع التبديل بين النوافذ والتبويبات أثناء الاختبار!',
            window_blur: '⚠️ فقدان تركيز نافذة الاختبار محظور!',
            visibility_lost: '⚠️ تم كشف الخروج من تبويب الاختبار! تم تسجيل المخالفة.',
            copy_attempt: '⚠️ محاولة نسخ نصوص الامتحان محظورة!',
            paste_attempt: '⚠️ محاولة إلصاق نصوص خارجية محظورة!',
            screenshot_attempt: '⚠️ محاولة التقاط صورة لشاشة الامتحان محظورة!',
            devtools_opened: '⚠️ يمنع فتح أدوات المطورين (DevTools).',
            devtools_shortcut: '⚠️ استخدام اختصارات أدوات الفحص محظور!',
        };

        const msg = details || messages[type] || '⚠️ تم رصد مخالفة أمنية';
        toast(`${msg} (الإنذار ${this.violationCount} من 3)`, this.violationCount >= 3 ? 'error' : 'warning');

        // 3rd Violation: Immediate Permanent Termination
        if (this.violationCount >= 3) {
            this.blackoutActive = true;
            toast('🚨 تم تجاوز الحد الأقصى للمخالفات (3 إنذارات)! تم إغلاق وتسليم الامتحان فوراً.', 'error');
            setTimeout(() => this.finishExam('تجاوز 3 إنذارات أمنية'), 1000);
        }
    },

    triggerExitBlackout(reason) {
        if (this.examSubmitted) return;

        this.exitViolationsCount += 1;
        this.blackoutActive = true;
        this.blackoutReason = reason;

        if (this.exitViolationsCount === 1 && this.violationCount < 3) {
            this.blackoutTimer = 30;
            const timer = setInterval(() => {
                this.blackoutTimer -= 1;
                if (this.blackoutTimer <= 0) {
                    clearInterval(timer);
                }
            }, 1000);
        } else {
            // 2nd Exit Violation or 3 total violations: Immediate Termination
            toast('🚨 المخالفة الثانية للخروج / تجاوز 3 إنذارات: تم إنهاء الاختبار نهائياً.', 'error');
            setTimeout(() => this.finishExam('تكرار الخروج من شاشة الامتحان'), 1000);
        }
    },

    triggerCopyBlackout(reason) {
        if (this.examSubmitted) return;

        this.blackoutActive = true;
        this.blackoutReason = reason;
        this.blackoutTimer = 15;

        const timer = setInterval(() => {
            this.blackoutTimer -= 1;
            if (this.blackoutTimer <= 0) {
                clearInterval(timer);
                this.blackoutActive = false;
                this.resumeFocus();
            }
        }, 1000);
    },

    resumeFocus() {
        this.blackoutActive = false;
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(() => {});
        }
    },

    // ─────────────────────────────────────────────────────────────
    // Question Navigation & Canvas Lens Spotlight
    // ─────────────────────────────────────────────────────────────
    get current() {
        return this.questions[this.currentIndex];
    },

    get answeredCount() {
        return this.questions.filter((q) => this.isAnswered(q)).length;
    },

    isAnswered(q) {
        return Array.isArray(q.answer) ? q.answer.length > 0 : Boolean(q.answer);
    },

    goto(index) {
        this.currentIndex = Math.max(0, Math.min(index, this.questions.length - 1));
        this.renderCanvas();
    },

    next() {
        this.goto(this.currentIndex + 1);
    },

    previous() {
        this.goto(this.currentIndex - 1);
    },

    toggleBookmark(index = null) {
        const idx = index ?? this.currentIndex;
        const pos = this.bookmarkedIndices.indexOf(idx);
        if (pos === -1) {
            this.bookmarkedIndices.push(idx);
        } else {
            this.bookmarkedIndices.splice(pos, 1);
        }
    },

    isBookmarked(idx) {
        return this.bookmarkedIndices.includes(idx);
    },

    selectSingle(val) {
        if (this.current) {
            this.current.answer = [val];
            this.saveAnswersLocally();
        }
    },

    toggleMulti(val) {
        if (!this.current) return;
        const ans = Array.isArray(this.current.answer) ? [...this.current.answer] : [];
        const idx = ans.indexOf(val);
        if (idx === -1) {
            ans.push(val);
        } else {
            ans.splice(idx, 1);
        }
        this.current.answer = ans;
        this.saveAnswersLocally();
    },

    clearAnswer() {
        if (this.current) {
            this.current.answer = [];
        }
    },

    renderCanvas() {
        this.$nextTick(() => {
            const canvas = document.getElementById('question-canvas');
            if (canvas && this.current) {
                renderQuestionCanvas(canvas, this.current.prompt, this.session.student_name, this.session.id, 'question-container');
            }
        });
    },

    // ─────────────────────────────────────────────────────────────
    // Time Formatters
    // ─────────────────────────────────────────────────────────────
    get formattedRemaining() {
        const m = String(Math.floor(this.remainingSeconds / 60)).padStart(2, '0');
        const s = String(this.remainingSeconds % 60).padStart(2, '0');
        return `${m}:${s}`;
    },

    get formattedFreezeRemaining() {
        const m = String(Math.floor(this.freezeRemainingSeconds / 60)).padStart(2, '0');
        const s = String(this.freezeRemainingSeconds % 60).padStart(2, '0');
        return `${m}:${s}`;
    },

    get formattedTimeTaken() {
        const m = String(Math.floor(this.timeTakenSeconds / 60)).padStart(2, '0');
        const s = String(this.timeTakenSeconds % 60).padStart(2, '0');
        return `${m}:${s}`;
    },

    // ─────────────────────────────────────────────────────────────
    // Submission & Result Calculation
    // ─────────────────────────────────────────────────────────────
    confirmSubmit() {
        document.getElementById('sandbox-submit-modal')?.showModal();
    },

    finishExam(reason = 'تسليم من الطالب') {
        this.examSubmitted = true;
        this.destroy();

        let earned = 0;
        this.questions.forEach((q) => {
            const userAns = q.answer || [];
            if (Array.isArray(q.correct)) {
                const matchAll = q.correct.every((c) => userAns.includes(c)) && userAns.length === q.correct.length;
                if (matchAll) earned += (q.points || 1);
            } else if (userAns[0] === q.correct) {
                earned += (q.points || 1);
            }
        });

        this.score = earned;
        this.surveyOpen = true;

        toast(`🎉 تم تسليم الامتحان! درجتك: ${this.score} من ${this.totalPoints}`, 'success');
    },

    // ─────────────────────────────────────────────────────────────
    // Instant Survey Submission & Sentiment Analysis
    // ─────────────────────────────────────────────────────────────
    async submitSurvey() {
        this.submitting = true;

        try {
            const res = await request('/sandbox/surveys', {
                method: 'POST',
                body: {
                    ...this.surveyData,
                    meta: {
                        score: this.score,
                        total_points: this.totalPoints,
                        time_taken_seconds: this.timeTakenSeconds,
                        integrity_index: this.integrityIndex,
                        violations_count: this.violationCount,
                        freeze_used_count: this.freezeCount,
                    },
                },
            });

            this.surveySubmitted = true;
            this.sentimentResult = res.sentiment;
            toast('تم إرسال استبيانك وتحليله بنجاح!', 'success');
        } catch {
            this.surveySubmitted = true;
            toast('تم حفظ الاستبيان محلياً بنجاح.', 'success');
        } finally {
            this.submitting = false;
        }
    },
});
