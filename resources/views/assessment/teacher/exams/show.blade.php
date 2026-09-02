<x-layouts.app :title="$exam['title']">
    <x-slot:header>
        <x-ui.page-header :title="$exam['title']" :breadcrumbs="['الاختبارات' => route('teacher.exams.index'), $exam['title'] => null]">
            <x-slot:actions>
                <x-ui.badge :color="$exam['status']->color()">{{ $exam['status']->label() }}</x-ui.badge>
                <a href="{{ route('teacher.exams.edit', $exam['id']) }}" class="btn btn-ghost btn-sm">تعديل</a>

                @if ($exam['is_draft'])
                    <button type="button" class="btn btn-primary btn-sm" onclick="publish_confirm.showModal()">نشر الاختبار</button>
                @elseif ($exam['is_published'])
                    <button type="button" class="btn btn-warning btn-sm" onclick="close_confirm.showModal()">إغلاق الاختبار</button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat label="رمز الاختبار" :value="$exam['code']" icon="hashtag" color="primary" />
        <x-ui.stat label="عدد الأسئلة" :value="$exam['questions_count']" icon="queue-list" color="secondary" />
        <x-ui.stat label="مدة الاختبار" :value="$exam['duration_minutes'].' د'" icon="clock" color="info" />
        <x-ui.stat label="عدد المحاولات" :value="$exam['max_attempts']" icon="arrow-path" color="success" />
    </div>

    <x-ui.card title="تفاصيل الاختبار" icon="information-circle">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="muted text-xs">الصف</dt>
                <dd class="font-semibold">{{ $exam['classroom_name'] ?? 'بلا صف محدد' }}</dd>
            </div>
            <div>
                <dt class="muted text-xs">نوع الاختبار</dt>
                <dd><x-ui.badge :color="$exam['mode']->color()">{{ $exam['mode']->label() }}</x-ui.badge></dd>
            </div>
            <div>
                <dt class="muted text-xs">مستوى المراقبة</dt>
                <dd><x-ui.badge :color="$exam['security_level']->color()">{{ $exam['security_level']->label() }}</x-ui.badge></dd>
            </div>
            <div>
                <dt class="muted text-xs">الوصف</dt>
                <dd>{{ $exam['description'] ?: 'لا يوجد وصف' }}</dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.modal id="publish_confirm" title="تأكيد نشر الاختبار">
        <p>سيصبح هذا الاختبار متاحاً للطلاب فور النشر، وستصبح الأسئلة غير قابلة للتعديل بمجرد بدء أول محاولة. هل تريد المتابعة؟</p>
        <x-slot:actions>
            <form method="POST" action="{{ route('teacher.exams.publish', $exam['id']) }}">
                @csrf
                <button type="submit" class="btn btn-primary">تأكيد النشر</button>
            </form>
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.modal id="close_confirm" title="تأكيد إغلاق الاختبار">
        <p>لن يتمكن أي طالب من بدء محاولة جديدة بعد إغلاق الاختبار. هل تريد المتابعة؟</p>
        <x-slot:actions>
            <form method="POST" action="{{ route('teacher.exams.close', $exam['id']) }}">
                @csrf
                <button type="submit" class="btn btn-warning">تأكيد الإغلاق</button>
            </form>
        </x-slot:actions>
    </x-ui.modal>
</x-layouts.app>
