<x-layouts.app title="لوحة التحكم">
    <x-ui.page-header
        title="أهلاً، {{ auth()->user()->official_name }}"
        description="نظرة سريعة على صفوفك واختباراتك القادمة." />

    @if ($active_session)
        <div class="alert alert-warning alert-soft animate-in mt-6 items-start">
            <x-heroicon-o-clock class="size-6 shrink-0" />
            <div class="flex-1">
                <p class="font-bold">لديك اختبار قيد التنفيذ</p>
                <p class="text-sm">
                    {{ $active_session->exam->title }}
                    — <span class="numeric">{{ $active_session->exam->code }}</span>
                </p>
            </div>
            <a href="{{ route('student.sessions.show', $active_session) }}" class="btn btn-warning btn-sm">
                متابعة الاختبار
            </a>
        </div>
    @endif

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="صفوفي" :value="$stats['classrooms']" icon="academic-cap" color="primary" />
        <x-ui.stat label="طلبات قيد المراجعة" :value="$stats['pending_requests']" icon="clock" color="warning" />
        <x-ui.stat label="اختبارات مكتملة" :value="$stats['completed']" icon="check-circle" color="success" />
        <x-ui.stat label="أجهزتي الموثّقة" :value="$stats['devices']" icon="device-phone-mobile" color="info" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2" title="اختبارات متاحة" icon="clipboard-document-list">
            <x-slot:actions>
                <a href="{{ route('student.exams.index') }}" class="btn btn-ghost btn-sm">عرض الكل</a>
            </x-slot:actions>

            @forelse ($upcoming as $exam)
                <div class="border-base-300 flex flex-wrap items-center justify-between gap-3 border-b py-3 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $exam->title }}</p>
                        <p class="muted">
                            {{ $exam->classroom?->name ?? 'تدريب ذاتي' }}
                            · <span class="numeric">{{ $exam->duration_minutes }}</span> دقيقة
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-ui.badge :color="$exam->security_level->color()">
                            {{ $exam->security_level->label() }}
                        </x-ui.badge>
                        <a href="{{ route('student.exams.show', $exam) }}" class="btn btn-primary btn-sm">ابدأ</a>
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    icon="clipboard-document-list"
                    title="لا توجد اختبارات متاحة"
                    description="ستظهر الاختبارات هنا بمجرد أن ينشرها معلّمك." />
            @endforelse
        </x-ui.card>

        <div class="space-y-6">
            @if ($average_integrity !== null)
                <x-ui.card title="متوسط النزاهة" icon="shield-check">
                    <x-ui.integrity-meter :value="$average_integrity" />
                    <p class="muted mt-3">محسوب من جميع اختباراتك المُسلَّمة.</p>
                </x-ui.card>
            @endif

            <x-ui.card title="آخر النتائج" icon="chart-bar">
                <x-slot:actions>
                    <a href="{{ route('student.results.index') }}" class="btn btn-ghost btn-sm">الكل</a>
                </x-slot:actions>

                @forelse ($recent as $session)
                    <div class="border-base-300 flex items-center justify-between gap-3 border-b py-2.5 last:border-0">
                        <p class="min-w-0 flex-1 truncate text-sm font-semibold">{{ $session->exam->title }}</p>
                        <span class="numeric font-bold">{{ $session->score }}</span>
                    </div>
                @empty
                    <x-ui.empty-state
                        icon="chart-bar"
                        title="لا توجد نتائج بعد"
                        description="ستظهر نتائجك هنا بعد تسليم أول اختبار." />
                @endforelse
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
