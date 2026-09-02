{{-- System Test / Interactive Sandbox Selection Modal --}}
<dialog id="sandbox-hub-modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box max-w-4xl border border-primary/30 bg-base-100/95 p-6 shadow-2xl backdrop-blur-xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute left-4 top-4">✕</button>
        </form>

        <div class="text-center">
            <div class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-1.5 text-xs font-extrabold text-primary border border-primary/20">
                <x-heroicon-o-sparkles class="size-4 animate-spin" />
                <span>منصة التجربة الحية المعزولة (Live Interactive Sandbox)</span>
            </div>
            
            <h3 class="mt-3 text-2xl font-black lg:text-3xl">
                جرّب بيئة المنصة الذكية لمنع الغش وحماية الامتحانات
            </h3>
            
            <p class="muted mt-2 text-sm max-w-xl mx-auto">
                اختر الدور الذي ترغب في استكشافه لتجربة بيئة الاختبار الحقيقية بحساب تجريبي معزول لا يؤثر على أي بيانات رسمية:
            </p>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- 1. Student Sandbox Card --}}
            <a href="{{ route('sandbox.student.gateway') }}" 
               class="group relative flex flex-col justify-between rounded-2xl border-2 border-primary/30 bg-gradient-to-br from-primary/5 via-base-100 to-base-200 p-5 transition-all duration-300 hover:-translate-y-1 hover:border-primary hover:shadow-xl">
                <div class="absolute -top-3 right-4 rounded-full bg-primary px-3 py-0.5 text-[10px] font-black text-primary-content shadow-sm">
                    الأكثر تجربة 🔥
                </div>

                <div>
                    <div class="flex size-12 items-center justify-center rounded-xl bg-primary/15 text-primary group-hover:scale-110 transition-transform">
                        <x-heroicon-o-academic-cap class="size-7" />
                    </div>

                    <h4 class="mt-4 text-base font-extrabold text-base-content group-hover:text-primary">
                        تجربة الطالب (Student Test)
                    </h4>

                    <p class="muted mt-2 text-xs leading-relaxed">
                        امتحان فائق الحماية (15 سؤال / 7 دقائق)، مع خيار مطابقة الوجه بالكاميرا، وتجربة تجميد الوقت عند انقطاع الإنترنت (3 محاولات × 10د).
                    </p>

                    <div class="mt-3 flex flex-wrap gap-1">
                        <span class="badge badge-xs badge-info">مطابقة الوجه</span>
                        <span class="badge badge-xs badge-warning">تجميد الإنترنت</span>
                        <span class="badge badge-xs badge-error">حظر النسخ</span>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between border-t border-base-200 pt-3 text-xs font-bold text-primary">
                    <span>بدء تجربة الطالب الآن</span>
                    <span class="group-hover:translate-x-[-4px] transition-transform">&larr;</span>
                </div>
            </a>

            {{-- 2. Teacher Sandbox Card --}}
            <a href="{{ route('sandbox.teacher') }}" 
               class="group relative flex flex-col justify-between rounded-2xl border border-base-300 bg-gradient-to-br from-secondary/5 via-base-100 to-base-200 p-5 transition-all duration-300 hover:-translate-y-1 hover:border-secondary hover:shadow-xl">
                <div>
                    <div class="flex size-12 items-center justify-center rounded-xl bg-secondary/15 text-secondary group-hover:scale-110 transition-transform">
                        <x-heroicon-o-presentation-chart-bar class="size-7" />
                    </div>

                    <h4 class="mt-4 text-base font-extrabold text-base-content group-hover:text-secondary">
                        تجربة المعلّم (Teacher Sandbox)
                    </h4>

                    <p class="muted mt-2 text-xs leading-relaxed">
                        استكشف كيف يُنشئ المعلم امتحاناً ذكياً بالذكاء الاصطناعي، يضبط مستويات الحراسة، ويتابع مؤشرات النزاهة الرقمية المشفرة مع حماية خصوصية الطالب بنسبة 100%.
                    </p>

                    <div class="mt-3 flex flex-wrap gap-1">
                        <span class="badge badge-xs badge-secondary">توليد الذكاء الاصطناعي</span>
                        <span class="badge badge-xs badge-info">حماية الخصوصية 100%</span>
                        <span class="badge badge-xs badge-ghost">مؤشرات نزاهة</span>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between border-t border-base-200 pt-3 text-xs font-bold text-secondary">
                    <span>تجربة لوحة المعلم</span>
                    <span class="group-hover:translate-x-[-4px] transition-transform">&larr;</span>
                </div>
            </a>

            {{-- 3. Institution Sandbox Card --}}
            <a href="{{ route('sandbox.institution') }}" 
               class="group relative flex flex-col justify-between rounded-2xl border border-base-300 bg-gradient-to-br from-accent/5 via-base-100 to-base-200 p-5 transition-all duration-300 hover:-translate-y-1 hover:border-accent hover:shadow-xl">
                <div>
                    <div class="flex size-12 items-center justify-center rounded-xl bg-accent/15 text-accent group-hover:scale-110 transition-transform">
                        <x-heroicon-o-building-library class="size-7" />
                    </div>

                    <h4 class="mt-4 text-base font-extrabold text-base-content group-hover:text-accent">
                        تجربة المؤسسة (Institution Demo)
                    </h4>

                    <p class="muted mt-2 text-xs leading-relaxed">
                        لوحة تحكم كبرى لصناع القرار والعمداء: متابعة مؤشرات النزاهة الشاملة، تقارير الانتحال، وإحصائيات منع الغش على مستوى الكليات.
                    </p>

                    <div class="mt-3 flex flex-wrap gap-1">
                        <span class="badge badge-xs badge-accent">مؤشر النزاهة</span>
                        <span class="badge badge-xs badge-ghost">أمن الامتحانات</span>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between border-t border-base-200 pt-3 text-xs font-bold text-accent">
                    <span>لوحة إدارة المؤسسة</span>
                    <span class="group-hover:translate-x-[-4px] transition-transform">&larr;</span>
                </div>
            </a>
        </div>

        {{-- Community & Family Survey + Analytics Hub Banner --}}
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <a href="{{ route('sandbox.family') }}" 
               class="flex items-center gap-3 rounded-xl border border-warning/30 bg-warning/5 p-3.5 transition-all hover:bg-warning/10 hover:border-warning">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-warning/20 text-warning">
                    <x-heroicon-o-user-group class="size-5" />
                </span>
                <div class="text-right">
                    <h5 class="text-xs font-extrabold">استبيان العائلات وأولياء الأمور</h5>
                    <p class="muted text-[11px]">شاركنا رأيك في حماية نزاهة أبنائك ونظام تجميد انقطاع الإنترنت</p>
                </div>
            </a>

            <a href="{{ route('sandbox.surveys') }}" 
               class="flex items-center gap-3 rounded-xl border border-success/30 bg-success/5 p-3.5 transition-all hover:bg-success/10 hover:border-success">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success/20 text-success">
                    <x-heroicon-o-chart-bar-square class="size-5" />
                </span>
                <div class="text-right">
                    <h5 class="text-xs font-extrabold">مركز تحليل الاستبيانات والمشاعر (AI Hub)</h5>
                    <p class="muted text-[11px]">شاهد التحليل المباشر ونسبة التأييد وتوزيع المشاعر وتقارير النصوص</p>
                </div>
            </a>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>إغلاق</button>
    </form>
</dialog>
