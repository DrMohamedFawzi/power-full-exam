<x-layouts.app>
    <x-slot:header>
        <x-ui.page-header
            title="سجل التهديدات"
            description="كل عملية رصد قام بها جدار الحماية، قابلة للتصفية"
            :breadcrumbs="['مركز الرصد' => route('overwatch.dashboard'), 'التهديدات' => null]"
        />
    </x-slot:header>

    <x-ui.card title="التصفية" icon="funnel" class="mb-4 animate-in">
        <form method="GET" action="{{ route('overwatch.threats.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-ui.select
                name="attack_type"
                label="نوع الهجوم"
                placeholder="الكل"
                :options="collect(\App\Modules\Overwatch\Enums\AttackType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
                :selected="$filters['attack_type'] ?? null"
            />
            <x-ui.select
                name="severity"
                label="الخطورة"
                placeholder="الكل"
                :options="collect(\App\Support\Enums\Severity::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])"
                :selected="$filters['severity'] ?? null"
            />
            <x-ui.input name="ip_address" label="عنوان IP" :value="$filters['ip_address'] ?? null" />
            <x-ui.input type="date" name="date_from" label="من تاريخ" :value="$filters['date_from'] ?? null" />
            <x-ui.input type="date" name="date_to" label="إلى تاريخ" :value="$filters['date_to'] ?? null" />

            <div class="flex items-end gap-2 lg:col-span-5">
                <button type="submit" class="btn btn-primary btn-sm">تصفية</button>
                <a href="{{ route('overwatch.threats.index') }}" class="btn btn-ghost btn-sm">إعادة تعيين</a>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card title="النتائج" icon="bug-ant" class="animate-in">
        @if ($threats->isEmpty())
            <x-ui.empty-state icon="shield-check" title="لا توجد تهديدات مطابقة" description="جرّب تعديل معايير التصفية." />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>عنوان IP</th>
                            <th>نوع الهجوم</th>
                            <th>الخطورة</th>
                            <th>المسار</th>
                            <th>الحمولة</th>
                            <th>الوقت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($threats as $threat)
                            <tr>
                                <td class="numeric">{{ $threat['ip_address'] }}</td>
                                <td><x-ui.badge :color="$threat['attack_type_color']">{{ $threat['attack_type_label'] }}</x-ui.badge></td>
                                <td><x-ui.badge :color="$threat['severity_color']">{{ $threat['severity_label'] }}</x-ui.badge></td>
                                <td class="numeric muted text-xs">{{ $threat['request_path'] }}</td>
                                <td class="max-w-xs">
                                    {{-- Attacker-controlled by definition: escaped, never rendered as HTML. --}}
                                    @if ($threat['payload'])
                                        <code class="bg-base-200 block truncate rounded-lg px-2 py-1 text-xs" title="{{ $threat['payload'] }}">{{ Str::limit($threat['payload'], 80) }}</code>
                                    @else
                                        <span class="muted text-xs">—</span>
                                    @endif
                                </td>
                                <td class="numeric muted text-xs">{{ $threat['detected_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $threats->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.app>
