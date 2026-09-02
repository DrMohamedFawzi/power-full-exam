<x-layouts.app title="حالة الصورة الشخصية غير معتمدة">
    <div class="mx-auto max-w-xl py-12 px-4">
        <div class="card bg-base-100 shadow-xl border border-warning/30">
            <div class="card-body items-center text-center">
                <div class="w-16 h-16 rounded-full bg-warning/10 border border-warning/30 flex items-center justify-center mb-2">
                    <x-heroicon-o-exclamation-triangle class="size-8 text-warning" />
                </div>

                <h2 class="card-title text-xl font-bold text-warning">الصورة الشخصية غير معتمدة</h2>

                <p class="text-sm text-base-content/70 my-2 leading-relaxed">
                    عفواً، لا يمكنك دخول صفحة الامتحان حتى تتم مراجعة واعتماد البصمة الرقمية لوجهك من قبل إدارة المنصة.
                </p>

                <div class="alert alert-warning alert-soft my-3 text-xs w-full">
                    <span>حالة البصمة الحالية: <strong class="uppercase font-extrabold">{{ $user->photo_status ?? 'لم ترفع بعد' }}</strong></span>
                </div>

                <div class="card-actions justify-center gap-3 mt-4 w-full">
                    <a href="{{ route('student.profile.face') }}" class="btn btn-warning btn-block sm:w-auto">
                        <x-heroicon-o-camera class="size-5" />
                        التقاط/رفع البصمة الرقمية للوجه
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
