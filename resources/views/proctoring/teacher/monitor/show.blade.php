<x-layouts.app>
    <x-slot:header>
        <x-ui.page-header :title="$exam->title" description="تُحدَّث هذه الصفحة تلقائياً كل بضع ثوانٍ."
            :breadcrumbs="['المراقبة المباشرة' => route('teacher.monitor.index'), $exam->title => null]" />
    </x-slot:header>

    <div x-data="proctorMonitor('{{ route('teacher.monitor.data', $exam) }}')" x-init="init()" @unload.window="destroy()">
        <template x-if="!loading && sessions.length === 0">
            <x-ui.card>
                <x-ui.empty-state icon="eye" title="لا توجد جلسات جارية حالياً" />
            </x-ui.card>
        </template>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <template x-for="session in sessions" :key="session.id">
                <x-ui.card>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold" x-text="session.student_name"></p>
                            <p class="muted text-sm">
                                أجاب عن <span class="numeric" x-text="session.answered_count"></span> من
                                <span class="numeric" x-text="session.total_questions"></span>
                            </p>
                        </div>
                        <span class="badge badge-soft" :class="session.offline_seconds > 0 ? 'badge-warning' : 'badge-success'">
                            <span x-text="session.offline_seconds > 0 ? 'اتصال غير مستقر' : 'متصل'"></span>
                        </span>
                    </div>

                    <div class="mt-3 flex flex-col gap-1">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="muted">مؤشر النزاهة</span>
                            <span class="numeric" x-text="session.integrity_index + '%'"></span>
                        </div>
                        <div class="integrity-bar">
                            <span :class="session.integrity_index >= 85 ? 'bg-success' : (session.integrity_index >= 60 ? 'bg-warning' : 'bg-error')"
                                :style="'width: ' + session.integrity_index + '%'"></span>
                        </div>
                    </div>

                    <template x-if="session.recent_violations.length > 0">
                        <ul class="mt-3 flex flex-col gap-1 text-sm">
                            <template x-for="violation in session.recent_violations">
                                <li class="flex items-center justify-between">
                                    <span x-text="violation.label"></span>
                                    <span class="badge badge-soft badge-sm" :class="'badge-' + violation.severity.color" x-text="violation.severity.label"></span>
                                </li>
                            </template>
                        </ul>
                    </template>
                </x-ui.card>
            </template>
        </div>
    </div>
</x-layouts.app>
