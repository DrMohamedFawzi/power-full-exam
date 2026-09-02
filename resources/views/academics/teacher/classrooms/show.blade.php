<x-layouts.app :title="$classroom->name">
    <x-slot:header>
        <x-ui.page-header :title="$classroom->name" :description="$classroom->description">
            <x-slot:actions>
                <a href="{{ route('teacher.classrooms.edit', $classroom) }}" class="btn btn-ghost btn-sm">
                    <x-heroicon-o-pencil-square class="size-4" />
                    تعديل
                </a>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.stat label="رمز الانضمام" :value="$classroom->code" icon="key" color="primary" />
        <x-ui.stat label="عدد الطلاب المنضمين" :value="$classroom->students_count" icon="users" color="success" />
    </div>

    <x-ui.card title="طلبات الانضمام المعلّقة" icon="user-plus" class="mt-4">
        @if ($pending->isEmpty())
            <x-ui.empty-state icon="user-plus" title="لا توجد طلبات معلّقة" description="ستظهر هنا طلبات الطلاب فور تقديمها." />
        @else
            <div class="mb-4 flex justify-end">
                <button
                    type="button"
                    class="btn btn-success btn-sm"
                    onclick="document.getElementById('bulk-approve-modal').showModal()"
                >
                    <x-heroicon-o-check class="size-4" />
                    قبول الكل
                </button>

                <x-ui.modal id="bulk-approve-modal" title="قبول جميع الطلبات المعلّقة">
                    <p>سيتم قبول <span class="numeric font-bold">{{ $pending->count() }}</span> طلب انضمام معلّق لهذا الصف.</p>
                    <x-slot:actions>
                        <form method="POST" action="{{ route('teacher.enrollments.bulk-approve', $classroom) }}">
                            @csrf
                            <button type="submit" class="btn btn-success">تأكيد القبول</button>
                        </form>
                    </x-slot:actions>
                </x-ui.modal>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th class="text-start">الطالب</th>
                            <th class="text-start">اسم المستخدم</th>
                            <th class="text-start">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pending as $request)
                            <tr>
                                <td>{{ $request->student->official_name }}</td>
                                <td class="numeric">{{ $request->student->username }}</td>
                                <td>
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="الطلاب المنضمّون" icon="academic-cap" class="mt-4">
        @if ($approved->isEmpty())
            <x-ui.empty-state icon="academic-cap" title="لا يوجد طلاب منضمّون بعد" description="شارك رمز الصف مع طلابك ليتمكنوا من الانضمام." />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th class="text-start">الطالب</th>
                            <th class="text-start">اسم المستخدم</th>
                            <th class="text-start">البريد الإلكتروني</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($approved as $request)
                            <tr>
                                <td>{{ $request->student->official_name }}</td>
                                <td class="numeric">{{ $request->student->username }}</td>
                                <td class="numeric">{{ $request->student->email }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
