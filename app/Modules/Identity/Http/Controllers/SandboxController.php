<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Models\SandboxSurvey;
use App\Modules\Identity\Services\SentimentAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SandboxController
{
    public function __construct(
        private readonly SentimentAnalysisService $sentimentService
    ) {}

    /**
     * Pre-Exam Gateway for Student Sandbox:
     * Explains security guidelines and provides Face Matching toggle & photo capture.
     */
    public function studentGateway(): View
    {
        return view('identity.sandbox.student-gateway');
    }

    /**
     * 15-Question / 7-Minute High-Security Simulated Live Exam Runner.
     */
    public function studentExam(Request $request): View
    {
        $faceVerificationEnabled = filter_var($request->query('face_match', '1'), FILTER_VALIDATE_BOOLEAN);
        $studentName = $request->query('student_name', 'طالب تجريبي (Sandbox)');
        $photoType = $request->query('photo_type', 'default');

        $questions = [
            [
                'id' => 101,
                'prompt' => 'ما هو الإجراء التلقائي الفوري الذي يتخذه نظام الحماية عند رصد انقطاع اتصال الإنترنت لدى الطالب؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'قفل الشاشة وتجميد وقت الامتحان فوراً لمنع استغلال الوقت في البحث',
                    'إنهاء الامتحان وتسجيل رسوب مباشر',
                    'استمرار المؤقت في العد التنازلي دون توقف',
                    'إلغاء جميع الإجابات السابقة',
                ],
                'correct' => 'قفل الشاشة وتجميد وقت الامتحان فوراً لمنع استغلال الوقت في البحث',
            ],
            [
                'id' => 102,
                'prompt' => 'أي من الإشارات التالية تُعتبر مخالفة سلوكية في نظام المراقبة الذكي Aegis-X؟',
                'type' => 'multiple_select',
                'type_label' => 'اختيارات متعددة',
                'points' => 2,
                'options' => [
                    'الخروج من تبويب أو نافذة الاختبار',
                    'محاولة نسخ أو لصق نصوص الأسئلة',
                    'فتح أدوات المطورين (DevTools / Inspect)',
                    'استخدام اختصارات تصوير الشاشة (Screenshot)',
                ],
                'correct' => [
                    'الخروج من تبويب أو نافذة الاختبار',
                    'محاولة نسخ أو لصق نصوص الأسئلة',
                    'فتح أدوات المطورين (DevTools / Inspect)',
                    'استخدام اختصارات تصوير الشاشة (Screenshot)',
                ],
            ],
            [
                'id' => 103,
                'prompt' => 'هل تجميد الوقت أثناء انقطاع الإنترنت يتيح للطالب متابعة قراءة الأسئلة والتفكير بها؟',
                'type' => 'true_false',
                'type_label' => 'صح / خطأ',
                'points' => 1,
                'options' => ['صح', 'خطأ (يتم قفل الشاشة تماماً لضمان العدالة وتكافؤ الفرص)'],
                'correct' => 'خطأ (يتم قفل الشاشة تماماً لضمان العدالة وتكافؤ الفرص)',
            ],
            [
                'id' => 104,
                'prompt' => 'ما هو الحد الأقصى المسموح به لفرص تجميد وقت الامتحان عند انقطاع الإنترنت؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    '3 فرص تجميد كحد أقصى (تصل إلى 10 دقائق لكل فرصة)',
                    'فرصة واحدة فقط لمدة دقيقة',
                    'عدد غير محدود من المرات',
                    '10 فرص لمدة نصف دقيقة',
                ],
                'correct' => '3 فرص تجميد كحد أقصى (تصل إلى 10 دقائق لكل فرصة)',
            ],
            [
                'id' => 105,
                'prompt' => 'كيف تضمن المنصة منع انتحال شخصية الطالب أثناء الاختبار؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'المطابقة الحيوية المباشرة للوجه (Face Matching) وبصمة المتصفح الموثوقة',
                    'طلب اسم المستخدم فقط',
                    'السماح لأي شخص بالدخول دون تحقق',
                    'الاعتماد على إقرار شفهي فقط',
                ],
                'correct' => 'المطابقة الحيوية المباشرة للوجه (Face Matching) وبصمة المتصفح الموثوقة',
            ],
            [
                'id' => 106,
                'prompt' => 'ما هي المدة الزمنية القصوى لكل عملية تجميد وقت عند انقطاع الشبكة؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    '10 دقائق كحد أقصى لكل انقطاع',
                    'ساعتان كاملتان',
                    '30 ثانية فقط',
                    'يوم كامل',
                ],
                'correct' => '10 دقائق كحد أقصى لكل انقطاع',
            ],
            [
                'id' => 107,
                'prompt' => 'ما هو دور بصمة الجهاز الرقمية (Device Fingerprint) في تأمين جلسة الاختبار؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'منع تبديل الجهاز أو مشاركة الرابط مع طرف خارجي أثناء الامتحان',
                    'تسريع سرعة الإنترنت لدى الطالب',
                    'تغيير لغة لوحة المفاتيح',
                    'إلغاء المؤقت الزمني',
                ],
                'correct' => 'منع تبديل الجهاز أو مشاركة الرابط مع طرف خارجي أثناء الامتحان',
            ],
            [
                'id' => 108,
                'prompt' => 'عند محاولة نسخ نص السؤال، ما هو الإجراء الفوري المطبق في نظام الحماية؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'تعتيم الشاشة فوراً (Blackout Lockout) لمدة 15 ثانية وتسجيل إنذار',
                    'إرسال بريد إلكتروني فقط',
                    'إيقاف الكاميرا',
                    'تجاوز السؤال والانتقال للتالي',
                ],
                'correct' => 'تعتيم الشاشة فوراً (Blackout Lockout) لمدة 15 ثانية وتسجيل إنذار',
            ],
            [
                'id' => 109,
                'prompt' => 'أي من التقنيات التالية تُستخدم لمراقبة الصوت والكشف عن التحدث البشري في الغرفة؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'خوارزمية Voice Activity Detection (VAD) الحية في المتصفح',
                    'المعالجة اليدوية بعد شهر من الامتحان',
                    'إيقاف ميكروفون الجهاز نهائياً',
                    'أدوات الترجمة التلقائية',
                ],
                'correct' => 'خوارزمية Voice Activity Detection (VAD) الحية في المتصفح',
            ],
            [
                'id' => 110,
                'prompt' => 'هل يتم إرسال إشارات المراقبة ومعالجتها في الخلفية دون التأثير على سرعة حفظ إجابات الطالب؟',
                'type' => 'true_false',
                'type_label' => 'صح / خطأ',
                'points' => 1,
                'options' => ['صح (معالجة غير معطلة Non-blocking)', 'خطأ'],
                'correct' => 'صح (معالجة غير معطلة Non-blocking)',
            ],
            [
                'id' => 111,
                'prompt' => 'في حال تكرار محاولة الخروج من الامتحان أو تصوير الشاشة للمرة الثانية، ما هو القرار الأمني؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'إنهاء وتسكير الامتحان وتطبيق رسوب مباشر فوراً',
                    'منحه 10 دقائق إضافية',
                    'إعادة تشغيل الجهاز',
                    'إلغاء المراقبة',
                ],
                'correct' => 'إنهاء وتسكير الامتحان وتطبيق رسوب مباشر فوراً',
            ],
            [
                'id' => 112,
                'prompt' => 'ما الغرض الأساسي من تفعيل التحقق البصري المسبق (Face Verification) قبل الدخول للامتحان؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'التأكد من أن الطالب الحقيقي هو المتواجد أمام الكاميرا قبل كشف الأسئلة',
                    'التقاط صورة للشهادة فقط',
                    'قياس سرعة المعالج',
                    'تزيين واجهة الاختبار',
                ],
                'correct' => 'التأكد من أن الطالب الحقيقي هو المتواجد أمام الكاميرا قبل كشف الأسئلة',
            ],
            [
                'id' => 113,
                'prompt' => 'أي من العناصر التالية مشمولة في احتساب مؤشر النزاهة الفوري (Integrity Index)؟',
                'type' => 'multiple_select',
                'type_label' => 'اختيارات متعددة',
                'points' => 2,
                'options' => [
                    'حركات الرأس والنظر بعيداً عن الشاشة',
                    'رصد تعدد الوجوه أو اختفاء الوجه',
                    'محاولات التبديل بين النوافذ أو فتح أدوات الفحص',
                    'رصد الأصوات والهمس المرتفع في محيط الطالب',
                ],
                'correct' => [
                    'حركات الرأس والنظر بعيداً عن الشاشة',
                    'رصد تعدد الوجوه أو اختفاء الوجه',
                    'محاولات التبديل بين النوافذ أو فتح أدوات الفحص',
                    'رصد الأصوات والهمس المرتفع في محيط الطالب',
                ],
            ],
            [
                'id' => 114,
                'prompt' => 'هل يتم تشفير إجابات الطالب وحفظها محلياً في المتصفح لضمان عدم ضياعها في حال حدوث أي طارئ في الاتصال؟',
                'type' => 'true_false',
                'type_label' => 'صح / خطأ',
                'points' => 1,
                'options' => ['صح (حفظ فوري متزامن)', 'خطأ'],
                'correct' => 'صح (حفظ فوري متزامن)',
            ],
            [
                'id' => 115,
                'prompt' => 'ما هي الرسالة والهدف الرئيسي لمنظومة منع الغش Aegis-X؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'توفير بيئة اختبارات إلكترونية عادلة ونزيهة تحمي مجهود الطالب وتمنع التسريب والانتحال',
                    'تعقيد الامتحانات على الطلاب',
                    'منع الطلاب من إكمال تعليمهم',
                    'زيادة تكلفة الخوادم',
                ],
                'correct' => 'توفير بيئة اختبارات إلكترونية عادلة ونزيهة تحمي مجهود الطالب وتمنع التسريب والانتحال',
            ],
        ];

        $runnerPayload = [
            'exam' => [
                'id' => 'sandbox-high-security',
                'title' => 'اختبار النظام فائق الحماية (High-Security Sandbox Exam)',
                'duration_minutes' => 7, // 7 minutes exactly as requested!
                'duration_seconds' => 420, // 7 * 60 = 420 seconds
                'security_level' => 'maximum',
                'total_questions' => 15,
                'monitors' => ['visibility', 'fullscreen', 'connectivity', 'copy-paste', 'devtools', 'vision', 'audio', 'multi-display'],
            ],
            'session' => [
                'id' => 'sandbox-session-' . time(),
                'student_name' => $studentName,
                'integrity_index' => 100,
                'expires_at' => now()->addMinutes(7)->toISOString(),
                'server_time' => now()->toISOString(),
                'face_verification_required' => $faceVerificationEnabled,
                'face_descriptor' => $faceVerificationEnabled ? [0.12, -0.34, 0.56, 0.78, -0.11] : null,
                'photo_type' => $photoType,
            ],
            'config' => [
                'heartbeat_interval_seconds' => 10,
                'autosave_interval_seconds' => 10,
                'max_freeze_chances' => 3,
                'freeze_duration_minutes' => 10,
            ],
            'questions' => $questions,
        ];

        return view('identity.sandbox.student-exam', [
            'runner' => $runnerPayload,
            'faceVerificationEnabled' => $faceVerificationEnabled,
        ]);
    }

    /**
     * Store submitted survey and return sentiment analysis response.
     */
    public function storeSurvey(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:student,teacher,institution,family'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'overall_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'support_anti_cheat' => ['required', 'string', 'in:strongly_support,support,neutral,oppose,strongly_oppose'],
            'face_match_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'time_freeze_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'security_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'usability_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'feedback_text' => ['nullable', 'string', 'max:2000'],
            'meta' => ['nullable', 'array'],
        ]);

        $sentiment = $this->sentimentService->analyze(
            $validated['feedback_text'] ?? '',
            $validated['support_anti_cheat'],
            (int) $validated['overall_rating']
        );

        $survey = SandboxSurvey::create([
            ...$validated,
            'sentiment_score' => $sentiment['sentiment_score'],
            'sentiment_label' => $sentiment['sentiment_label'],
            'detected_keywords' => $sentiment['detected_keywords'],
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'شكراً لك! تم استلام تقييمك واستبيانك وتحليله بنجاح.',
                'survey' => $survey,
                'sentiment' => $sentiment,
            ]);
        }

        return redirect()->route('sandbox.surveys')->with('status', 'شكراً لك! تم حفظ استبيانك ومشاركته في مركز التحليلات.');
    }

    /**
     * Teacher Sandbox interactive demo: AI quiz authoring + Live proctoring wall preview.
     */
    public function teacherDemo(): View
    {
        return view('identity.sandbox.teacher-demo');
    }

    /**
     * Institution Sandbox interactive demo: Institutional overview + Threat intelligence.
     */
    public function institutionDemo(): View
    {
        return view('identity.sandbox.institution-demo');
    }

    /**
     * Family & Parents dedicated survey page.
     */
    public function familySurvey(): View
    {
        return view('identity.sandbox.family-survey');
    }

    /**
     * Surveys & AI Sentiment Analysis Dashboard.
     */
    public function surveysDashboard(): View
    {
        $summary = $this->sentimentService->getAnalyticsSummary();

        return view('identity.sandbox.surveys-dashboard', [
            'summary' => $summary,
        ]);
    }

    /**
     * Export complete analytical report (Print preview, Markdown, JSON export).
     */
    public function exportReport(Request $request): View|JsonResponse
    {
        $summary = $this->sentimentService->getAnalyticsSummary();
        $format = $request->query('format', 'html');

        if ($format === 'json') {
            return response()->json($summary);
        }

        return view('identity.sandbox.report-export', [
            'summary' => $summary,
            'generatedAt' => now()->toDateTimeString(),
        ]);
    }
}
