<x-layouts.app>
    <x-slot:header>
        <x-ui.page-header title="الاختبارات المتاحة" description="اختباراتك الرسمية والتدريبية المتاحة حالياً." />
    </x-slot:header>

    @if (empty($exams))
        <x-ui.card>
            <x-ui.empty-state icon="clipboard-document-list" title="لا توجد اختبارات متاحة الآن"
                description="سيظهر هنا كل اختبار ينشره معلّمك بعد قبول انضمامك للصف، أو أي اختبار تدريب ذاتي تنشئه." />
        </x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ($exams as $exam)
                <x-ui.card :title="$exam['title']" icon="clipboard-document-list">
                    <x-slot:actions>
                        <x-ui.badge :color="$exam['security_level']['color']" icon="shield-check">
                            {{ $exam['security_level']['label'] }}
                        </x-ui.badge>
                    </x-slot:actions>

                    <p class="muted">{{ $exam['description'] }}</p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <x-ui.badge :color="$exam['mode']['color']">{{ $exam['mode']['label'] }}</x-ui.badge>
                        @if ($exam['classroom_name'])
                            <x-ui.badge color="neutral" icon="academic-cap">{{ $exam['classroom_name'] }}</x-ui.badge>
                        @endif
                        <span class="muted text-sm">
                            المدة: <span class="numeric">{{ $exam['duration_minutes'] }}</span> دقيقة
                        </span>
                        <span class="muted text-sm">
                            المحاولات: <span class="numeric">{{ $exam['attempts_used'] }}/{{ $exam['max_attempts'] }}</span>
                        </span>
                    </div>

                    <x-slot:footer>
                        @if ($exam['active_session_id'])
                            <a href="{{ route('student.sessions.show', $exam['active_session_id']) }}" class="btn btn-primary btn-sm">
                                متابعة الجلسة الجارية
                            </a>
                        @elseif ($exam['can_start'])
                            <a href="{{ route('student.exams.show', $exam['id']) }}" class="btn btn-primary btn-sm">
                                عرض التفاصيل والبدء
                            </a>
                        @else
                            <span class="muted text-sm">تم استنفاد عدد المحاولات المسموح بها</span>
                        @endif
                    </x-slot:footer>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</x-layouts.app>
