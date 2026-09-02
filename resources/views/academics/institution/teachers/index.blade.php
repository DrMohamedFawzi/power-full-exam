<x-layouts.app title="المعلّمون">
    <x-slot:header>
        <x-ui.page-header title="المعلّمون" description="اعتمد المعلّمين الجدد قبل أن يتمكنوا من إنشاء الصفوف والاختبارات." />
    </x-slot:header>

    <x-ui.card padding="p-0">
        @if ($teachers->isEmpty())
            <x-ui.empty-state icon="users" title="لا يوجد معلّمون بعد" description="سيظهر هنا كل معلّم ينضم إلى مؤسستك." />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th class="text-start">المعلّم</th>
                            <th class="text-start">اسم المستخدم</th>
                            <th class="text-start numeric">عدد الصفوف</th>
                            <th class="text-start">الحالة</th>
                            <th class="text-start">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($teachers as $teacher)
                            <tr>
                                <td>{{ $teacher->official_name }}</td>
                                <td class="numeric">{{ $teacher->username }}</td>
                                <td class="numeric">{{ $teacher->classrooms_count }}</td>
                                <td>
                                    @if ($teacher->is_approved)
                                        <x-ui.badge color="success" icon="check-circle">معتمد</x-ui.badge>
                                    @else
                                        <x-ui.badge color="warning" icon="clock">بانتظار الاعتماد</x-ui.badge>
                                    @endif
                                </td>
                                <td>
                                    @if ($teacher->is_approved)
                                        <button
                                            type="button"
                                            class="btn btn-error btn-outline btn-xs"
                                            onclick="document.getElementById('revoke-{{ $teacher->id }}').showModal()"
                                        >
                                            إلغاء الاعتماد
                                        </button>

                                        <x-ui.modal id="revoke-{{ $teacher->id }}" title="إلغاء اعتماد المعلّم">
                                            <p>
                                                سيفقد <span class="font-bold">{{ $teacher->official_name }}</span>
                                                القدرة على إدارة صفوفه واختباراته حتى تتم إعادة اعتماده.
                                            </p>
                                            <x-slot:actions>
                                                <form method="POST" action="{{ route('institution.teachers.revoke', $teacher->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-error">تأكيد الإلغاء</button>
                                                </form>
                                            </x-slot:actions>
                                        </x-ui.modal>
                                    @else
                                        <form method="POST" action="{{ route('institution.teachers.approve', $teacher->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-xs">اعتماد</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-base-300 border-t px-5 py-3">
                {{ $teachers->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
