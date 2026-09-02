<x-layouts.app title="الصفوف">
    <x-slot:header>
        <x-ui.page-header
            title="الصفوف"
            description="أنشئ صفوفًا وشارك رمز الانضمام مع طلابك."
        >
            <x-slot:actions>
                <a href="{{ route('teacher.classrooms.create') }}" class="btn btn-primary btn-sm">
                    <x-heroicon-o-plus class="size-4" />
                    صف جديد
                </a>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <x-ui.card padding="p-0">
        @if ($classrooms->isEmpty())
            <x-ui.empty-state
                icon="academic-cap"
                title="لا توجد صفوف بعد"
                description="ابدأ بإنشاء أول صف لك ليتمكن الطلاب من الانضمام إليه برمز الصف."
            >
                <x-slot:action>
                    <a href="{{ route('teacher.classrooms.create') }}" class="btn btn-primary btn-sm">إنشاء صف</a>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th class="text-start">الصف</th>
                            <th class="text-start">رمز الانضمام</th>
                            <th class="text-start numeric">الطلاب</th>
                            <th class="text-start numeric">طلبات معلّقة</th>
                            <th class="text-start">الحالة</th>
                            <th class="text-start">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($classrooms as $classroom)
                            <tr>
                                <td>
                                    <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="link link-hover font-semibold">
                                        {{ $classroom->name }}
                                    </a>
                                </td>
                                <td class="numeric">{{ $classroom->code }}</td>
                                <td class="numeric">{{ $classroom->students_count }}</td>
                                <td class="numeric">{{ $classroom->pending_count }}</td>
                                <td>
                                    @if ($classroom->is_archived)
                                        <x-ui.badge color="neutral" icon="archive-box">مؤرشف</x-ui.badge>
                                    @else
                                        <x-ui.badge color="success" icon="check-circle">نشط</x-ui.badge>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('teacher.classrooms.edit', $classroom) }}" class="btn btn-ghost btn-xs" aria-label="تعديل الصف">
                                            <x-heroicon-o-pencil-square class="size-4" />
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-xs text-error"
                                            aria-label="حذف الصف"
                                            onclick="document.getElementById('delete-classroom-{{ $classroom->id }}').showModal()"
                                        >
                                            <x-heroicon-o-trash class="size-4" />
                                        </button>

                                        <x-ui.modal id="delete-classroom-{{ $classroom->id }}" title="حذف الصف">
                                            <p>
                                                هل أنت متأكد من حذف صف
                                                <span class="font-bold">{{ $classroom->name }}</span>؟
                                                إن كان له اختبارات مرتبطة سيتم أرشفته بدلًا من الحذف.
                                            </p>
                                            <x-slot:actions>
                                                <form method="POST" action="{{ route('teacher.classrooms.destroy', $classroom) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-error">تأكيد الحذف</button>
                                                </form>
                                            </x-slot:actions>
                                        </x-ui.modal>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-base-300 border-t px-5 py-3">
                {{ $classrooms->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
