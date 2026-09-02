<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="aegis">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مركز تحليل الاستبيانات والمشاعر (AI Sentiment Hub) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="from-base-200 via-base-100 to-base-200 min-h-screen bg-gradient-to-bl p-4 lg:p-8">
    <div class="mx-auto max-w-6xl" x-data="{
        roleFilter: 'all',
        sentimentFilter: 'all',
        searchQuery: '',
    }">
        {{-- Navigation Header --}}
        <header class="flex items-center justify-between pb-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <x-ui.logo class="size-8" />
                <span class="text-base font-extrabold">{{ config('app.name') }}</span>
                <span class="badge badge-success badge-sm font-bold">مركز تحليل الاستبيانات</span>
            </a>

            <div class="flex items-center gap-2">
                <x-ui.theme-toggle />
                <a href="{{ route('sandbox.surveys.export') }}" target="_blank" class="btn btn-outline btn-sm font-bold gap-1">
                    <x-heroicon-o-document-text class="size-4" />
                    <span>تصدير ومعاينة التقرير الكامل</span>
                </a>
                <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">الرئيسية</a>
            </div>
        </header>

        {{-- Main Banner --}}
        <div class="rounded-3xl border border-success/30 bg-gradient-to-r from-success/10 via-base-100 to-base-100 p-6 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="badge badge-success font-bold">ذكاء اصطناعي وتحليل نصوص</span>
                    <span class="badge badge-ghost text-xs">تحديث فوري مباشر</span>
                </div>
                <h1 class="mt-2 text-2xl font-black lg:text-3xl text-base-content">
                    لوحة تحليلات الاستبيانات وقياس مشاعر المجتمع (Sentiment Analysis)
                </h1>
                <p class="muted text-xs mt-1 max-w-2xl leading-relaxed">
                    تحليل شامل لردود وانطباعات الطلاب، المعلمين، المؤسسات، وأولياء الأمور حول نظام منع الغش، التحقق البصري، وميزة تجميد الوقت عند انقطاع الإنترنت.
                </p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('sandbox.student.gateway') }}" class="btn btn-primary btn-sm font-black">
                    <x-heroicon-o-play class="size-4" />
                    <span>تجربة الامتحان كطالب</span>
                </a>
            </div>
        </div>

        {{-- Section 1: KPI Stats Grid --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="card bg-base-100 border border-base-300 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="muted text-xs font-bold">إجمالي الاستبيانات</span>
                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <x-heroicon-o-chat-bubble-bottom-center-text class="size-5" />
                    </span>
                </div>
                <p class="text-3xl font-black text-base-content mt-2 numeric">{{ $summary['total'] }}</p>
                <span class="text-[11px] text-muted mt-1 block">استبيان محلل بنجاح</span>
            </div>

            <div class="card bg-base-100 border border-success/30 bg-success/5 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="muted text-xs font-bold text-success">نسبة التأييد للفكرة</span>
                    <span class="flex size-8 items-center justify-center rounded-lg bg-success/20 text-success">
                        <x-heroicon-o-hand-thumb-up class="size-5" />
                    </span>
                </div>
                <p class="text-3xl font-black text-success mt-2 numeric">{{ $summary['support_percentage'] }}%</p>
                <span class="text-[11px] text-success font-bold mt-1 block">تأييد ساحق لنظام الحماية</span>
            </div>

            <div class="card bg-base-100 border border-base-300 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="muted text-xs font-bold">متوسط التقييم العام</span>
                    <span class="flex size-8 items-center justify-center rounded-lg bg-warning/10 text-warning">
                        <x-heroicon-o-star class="size-5" />
                    </span>
                </div>
                <p class="text-3xl font-black text-warning mt-2 numeric">{{ $summary['avg_rating'] }} / 5</p>
                <span class="text-[11px] text-muted mt-1 block">رضا استثنائي عن الواجهة</span>
            </div>

            <div class="card bg-base-100 border border-base-300 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="muted text-xs font-bold">تجميد انقطاع الإنترنت</span>
                    <span class="flex size-8 items-center justify-center rounded-lg bg-info/10 text-info">
                        <x-heroicon-o-wifi class="size-5" />
                    </span>
                </div>
                <p class="text-3xl font-black text-info mt-2 numeric">{{ $summary['feature_ratings']['time_freeze'] }} / 5</p>
                <span class="text-[11px] text-info font-bold mt-1 block">أعلى ميزة نالت الاستحسان</span>
            </div>
        </div>

        {{-- Section 2: Sentiment Distribution & Feature Satisfaction --}}
        <div class="grid gap-6 md:grid-cols-2 mb-6">
            {{-- Sentiment Breakdown Card --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5">
                    <h3 class="font-extrabold text-sm flex items-center justify-between text-base-content border-b border-base-200 pb-3">
                        <span>توزيع المشاعر والنصوص (Sentiment Breakdown)</span>
                        <span class="badge badge-xs badge-info">NLP Analysis</span>
                    </h3>

                    <div class="mt-4 space-y-4">
                        <div>
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-success flex items-center gap-1">
                                    <span>😊 مشاعر إيجابية ومؤيدة (Positive):</span>
                                </span>
                                <span class="numeric">{{ $summary['sentiment_counts']['positive'] }} ردود</span>
                            </div>
                            <div class="w-full bg-base-200 h-3 rounded-full overflow-hidden">
                                <div class="bg-success h-full" style="width: {{ $summary['total'] > 0 ? ($summary['sentiment_counts']['positive'] / $summary['total']) * 100 : 0 }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-neutral-content flex items-center gap-1">
                                    <span>😐 محايد / استفسارات (Neutral):</span>
                                </span>
                                <span class="numeric">{{ $summary['sentiment_counts']['neutral'] }} ردود</span>
                            </div>
                            <div class="w-full bg-base-200 h-3 rounded-full overflow-hidden">
                                <div class="bg-neutral h-full" style="width: {{ $summary['total'] > 0 ? ($summary['sentiment_counts']['neutral'] / $summary['total']) * 100 : 0 }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-error flex items-center gap-1">
                                    <span>🙁 معارض / ملاحظات نقدية (Negative):</span>
                                </span>
                                <span class="numeric">{{ $summary['sentiment_counts']['negative'] }} ردود</span>
                            </div>
                            <div class="w-full bg-base-200 h-3 rounded-full overflow-hidden">
                                <div class="bg-error h-full" style="width: {{ $summary['total'] > 0 ? ($summary['sentiment_counts']['negative'] / $summary['total']) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Role breakdown chips --}}
                    <div class="mt-5 border-t border-base-200 pt-3 flex flex-wrap gap-2 text-xs">
                        <span class="badge badge-primary font-bold">الطلاب: {{ $summary['role_counts']['student'] }}</span>
                        <span class="badge badge-secondary font-bold">المعلمون: {{ $summary['role_counts']['teacher'] }}</span>
                        <span class="badge badge-accent font-bold">المؤسسات: {{ $summary['role_counts']['institution'] }}</span>
                        <span class="badge badge-warning font-bold">العائلات: {{ $summary['role_counts']['family'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Feature Satisfaction Radar & Keywords --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5">
                    <h3 class="font-extrabold text-sm flex items-center justify-between text-base-content border-b border-base-200 pb-3">
                        <span>الكلمات المفتاحية والاهتمامات (Top Keywords & Topics)</span>
                        <span class="badge badge-xs badge-secondary">استخراج المفاهيم</span>
                    </h3>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($summary['top_keywords'] as $keyword => $count)
                            <div class="badge badge-outline badge-lg gap-1.5 py-3 px-3.5 hover:badge-primary transition-all cursor-default">
                                <span class="font-black text-xs text-base-content">{{ $keyword }}</span>
                                <span class="badge badge-xs badge-neutral numeric font-bold">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 border-t border-base-200 pt-3">
                        <h4 class="text-xs font-bold text-muted mb-2">تقييم الميزات الحيوية (من 5 نجوم):</h4>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="bg-base-200/60 p-2.5 rounded-xl flex items-center justify-between">
                                <span>🛡️ دقة الحماية:</span>
                                <span class="font-black text-primary numeric">{{ $summary['feature_ratings']['security'] }} ★</span>
                            </div>
                            <div class="bg-base-200/60 p-2.5 rounded-xl flex items-center justify-between">
                                <span>📷 مطابقة الوجه:</span>
                                <span class="font-black text-primary numeric">{{ $summary['feature_ratings']['face_match'] }} ★</span>
                            </div>
                            <div class="bg-base-200/60 p-2.5 rounded-xl flex items-center justify-between">
                                <span>🔌 تجميد الإنترنت:</span>
                                <span class="font-black text-warning numeric">{{ $summary['feature_ratings']['time_freeze'] }} ★</span>
                            </div>
                            <div class="bg-base-200/60 p-2.5 rounded-xl flex items-center justify-between">
                                <span>⚡ سرعة الواجهة:</span>
                                <span class="font-black text-success numeric">{{ $summary['feature_ratings']['usability'] }} ★</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Detailed Survey Table --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden mb-8">
            <div class="card-body p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-base-200 pb-4">
                    <div>
                        <h3 class="font-extrabold text-base text-base-content">
                            سجل الاستبيانات والآراء المحللة (Surveys Feed)
                        </h3>
                        <p class="muted text-xs mt-0.5">يمكنك تصفح وتحليل ردود الطلاب والمعلمين وصناع القرار</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('sandbox.surveys.export') }}" target="_blank" class="btn btn-xs btn-primary font-bold gap-1">
                            <x-heroicon-o-printer class="size-3.5" />
                            <span>تقرير كامل PDF / الطباعة</span>
                        </a>
                        <a href="{{ route('sandbox.surveys.export', ['format' => 'json']) }}" target="_blank" class="btn btn-xs btn-ghost border border-base-300 font-bold gap-1">
                            <span>JSON Data</span>
                        </a>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto mt-3">
                    <table class="table table-zebra w-full text-xs">
                        <thead>
                            <tr class="text-muted border-b border-base-200">
                                <th>الفئة والدور</th>
                                <th>الاسم / الجهة</th>
                                <th>الموقف من الفكرة</th>
                                <th>المشاعر المحللة</th>
                                <th>التعليق والملاحظات</th>
                                <th>الكلمات المستخرجة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summary['recent_surveys'] as $survey)
                                <tr class="hover:bg-base-200/50">
                                    <td>
                                        <span class="badge badge-sm font-bold"
                                              :class="{
                                                  'badge-primary': '{{ $survey->role }}' === 'student',
                                                  'badge-secondary': '{{ $survey->role }}' === 'teacher',
                                                  'badge-accent': '{{ $survey->role }}' === 'institution',
                                                  'badge-warning': '{{ $survey->role }}' === 'family'
                                              }">
                                            {{ $survey->roleLabelAr() }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-bold text-base-content">{{ $survey->name ?? 'مشارك مجهول' }}</div>
                                        <div class="text-[10px] text-muted">{{ $survey->organization ?? 'عام' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-xs font-bold {{ $survey->support_anti_cheat === 'strongly_support' ? 'badge-success text-white' : ($survey->support_anti_cheat === 'support' ? 'badge-info' : 'badge-neutral') }}">
                                            {{ $survey->supportLabelAr() }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-1.5">
                                            <span class="size-2 rounded-full {{ $survey->isPositive() ? 'bg-success' : ($survey->isNegative() ? 'bg-error' : 'bg-neutral') }}"></span>
                                            <span class="font-bold {{ $survey->isPositive() ? 'text-success' : ($survey->isNegative() ? 'text-error' : 'text-neutral-content') }}">
                                                {{ $survey->isPositive() ? 'إيجابي' : ($survey->isNegative() ? 'سلبي' : 'محايد') }}
                                            </span>
                                            <span class="text-[10px] numeric font-semibold text-muted">({{ $survey->sentiment_score > 0 ? '+' : '' }}{{ $survey->sentiment_score }})</span>
                                        </div>
                                    </td>
                                    <td class="max-w-xs">
                                        <p class="truncate font-medium text-base-content/90" title="{{ $survey->feedback_text }}">
                                            {{ $survey->feedback_text ?? '—' }}
                                        </p>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap gap-1">
                                            @if ($survey->detected_keywords && is_array($survey->detected_keywords))
                                                @foreach (array_slice($survey->detected_keywords, 0, 2) as $kw)
                                                    <span class="badge badge-ghost badge-xs text-[10px]">{{ $kw }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted text-[10px]">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-muted text-[10px] numeric">
                                        {{ $survey->created_at ? $survey->created_at->format('Y-m-d H:i') : now()->format('Y-m-d') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
