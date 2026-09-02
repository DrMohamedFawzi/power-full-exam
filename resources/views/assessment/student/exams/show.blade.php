@php
    $monitors = $exam->security_level->monitors();
    $needsCamera = in_array('vision', $monitors, true);
    $needsMic = in_array('vision', $monitors, true);
@endphp

<x-layouts.app>
    <x-slot:header>
        <x-ui.page-header :title="$exam->title" description="اقرأ القواعد جيداً قبل بدء الاختبار."
            :breadcrumbs="['الاختبارات المتاحة' => route('student.exams.index'), $exam->title => null]" />
    </x-slot:header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-ui.card title="قواعد الاختبار" icon="document-text">
                <ul class="list-inside list-disc space-y-2 text-sm">
                    <li>المدة المسموح بها <span class="numeric">{{ $exam->duration_minutes }}</span> دقيقة، تُحسب من لحظة البدء ولا تتوقف عند إغلاق الصفحة.</li>
                    <li>عدد المحاولات المسموح بها: <span class="numeric">{{ $exam->max_attempts }}</span>.</li>
                    <li>مستوى المراقبة: <strong>{{ $exam->security_level->label() }}</strong>.</li>
                    @if ($exam->security_level->blocksOnViolation())
                        <li class="text-error">قد يُنهى الاختبار تلقائياً إذا انخفض مؤشر النزاهة كثيراً بسبب مخالفات متكررة.</li>
                    @endif
                    <li>لا تغادر نافذة الاختبار، ولا تفتح أدوات المطوّر، ولا تحاول النسخ أو اللصق.</li>
                    @if ($needsCamera)
                        <li>يتطلب هذا الاختبار الوصول إلى الكاميرا والميكروفون لمراقبة الحضور.</li>
                    @endif
                </ul>
            </x-ui.card>
        </div>

        <div class="flex flex-col gap-4">
            <x-ui.stat label="المدة" value="{{ $exam->duration_minutes }} د" icon="clock" color="info" />
            <x-ui.stat label="مستوى المراقبة" :value="$exam->security_level->label()" icon="shield-check"
                :color="$exam->security_level->color()" />

            <x-ui.card title="جاهز للبدء؟" icon="play">
                <form method="POST" action="{{ route('student.sessions.store', $exam) }}" x-data="examDeviceCheck">
                    @csrf
                    <input type="hidden" name="device_hash" x-model="deviceHash">
                    <input type="hidden" name="fingerprint[screen_resolution]" x-model="screenResolution">
                    <input type="hidden" name="fingerprint[timezone]" x-model="timezone">

                    <p class="muted mb-4 text-sm">بالضغط على "ابدأ الاختبار" فإنك تقر بقراءة القواعد أعلاه والموافقة عليها.</p>

                    <button type="submit" class="btn btn-primary w-full">ابدأ الاختبار</button>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
