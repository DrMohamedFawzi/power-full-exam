<x-layouts.app>
    <x-slot:header>
        <x-ui.page-header
            title="مركز الرصد"
            description="نظرة عامة على التهديدات الأمنية والحماية اللحظية للمنصة"
            :breadcrumbs="Route::has('institution.dashboard') ? ['الرئيسية' => route('institution.dashboard'), 'مركز الرصد' => null] : ['مركز الرصد' => null]"
        />
    </x-slot:header>

    <div class="grid animate-in grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat
            label="التهديدات اليوم"
            :value="$kpis['threats_today']"
            icon="bug-ant"
            color="warning"
        />
        <x-ui.stat
            label="عناوين IP المحظورة"
            :value="$kpis['blocked_ips']"
            icon="no-symbol"
            color="error"
        />
        <x-ui.stat
            label="أكثر أنواع الهجمات"
            :value="$kpis['top_attack_type']['label'] ?? '—'"
            icon="shield-exclamation"
            :color="$kpis['top_attack_type']['color'] ?? 'primary'"
            hint="خلال آخر 7 أيام"
        />
        <x-ui.stat
            label="طلبات مرفوضة اليوم"
            :value="$kpis['requests_rejected_today']"
            icon="hand-raised"
            color="info"
        />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-ui.card title="التهديدات عبر الزمن" icon="chart-bar" class="lg:col-span-2">
            <div
                x-data="overwatchThreatsChart({ labels: @js($chart['labels']), data: @js($chart['data']) })"
                x-init="init($refs.canvas)"
                wire:ignore
            >
                <canvas x-ref="canvas" height="110"></canvas>
            </div>
        </x-ui.card>

        <x-ui.card title="ملخص" icon="shield-check">
            <ul class="space-y-3 text-sm">
                <li class="flex items-center justify-between">
                    <span class="muted">إجمالي التهديدات (آخر 14 يوم)</span>
                    <span class="numeric font-bold">{{ array_sum($chart['data']) }}</span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="muted">عناوين محظورة حاليًا</span>
                    <span class="numeric font-bold">{{ $kpis['blocked_ips'] }}</span>
                </li>
            </ul>

            <div class="mt-4 flex flex-col gap-2">
                <a href="{{ route('overwatch.threats.index') }}" class="btn btn-outline btn-sm">عرض كل التهديدات</a>
                <a href="{{ route('overwatch.bans.index') }}" class="btn btn-outline btn-sm">إدارة الحظر</a>
            </div>
        </x-ui.card>
    </div>

    <div class="mt-6">
        <x-ui.card title="آخر التهديدات المرصودة" icon="clock">
            @if (empty($recent_threats))
                <x-ui.empty-state icon="shield-check" title="لا توجد تهديدات بعد" description="سيظهر هنا كل نشاط مشبوه يتم رصده." />
            @else
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>عنوان IP</th>
                                <th>نوع الهجوم</th>
                                <th>الخطورة</th>
                                <th>المسار</th>
                                <th>الوقت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent_threats as $threat)
                                <tr>
                                    <td class="numeric">{{ $threat['ip_address'] }}</td>
                                    <td><x-ui.badge :color="$threat['attack_type_color']">{{ $threat['attack_type_label'] }}</x-ui.badge></td>
                                    <td><x-ui.badge :color="$threat['severity_color']">{{ $threat['severity_label'] }}</x-ui.badge></td>
                                    <td class="numeric muted text-xs">{{ $threat['request_path'] }}</td>
                                    <td class="muted text-xs" title="{{ $threat['detected_at_full'] }}">{{ $threat['detected_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
