<x-layouts.app>
    <x-slot:header>
        <x-ui.page-header title="المراقبة المباشرة" description="الاختبارات التي لديها طلاب يؤدونها الآن." />
    </x-slot:header>

    <x-ui.card>
        @if (empty($exams))
            <x-ui.empty-state icon="eye" title="لا توجد جلسات جارية"
                description="بمجرد أن يبدأ أحد طلابك اختباراً، سيظهر هنا." />
        @else
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($exams as $exam)
                    <a href="{{ route('teacher.monitor.show', $exam['id']) }}"
                        class="border-base-300 hover:border-primary flex items-center justify-between rounded-box border p-4">
                        <div>
                            <p class="font-semibold">{{ $exam['title'] }}</p>
                            <x-ui.badge :color="$exam['security_level']['color']" class="mt-1">{{ $exam['security_level']['label'] }}</x-ui.badge>
                        </div>
                        <x-ui.badge color="info" icon="user-group">{{ $exam['active_sessions_count'] }} طالب</x-ui.badge>
                    </a>
                @endforeach
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
