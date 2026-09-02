@php use App\Modules\Academics\Enums\EnrollmentStatus; @endphp
<x-layouts.app title="طلبات الانضمام">
    <x-slot:header>
        <x-ui.page-header title="طلبات الانضمام" description="راجع طلبات الطلاب للانضمام إلى صفوفك." />
    </x-slot:header>

    <x-ui.card padding="p-0">
        @if ($enrollments->isEmpty())
            <x-ui.empty-state
                icon="user-plus"
                title="لا توجد طلبات انضمام"
                description="ستظهر هنا طلبات الطلاب فور تقديمها لأي من صفوفك."
            />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th class="text-start">الطالب</th>
                            <th class="text-start">الصف</th>
                            <th class="text-start">الحالة</th>
                            <th class="text-start">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enrollments as $request)
                            <tr>
                                <td>
                                    {{ $request->student->official_name }}
                                    <span class="muted numeric block text-xs">{{ $request->student->username }}</span>
                                </td>
                                <td>
                                    {{ $request->classroom->name }}
                                    <span class="muted numeric block text-xs">{{ $request->classroom->code }}</span>
                                </td>
                                <td>
                                    <x-ui.badge :color="$request->status->color()">{{ $request->status->label() }}</x-ui.badge>
                                    @if ($request->status === EnrollmentStatus::Rejected && $request->rejection_reason)
                                        <p class="muted mt-1 text-xs">{{ $request->rejection_reason }}</p>
                                    @endif
                                </td>
                                <td>
                                    @if ($request->status === EnrollmentStatus::Pending)
                                        <div class="flex items-center gap-2">
                                            <form method="POST" action="{{ route('teacher.enrollments.approve', $request) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-xs">قبول</button>
                                            </form>

                                            <button
                                                type="button"
                                                class="btn btn-error btn-outline btn-xs"
                                                onclick="document.getElementById('reject-{{ $request->id }}').showModal()"
                                            >
                                                رفض
                                            </button>

                                            <x-ui.modal id="reject-{{ $request->id }}" title="رفض طلب الانضمام">
                                                <form method="POST" action="{{ route('teacher.enrollments.reject', $request) }}" class="space-y-4">
                                                    @csrf
                                                    <x-ui.textarea name="reason" label="سبب الرفض (اختياري)" />
                                                    <div class="flex justify-end">
                                                        <button type="submit" class="btn btn-error">تأكيد الرفض</button>
                                                    </div>
                                                </form>
                                            </x-ui.modal>
                                        </div>
                                    @else
                                        <span class="muted text-xs">
                                            {{ $request->reviewed_at?->format('Y-m-d') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-base-300 border-t px-5 py-3">
                {{ $enrollments->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
