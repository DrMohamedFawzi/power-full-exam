<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="aegis">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>التقرير التحليلي الشامل للاستبيانات والمشاعر — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; padding: 0 !important; }
            .card { border: 1px solid #ccc !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-base-100 min-h-screen text-base-content p-6 lg:p-12">
    <div class="mx-auto max-w-4xl space-y-8">
        {{-- Print & Action Bar --}}
        <div class="no-print flex items-center justify-between border-b border-base-300 pb-4">
            <a href="{{ route('sandbox.surveys') }}" class="btn btn-ghost btn-sm">
                &rarr; العودة لمركز التحليلات
            </a>
            <div class="flex gap-2">
                <button type="button" onclick="window.print()" class="btn btn-primary btn-sm font-bold gap-1">
                    <x-heroicon-o-printer class="size-4" />
                    <span>طباعة / حفظ كملف PDF</span>
                </button>
            </div>
        </div>

        {{-- Document Header --}}
        <div class="border-b-2 border-primary pb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-ui.logo class="size-10" />
                    <div>
                        <h1 class="text-2xl font-black text-base-content">{{ config('app.name') }}</h1>
                        <p class="text-xs text-muted">منصة الاختبارات الإلكترونية الآمنة ومنظومة منع الغش</p>
                    </div>
                </div>
                <div class="text-left">
                    <span class="badge badge-primary font-bold text-xs">تقرير تحليلي رسمي</span>
                    <p class="text-[11px] text-muted mt-1 numeric">تاريخ التوليد: {{ $generatedAt }}</p>
                </div>
            </div>

            <div class="mt-6">
                <h2 class="text-xl font-black">
                    تقرير استطلاع وتقييم الرأي: نظام منع الغش، التحقق البصري، وتجميد انقطاع الإنترنت
                </h2>
                <p class="text-xs text-muted mt-1 leading-relaxed">
                    ملخص تحليلي شامل لمخرجات استبيانات الطلاب، أعضاء هيئة التدريس، إدارات المؤسسات، وأولياء الأمور لقياس مدى القبول المجتمعي والأكاديمي للمنظومة.
                </p>
            </div>
        </div>

        {{-- Section 1: Executive Summary --}}
        <div class="card bg-base-200/50 border border-base-300 p-5 rounded-2xl">
            <h3 class="text-sm font-extrabold text-primary mb-2 flex items-center gap-1.5">
                <x-heroicon-o-document-magnifying-glass class="size-4" />
                <span>1. الملخص التنفيذي (Executive Summary)</span>
            </h3>
            <p class="text-xs text-base-content/90 leading-relaxed">
                أظهرت نتائج الاستبيانات وتحليل النصوص بالذكاء الاصطناعي **تأييداً ساحقاً بنسبة {{ $summary['support_percentage'] }}%** لتطبيق منظومة منع الغش.
                سجلت ميزة **"تجميد الوقت عند انقطاع الإنترنت (3 فرص × 10 دقائق)"** أعلى نسبة رضا بارتياح نفسي كبير لدى الطلاب وأولياء الأمور بنسبة **{{ $summary['feature_ratings']['time_freeze'] }} من 5**، نظراً لأنها توفق بنجاح بين الحراسة الصارمة والعدالة التقنية في ظروف انقطاع الشبكة.
            </p>
        </div>

        {{-- Section 2: Key Quantitative Indicators --}}
        <div>
            <h3 class="text-sm font-extrabold text-base-content mb-3">2. المؤشرات الكمية والمشاعر المحسوبة:</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="border border-base-300 p-3.5 rounded-xl text-center">
                    <span class="text-[11px] text-muted block font-semibold">إجمالي العينة المشاركة</span>
                    <span class="text-2xl font-black text-primary numeric">{{ $summary['total'] }}</span>
                </div>
                <div class="border border-base-300 p-3.5 rounded-xl text-center bg-success/5">
                    <span class="text-[11px] text-success block font-semibold">نسبة التأييد الإجمالية</span>
                    <span class="text-2xl font-black text-success numeric">{{ $summary['support_percentage'] }}%</span>
                </div>
                <div class="border border-base-300 p-3.5 rounded-xl text-center">
                    <span class="text-[11px] text-muted block font-semibold">المشاعر الإيجابية</span>
                    <span class="text-2xl font-black text-success numeric">{{ $summary['sentiment_counts']['positive'] }}</span>
                </div>
                <div class="border border-base-300 p-3.5 rounded-xl text-center">
                    <span class="text-[11px] text-muted block font-semibold">المشاعر السلبية/النقدية</span>
                    <span class="text-2xl font-black text-error numeric">{{ $summary['sentiment_counts']['negative'] }}</span>
                </div>
            </div>
        </div>

        {{-- Section 3: Feature Evaluation Table --}}
        <div>
            <h3 class="text-sm font-extrabold text-base-content mb-3">3. تقييم الميزات التقنية النوعية (من 5 درجات):</h3>
            <table class="table table-bordered w-full text-xs border border-base-300">
                <thead>
                    <tr class="bg-base-200">
                        <th>الميزة التقنية</th>
                        <th>الدرجة المحققة</th>
                        <th>التقييم النوعي والأثر</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-bold">تجميد الوقت عند انقطاع الإنترنت (Offline Freeze Lock)</td>
                        <td class="font-black text-primary numeric">{{ $summary['feature_ratings']['time_freeze'] }} / 5</td>
                        <td>إزالة التوتر وضمان تكافؤ الفرص مع منع قراءة الأسئلة أثناء الانقطاع.</td>
                    </tr>
                    <tr>
                        <td class="font-bold">المطابقة الحيوية للوجه وبصمة الملامح (Face Verification)</td>
                        <td class="font-black text-primary numeric">{{ $summary['feature_ratings']['face_match'] }} / 5</td>
                        <td>منع انتحال الهوية وتبديل الطالب قبل أو أثناء الاختبار.</td>
                    </tr>
                    <tr>
                        <td class="font-bold">حظر الخروج وتعتيم الشاشة (Blackout Lockout)</td>
                        <td class="font-black text-primary numeric">{{ $summary['feature_ratings']['security'] }} / 5</td>
                        <td>حماية المحتوى من النسخ أو النقل أو لقطات الشاشة.</td>
                    </tr>
                    <tr>
                        <td class="font-bold">سهولة الاستخدام وسرعة الاستجابة (UX & Performance)</td>
                        <td class="font-black text-primary numeric">{{ $summary['feature_ratings']['usability'] }} / 5</td>
                        <td>واجهة سلسة لا تؤثر على سرعة إجابة الطالب.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Section 4: Qualitative Keyword & Concept Extraction --}}
        <div>
            <h3 class="text-sm font-extrabold text-base-content mb-3">4. أبرز الكلمات المفتاحية ومفاهيم المشاركين المستخرجة:</h3>
            <div class="flex flex-wrap gap-2">
                @foreach ($summary['top_keywords'] as $keyword => $count)
                    <span class="badge badge-lg badge-outline py-2 px-3 text-xs font-bold">
                        {{ $keyword }} (تكرار: {{ $count }})
                    </span>
                @endforeach
            </div>
        </div>

        {{-- Document Footer --}}
        <div class="border-t border-base-300 pt-6 text-center text-xs text-muted space-y-1">
            <p>تم إعداد وتوليد هذا التقرير تلقائياً بواسطة وحدة التحليلات المتقدمة لمنصة {{ config('app.name') }}.</p>
            <p>© {{ date('Y') }} SWAT-IT & MSN-IT Solutions — جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
