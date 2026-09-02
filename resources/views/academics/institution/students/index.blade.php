<x-layouts.app title="الطلاب">
    <x-slot:header>
        <x-ui.page-header title="قائمة الطلاب وبصمات الوجه" description="سجلّ طلاب مؤسستك وحالة اعتماد بصمات الوجه الخاصة بهم." />
    </x-slot:header>

    <x-ui.card>
        <form method="GET" action="{{ route('institution.students.index') }}" class="mb-4 flex max-w-sm items-end gap-3">
            <div class="grow">
                <x-ui.input name="search" label="بحث" placeholder="الاسم أو اسم المستخدم أو البريد" :value="request('search')" icon="magnifying-glass" />
            </div>
            <button type="submit" class="btn btn-primary">بحث</button>
        </form>

        @if ($students->isEmpty())
            <x-ui.empty-state icon="academic-cap" title="لا يوجد طلاب" description="لم يتم العثور على طلاب مطابقين لبحثك." />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th class="text-start">الصورة الشخصية</th>
                            <th class="text-start">الطالب</th>
                            <th class="text-start">البريد الإلكتروني</th>
                            <th class="text-start">حالة البصمة الرقمية</th>
                            <th class="text-start">حالة الانضمام</th>
                            <th class="text-center">إجراءات الاعتماد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr>
                                <td>
                                    <div class="avatar">
                                        <div class="size-10 rounded-full border border-base-300 overflow-hidden bg-base-200">
                                            <img src="{{ $student->avatar_url }}" alt="{{ $student->official_name }}" class="object-cover w-full h-full" />
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-bold">{{ $student->official_name }}</div>
                                    <div class="text-xs text-base-content/60 numeric">{{ $student->username }}</div>
                                </td>
                                <td class="numeric">{{ $student->email }}</td>
                                <td>
                                    @if ($student->photo_status === 'approved')
                                        <span class="badge badge-success gap-1 text-[11px] font-bold">
                                            <x-heroicon-o-check-circle class="size-3.5" /> معتمدة
                                        </span>
                                    @elseif ($student->photo_status === 'pending')
                                        <span class="badge badge-warning gap-1 text-[11px] font-bold animate-pulse">
                                            <x-heroicon-o-clock class="size-3.5" /> بانتظار الاعتماد
                                        </span>
                                    @elseif ($student->photo_status === 'rejected')
                                        <span class="badge badge-error gap-1 text-[11px] font-bold">
                                            <x-heroicon-o-x-circle class="size-3.5" /> مرفوضة
                                        </span>
                                    @else
                                        <span class="badge badge-neutral gap-1 text-[11px] font-bold">
                                            غير مسجلة
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($student->is_enrolled)
                                        <x-ui.badge color="success" icon="check-circle">منضم</x-ui.badge>
                                    @else
                                        <x-ui.badge color="neutral" icon="minus-circle">غير منضم</x-ui.badge>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-2">
                                        @if ($student->photo_status !== 'approved')
                                            <form method="POST" action="{{ route('admin.students.approve-face', $student->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-success text-white font-bold gap-1">
                                                    <x-heroicon-o-check class="size-3.5" /> اعتماد البصمة
                                                </button>
                                            </form>
                                        @endif

                                        @if ($student->photo_status !== 'rejected')
                                            <form method="POST" action="{{ route('admin.students.reject-face', $student->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-error text-white font-bold gap-1">
                                                    <x-heroicon-o-x-mark class="size-3.5" /> رفض
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-base-300 mt-3 border-t pt-3">
                {{ $students->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
