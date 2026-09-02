<x-layouts.app title="لوحة التحكم">
    <x-ui.page-header
        title="لوحة المؤسسة"
        description="نظرة شاملة على المعلّمين والطلاب والاختبارات وحالة الأمن." />

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="المعلّمون" :value="$stats['teachers']" icon="users" color="primary"
                   :hint="$stats['pending_teachers'].' بانتظار الاعتماد'" />
        <x-ui.stat label="الطلاب" :value="$stats['students']" icon="academic-cap" color="info" />
        <x-ui.stat label="الصفوف" :value="$stats['classrooms']" icon="rectangle-group" color="secondary" />
        <x-ui.stat label="الاختبارات" :value="$stats['exams']" icon="clipboard-document-list" color="success" />
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <x-ui.stat label="تهديدات اليوم" :value="$stats['threats_today']" icon="bug-ant"
                   :color="$stats['threats_today'] > 0 ? 'error' : 'success'" />
        <x-ui.stat label="عناوين محظورة" :value="$stats['active_bans']" icon="no-symbol"
                   :color="$stats['active_bans'] > 0 ? 'warning' : 'success'" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-ui.card title="معلّمون بانتظار الاعتماد" icon="user-plus">
            <x-slot:actions>
                <a href="{{ route('institution.teachers.index') }}" class="btn btn-ghost btn-sm">إدارة المعلّمين</a>
            </x-slot:actions>

            @forelse ($pending_teachers as $teacher)
                <div class="border-base-300 flex items-center justify-between gap-3 border-b py-3 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $teacher->official_name }}</p>
                        <p class="muted numeric truncate">{{ $teacher->email }}</p>
                    </div>
                    <x-ui.badge color="warning">قيد الاعتماد</x-ui.badge>
                </div>
            @empty
                <x-ui.empty-state
                    icon="check-circle"
                    title="لا توجد طلبات اعتماد"
                    description="جميع المعلّمين في مؤسستك معتمدون." />
            @endforelse
        </x-ui.card>

        <x-ui.card title="جلسات مشبوهة" icon="shield-exclamation"
                   subtitle="مؤشر نزاهتها أقل من الحد المسموح">
            @forelse ($flagged_sessions as $session)
                <div class="border-base-300 flex items-center gap-3 border-b py-3 last:border-0">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $session->student->official_name }}</p>
                        <p class="muted truncate">{{ $session->exam->title }}</p>
                    </div>
                    <div class="w-24 shrink-0">
                        <x-ui.integrity-meter :value="$session->integrity_index" :show-label="false" />
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    icon="shield-check"
                    title="لا توجد جلسات مشبوهة"
                    description="جميع الاختبارات المُسلَّمة ضمن الحدود الطبيعية." />
            @endforelse
        </x-ui.card>

        <x-ui.card title="أكثر الصفوف نشاطاً" icon="rectangle-group">
            @forelse ($busiest_classrooms as $classroom)
                <div class="border-base-300 flex items-center justify-between gap-3 border-b py-3 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $classroom->name }}</p>
                        <p class="muted truncate">{{ $classroom->teacher->official_name }}</p>
                    </div>
                    <span class="numeric shrink-0 text-sm font-bold">{{ $classroom->students_count }}</span>
                </div>
            @empty
                <x-ui.empty-state icon="rectangle-group" title="لا توجد صفوف بعد" />
            @endforelse
        </x-ui.card>

        <x-ui.card title="آخر التهديدات" icon="bug-ant">
            <x-slot:actions>
                <a href="{{ route('overwatch.dashboard') }}" class="btn btn-ghost btn-sm">مركز الرصد</a>
            </x-slot:actions>

            @forelse ($recent_threats as $threat)
                <div class="border-base-300 flex items-center justify-between gap-3 border-b py-3 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $threat->attack_type->label() }}</p>
                        <p class="muted numeric truncate">{{ $threat->ip_address }}</p>
                    </div>
                    <x-ui.badge :color="$threat->severity->color()">{{ $threat->severity->label() }}</x-ui.badge>
                </div>
            @empty
                <x-ui.empty-state icon="shield-check" title="لا توجد تهديدات مسجّلة" />
            @endforelse
        </x-ui.card>
    </div>
</x-layouts.app>
