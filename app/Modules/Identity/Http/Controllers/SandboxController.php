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
            // ─── جغرافيا (1-4) ───
            [
                'id' => 101,
                'prompt' => 'ما هي عاصمة دولة فلسطين؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'القدس الشريف',
                    'رام الله',
                    'غزة',
                    'نابلس',
                ],
                'correct' => 'القدس الشريف',
            ],
            [
                'id' => 102,
                'prompt' => 'أي من المدن الفلسطينية التالية تقع على ساحل البحر الأبيض المتوسط؟',
                'type' => 'multiple_select',
                'type_label' => 'اختيارات متعددة',
                'points' => 2,
                'options' => [
                    'غزة',
                    'يافا',
                    'الخليل',
                    'حيفا',
                ],
                'correct' => [
                    'غزة',
                    'يافا',
                    'حيفا',
                ],
            ],
            [
                'id' => 103,
                'prompt' => 'ما هو أخفض بقعة على سطح الأرض والذي يقع على الحدود الشرقية لفلسطين؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'البحر الميت',
                    'بحيرة طبريا',
                    'نهر الأردن',
                    'البحر الأحمر',
                ],
                'correct' => 'البحر الميت',
            ],
            [
                'id' => 104,
                'prompt' => 'هل تقع مدينة نابلس بين جبلي عيبال وجرزيم؟',
                'type' => 'true_false',
                'type_label' => 'صح / خطأ',
                'points' => 1,
                'options' => ['صح', 'خطأ'],
                'correct' => 'صح',
            ],

            // ─── تاريخ (5-8) ───
            [
                'id' => 105,
                'prompt' => 'في أي عام وقعت نكبة فلسطين؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    '1948م',
                    '1936م',
                    '1967م',
                    '1917م',
                ],
                'correct' => '1948م',
            ],
            [
                'id' => 106,
                'prompt' => 'متى صدر وعد بلفور المشؤوم؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    '2 نوفمبر 1917م',
                    '15 مايو 1948م',
                    '5 يونيو 1967م',
                    '30 أكتوبر 1918م',
                ],
                'correct' => '2 نوفمبر 1917م',
            ],
            [
                'id' => 107,
                'prompt' => 'من هو القائد المسلم الذي حرّر القدس من الصليبيين عام 1187م؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'صلاح الدين الأيوبي',
                    'عمر بن الخطاب',
                    'خالد بن الوليد',
                    'محمد الفاتح',
                ],
                'correct' => 'صلاح الدين الأيوبي',
            ],
            [
                'id' => 108,
                'prompt' => 'هل فُتحت القدس في عهد الخليفة عمر بن الخطاب رضي الله عنه؟',
                'type' => 'true_false',
                'type_label' => 'صح / خطأ',
                'points' => 1,
                'options' => ['صح', 'خطأ'],
                'correct' => 'صح',
            ],

            // ─── لغة عربية (9-12) ───
            [
                'id' => 109,
                'prompt' => 'ما إعراب كلمة "الطالبُ" في جملة: "نجحَ الطالبُ المجتهدُ"؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'فاعل مرفوع وعلامة رفعه الضمة الظاهرة',
                    'مفعول به منصوب وعلامة نصبه الفتحة',
                    'مبتدأ مرفوع وعلامة رفعه الضمة',
                    'خبر مرفوع وعلامة رفعه الضمة',
                ],
                'correct' => 'فاعل مرفوع وعلامة رفعه الضمة الظاهرة',
            ],
            [
                'id' => 110,
                'prompt' => 'ما جمع كلمة "كتاب"؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'كُتُب',
                    'كتابات',
                    'مكاتب',
                    'كاتبون',
                ],
                'correct' => 'كُتُب',
            ],
            [
                'id' => 111,
                'prompt' => 'أي من الأدوات التالية تُستخدم لنصب الفعل المضارع؟',
                'type' => 'multiple_select',
                'type_label' => 'اختيارات متعددة',
                'points' => 2,
                'options' => [
                    'أنْ',
                    'لنْ',
                    'لمْ',
                    'كيْ',
                ],
                'correct' => [
                    'أنْ',
                    'لنْ',
                    'كيْ',
                ],
            ],
            [
                'id' => 112,
                'prompt' => 'هل "لمْ" حرف جزم يدخل على الفعل المضارع فيجزمه؟',
                'type' => 'true_false',
                'type_label' => 'صح / خطأ',
                'points' => 1,
                'options' => ['صح', 'خطأ'],
                'correct' => 'صح',
            ],

            // ─── تربية إسلامية (13-15) ───
            [
                'id' => 113,
                'prompt' => 'كم عدد أركان الإسلام؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'خمسة أركان',
                    'ثلاثة أركان',
                    'ستة أركان',
                    'أربعة أركان',
                ],
                'correct' => 'خمسة أركان',
            ],
            [
                'id' => 114,
                'prompt' => 'ما هي أول سورة نزلت في القرآن الكريم؟',
                'type' => 'multiple_choice',
                'type_label' => 'اختيار من متعدد',
                'points' => 1,
                'options' => [
                    'سورة العلق',
                    'سورة الفاتحة',
                    'سورة البقرة',
                    'سورة الإخلاص',
                ],
                'correct' => 'سورة العلق',
            ],
            [
                'id' => 115,
                'prompt' => 'أي من العبادات التالية تُعدّ من أركان الإسلام الخمسة؟',
                'type' => 'multiple_select',
                'type_label' => 'اختيارات متعددة',
                'points' => 2,
                'options' => [
                    'الصلاة',
                    'الزكاة',
                    'قراءة القرآن يومياً',
                    'صوم رمضان',
                ],
                'correct' => [
                    'الصلاة',
                    'الزكاة',
                    'صوم رمضان',
                ],
            ],
        ];

        $runnerPayload = [
            'exam' => [
                'id' => 'sandbox-high-security',
                'title' => 'اختبار تجريبي — المنهاج الفلسطيني (متعدد المواد)',
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
                'face_verification_required' => false, // Matching is completed once at instructions gateway
                'face_descriptor' => null,
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
