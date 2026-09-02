@php use App\Modules\Academics\Enums\EnrollmentStatus; @endphp
<x-layouts.app title="صفوفي">
    <x-slot:header>
        <x-ui.page-header title="صفوفي" description="انضم إلى صف جديد برمز الانضمام الذي شاركه معلّمك." />
    </x-slot:header>

    <x-ui.card title="الانضمام برمز" icon="key" class="mb-4">
        <form method="POST" action="{{ route('student.classrooms.join') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="max-w-xs grow">
                <x-ui.input name="code" label="رمز الصف" placeholder="مثال: AB12CD" class="uppercase" />
            </div>
            <button type="submit" class="btn btn-primary">
                <x-heroicon-o-arrow-left-end-on-rectangle class="size-4" />
                انضمام
            </button>
        </form>
    </x-ui.card>

    <x-ui.card title="الصفوف المنضمّ إليها" icon="academic-cap" class="mb-4">
        @if ($approved->isEmpty())
            <x-ui.empty-state icon="academic-cap" title="لست منضمًا إلى أي صف" description="استخدم رمز الانضمام أعلاه للبدء." />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th class="text-start">الصف</th>
                            <th class="text-start">المعلّم</th>
                            <th class="text-start">رمز الصف</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($approved as $request)
                            <tr>
                                <td>{{ $request->classroom->name }}</td>
                                <td>{{ $request->classroom->teacher->official_name }}</td>
                                <td class="numeric">{{ $request->classroom->code }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="طلبات سابقة" icon="clock">
        @if ($other->isEmpty())
            <x-ui.empty-state icon="clock" title="لا توجد طلبات سابقة" description="ستظهر هنا طلبات الانضمام المعلّقة أو المرفوضة." />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th class="text-start">الصف</th>
                            <th class="text-start">الحالة</th>
                            <th class="text-start">ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($other as $request)
                            <tr>
                                <td>{{ $request->classroom->name }}</td>
                                <td>
                                    <x-ui.badge :color="$request->status->color()">{{ $request->status->label() }}</x-ui.badge>
                                </td>
                                <td class="muted text-xs">
                                    @if ($request->status === EnrollmentStatus::Rejected)
                                        {{ $request->rejection_reason ?? 'بدون سبب محدد' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
