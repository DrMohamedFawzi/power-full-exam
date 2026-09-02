<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="aegis">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — منصة الاختبارات الآمنة</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="from-base-200 via-base-100 to-base-200 min-h-screen bg-gradient-to-bl">
    <div class="flex min-h-screen flex-col">
        <header class="container mx-auto flex items-center justify-between p-4 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <x-ui.logo class="size-9" />
                <span class="text-lg font-extrabold">{{ config('app.name') }}</span>
            </a>

            <div class="flex items-center gap-2">
                <x-ui.theme-toggle />
                <button type="button" onclick="document.getElementById('sandbox-hub-modal').showModal()" class="btn btn-warning btn-sm font-black gap-1.5 shadow-sm border border-warning/30">
                    <x-heroicon-o-sparkles class="size-4 animate-spin" />
                    <span>اختبار النظام</span>
                </button>
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">تسجيل الدخول</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">إنشاء حساب</a>
            </div>
        </header>

        <main class="container mx-auto flex-1 p-4 lg:px-8">
            <x-ui.flash />

            <section class="animate-in mx-auto max-w-3xl py-10 text-center">
                <x-ui.badge color="info" icon="shield-check">حماية ذكية ثنائية · منصة اختبارات موثوقة بلا تسريب</x-ui.badge>

                <h1 class="mt-6 text-3xl font-extrabold lg:text-5xl">
                    اختبارات إلكترونية آمنة ونزيهة، من التسجيل حتى النتيجة
                </h1>

                <p class="muted mt-4 text-base lg:text-lg">
                    {{ config('app.name') }} تجمع بين إدارة الصفوف والاختبارات ومراقبة سلوك الطالب أثناء الامتحان،
                    عبر بصمة جهاز رقمية ومؤشر نزاهة يُحسب فورياً من كل حدث مرصود.
                </p>

                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row items-center">
                    <button type="button" onclick="document.getElementById('sandbox-hub-modal').showModal()" class="btn btn-warning btn-lg font-black gap-2 shadow-lg shadow-warning/20 hover:scale-105 transition-all">
                        <x-heroicon-o-sparkles class="size-6" />
                        <span>🧪 اختبار النظام (تجربة تفاعلية حيّة)</span>
                    </button>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg">ابدأ الآن بتسجيل الدخول</a>
                    <a href="{{ route('register') }}" class="btn btn-outline btn-lg">أنشئ حساب معلّم أو مؤسسة</a>
                </div>
            </section>

            <section class="mx-auto grid max-w-5xl grid-cols-2 gap-4 py-6 lg:grid-cols-4">
                <x-ui.stat label="جلسات محمية" value="100%" icon="lock-closed" color="success" />
                <x-ui.stat label="مؤشر نزاهة فوري" value="حي" icon="chart-bar" color="primary" />
                <x-ui.stat label="بصمة جهاز فريدة" value="لكل طالب" icon="finger-print" color="info" />
                <x-ui.stat label="تنبيهات مباشرة" value="أثناء الاختبار" icon="bell-alert" color="warning" />
            </section>

            <section class="mx-auto grid max-w-5xl gap-6 py-10 md:grid-cols-3">
                <x-ui.card title="بوابة الطلاب" icon="academic-cap">
                    <p class="muted">
                        انضم إلى صفوفك الدراسية، وسجّل الدخول بكلمة المرور أو برمز QR من جهاز موثوق،
                        وأدِّ اختباراتك في بيئة مراقبة تحافظ على نزاهتك.
                    </p>
                </x-ui.card>

                <x-ui.card title="منصة المعلّمين" icon="presentation-chart-bar">
                    <p class="muted">
                        أنشئ الصفوف، اعتمد طلبات الانضمام، صمّم الاختبارات يدوياً أو بمساعدة الذكاء الاصطناعي،
                        وتابع مؤشرات النزاهة لكل جلسة لحظة بلحظة.
                    </p>
                </x-ui.card>

                <x-ui.card title="إدارة المؤسسة" icon="building-library">
                    <p class="muted">
                        فعّل حسابات المعلّمين الجدد، أشرف على الصفوف والاختبارات على مستوى المؤسسة،
                        وراقب التهديدات الأمنية من مركز رصد واحد.
                    </p>
                </x-ui.card>
            </section>

            <x-ui.card
                title="كيف تضمن المنصة نزاهة الاختبار؟"
                icon="shield-check"
                class="mx-auto mb-12 max-w-5xl"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex items-start gap-3">
                        <x-heroicon-o-finger-print class="text-primary size-6 shrink-0" />
                        <div>
                            <h3 class="font-bold">بصمة الجهاز والمتصفح</h3>
                            <p class="muted text-sm">منع انتحال الهوية أو تبديل الجهاز أثناء الجلسة.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <x-heroicon-o-eye class="text-primary size-6 shrink-0" />
                        <div>
                            <h3 class="font-bold">مراقبة سلوك حيّة</h3>
                            <p class="muted text-sm">رصد الخروج من نافذة الاختبار أو فقدان الاتصال فوراً.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <x-heroicon-o-calculator class="text-primary size-6 shrink-0" />
                        <div>
                            <h3 class="font-bold">مؤشر نزاهة مُشتق</h3>
                            <p class="muted text-sm">يُعاد احتسابه من كامل سجل الأحداث، فهو قابل للتفسير دائماً.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <x-heroicon-o-queue-list class="text-primary size-6 shrink-0" />
                        <div>
                            <h3 class="font-bold">معالجة غير معطِّلة</h3>
                            <p class="muted text-sm">إشارات المراقبة تُعالج في الخلفية ولا تُبطئ إجابة الطالب أبداً.</p>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </main>

        <footer class="border-base-300 border-t py-10">
            <div class="container mx-auto grid gap-4 p-4 sm:grid-cols-2 lg:px-8">
                <x-ui.card padding="p-5">
                    <div class="flex items-start gap-3">
                        <span class="bg-success/10 text-success grid size-10 shrink-0 place-items-center rounded-xl">
                            <x-heroicon-o-rocket-launch class="size-5" />
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold">التطوير والتشغيل</h4>
                                <x-ui.badge color="success" size="sm">رسمي</x-ui.badge>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <span class="bg-primary/10 text-primary grid size-9 shrink-0 place-items-center rounded-lg">
                                    <x-heroicon-o-briefcase class="size-5" />
                                </span>
                                <div>
                                    <p class="muted text-xs">الشركة المطورة</p>
                                    <p class="text-sm font-extrabold">شركة SWAT-IT لحلول البرمجة</p>
                                </div>
                            </div>
                            <div class="border-base-300 mt-3 flex items-center gap-3 border-t pt-3">
                                <span class="bg-info/10 text-info grid size-9 shrink-0 place-items-center rounded-lg">
                                    <x-heroicon-o-code-bracket class="size-5" />
                                </span>
                                <div>
                                    <p class="muted text-xs">رئيس البنية البرمجية والمطور</p>
                                    <p class="text-sm font-extrabold">المهندس محمد فوزي أبو نحلة</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-5">
                    <div class="flex items-start gap-3">
                        <span class="bg-warning/10 text-warning grid size-10 shrink-0 place-items-center rounded-xl">
                            <x-heroicon-o-server-stack class="size-5" />
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold">الدعم التقني والاستضافة</h4>
                                <x-ui.badge color="warning" size="sm">شريك تشغيل</x-ui.badge>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <span class="bg-warning/10 text-warning grid size-9 shrink-0 place-items-center rounded-lg">
                                    <x-heroicon-o-building-office-2 class="size-5" />
                                </span>
                                <div>
                                    <p class="muted text-xs">الشركة الداعمة</p>
                                    <p class="text-sm font-extrabold">شركة MSN-IT</p>
                                </div>
                            </div>
                            <p class="muted mt-3 text-xs leading-relaxed">
                                مساعدة فنية في التطوير، استضافة وإدارة الخوادم، وتقديم استشارات تقنية للمنصة.
                            </p>
                            <a
                                href="https://msnit-international.com"
                                target="_blank"
                                rel="noopener"
                                class="text-warning mt-3 flex items-center gap-1 text-xs font-semibold hover:underline"
                            >
                                msnit-international.com
                                <x-heroicon-o-arrow-top-right-on-square class="size-3.5" />
                            </a>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <p class="muted border-base-300 border-t p-6 text-center text-sm">
                © {{ date('Y') }} {{ config('app.name') }} — منصة الاختبارات الآمنة
            </p>
        </footer>
    </div>

    @include('identity.sandbox.modal-hub')

    <x-ui.toast-host />
</body>
</html>
