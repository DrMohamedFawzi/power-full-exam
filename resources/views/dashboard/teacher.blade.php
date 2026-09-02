<x-layouts.app title="لوحة التحكم">
    <x-ui.page-header
        title="أهلاً، {{ auth()->user()->official_name }}"
        description="متابعة صفوفك واختباراتك والجلسات الجارية الآن.">
        <x-slot:actions>
            <a href="{{ route('teacher.exams.create') }}" class="btn btn-primary btn-sm gap-2">
                <x-heroicon-o-plus class="size-4" /> اختبار جديد
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="الصفوف" :value="$stats['classrooms']" icon="academic-cap" color="primary" />
        <x-ui.stat label="الطلاب" :value="$stats['students']" icon="users" color="info" />
        <x-ui.stat
            label="طلبات انضمام معلّقة"
            :value="$stats['pending_enrollments']"
            icon="user-plus"
            :color="$stats['pending_enrollments'] > 0 ? 'warning' : 'success'" />
        <x-ui.stat
            label="جلسات جارية الآن"
            :value="$stats['live_now']"
            icon="eye"
            :color="$stats['live_now'] > 0 ? 'error' : 'success'" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2" title="الجلسات الجارية" icon="eye"
                   subtitle="مرتّبة تصاعدياً حسب مؤشر النزاهة">
            <x-slot:actions>
                <a href="{{ route('teacher.monitor.index') }}" class="btn btn-ghost btn-sm">المراقبة المباشرة</a>
            </x-slot:actions>

            @forelse ($live_sessions as $session)
                <div class="border-base-300 flex flex-wrap items-center gap-3 border-b py-3 last:border-0">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $session->student->official_name }}</p>
                        <p class="muted truncate">{{ $session->exam->title }}</p>
                    </div>
                    <div class="w-32 shrink-0">
                        <x-ui.integrity-meter :value="$session->integrity_index" :show-label="false" />
                    </div>
                    <span class="numeric w-12 shrink-0 text-sm font-bold">{{ $session->integrity_index }}%</span>
                </div>
            @empty
                <x-ui.empty-state
                    icon="eye"
                    title="لا توجد جلسات جارية"
                    description="ستظهر جلسات الطلاب هنا فور بدء اختبار منشور." />
            @endforelse
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="طلبات الانضمام" icon="user-plus">
                <x-slot:actions>
                    <a href="{{ route('teacher.enrollments.index') }}" class="btn btn-ghost btn-sm">الكل</a>
                </x-slot:actions>

                @forelse ($pending_enrollments as $enrollment)
                    <div class="border-base-300 border-b py-2.5 last:border-0">
                        <p class="truncate text-sm font-semibold">{{ $enrollment->student->official_name }}</p>
                        <p class="muted truncate">{{ $enrollment->classroom->name }}</p>
                    </div>
                @empty
                    <x-ui.empty-state icon="check-circle" title="لا توجد طلبات معلّقة" />
                @endforelse
            </x-ui.card>

            <x-ui.card title="آخر المخالفات" icon="exclamation-triangle">
                @forelse ($recent_violations as $violation)
                    <div class="border-base-300 flex items-center justify-between gap-2 border-b py-2.5 last:border-0">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ $violation->type->label() }}</p>
                            <p class="muted truncate">{{ $violation->student->official_name }}</p>
                        </div>
                        <x-ui.badge :color="$violation->severity->color()">
                            {{ $violation->severity->label() }}
                        </x-ui.badge>
                    </div>
                @empty
                    <x-ui.empty-state icon="shield-check" title="لا توجد مخالفات" />
                @endforelse
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
