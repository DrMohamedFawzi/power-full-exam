<x-layouts.exam :title="$runner['exam']['title']">
    <div x-data="sandboxExamRunner(@js($runner))" x-init="init()" class="mx-auto flex min-h-screen max-w-5xl flex-col gap-4 p-4 lg:p-8 relative">

        {{-- 1. Preflight Face Match Modal Overlay (if enabled) --}}
        <template x-if="preflightModalOpen">
            <div class="fixed inset-0 z-[99999] bg-base-900/98 backdrop-blur-2xl flex items-center justify-center p-4">
                <div class="card bg-base-100 max-w-xl w-full border border-primary/40 shadow-2xl">
                    <div class="card-body text-center items-center">
                        <div class="w-16 h-16 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center mb-2 animate-bounce">
                            <x-heroicon-o-shield-check class="size-8 text-primary" />
                        </div>

                        <h2 class="card-title text-2xl font-black">بوابة التحقق ومطابقة الهوية (Face Verification)</h2>
                        <p class="text-xs text-base-content/70 my-1 leading-relaxed max-w-md">
                            يتم تفعيل زر "بدء الامتحان" فور التأكد من مطابقة ملامح الوجه مع الصورة المعتمدة.
                        </p>

                        {{-- Face Match Live Card --}}
                        <div class="w-full bg-base-200 rounded-box p-4 border border-base-300 my-3 flex flex-col items-center">
                            <div class="flex items-center justify-around w-full gap-4 mb-3">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-box overflow-hidden border-2 border-primary shadow-sm bg-base-300 flex items-center justify-center">
                                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80" class="w-full h-full object-cover">
                                    </div>
                                    <span class="text-[10px] text-muted mt-1 font-bold">الصورة المعتمدة</span>
                                </div>
                                <div class="text-2xl font-black text-primary animate-pulse">⟷</div>
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-box overflow-hidden border-2 border-primary bg-black flex items-center justify-center relative shadow-sm">
                                        <video id="preflight-live-video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                                    </div>
                                    <span class="text-[10px] text-muted mt-1 font-bold">الكاميرا الحية</span>
                                </div>
                            </div>

                            <div class="badge text-xs font-bold py-3 px-4 rounded-btn"
                                 :class="faceMatched ? 'badge-success text-white' : 'badge-warning'">
                                <span x-text="preflightStatusText"></span>
                            </div>
                        </div>

                        <button type="button" @click="startExam()"
                            class="btn btn-primary btn-block font-black text-base mt-2"
                            :disabled="!faceMatched"
                            :class="!faceMatched ? 'btn-disabled opacity-50 cursor-not-allowed' : 'shadow-lg shadow-primary/30'">
                            <span x-text="faceMatched ? 'بدء الامتحان فائق الحماية الآن (7 دقائق)' : 'بانتظار اتمام مطابقة الوجه...'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- 2. Offline Freeze Fullscreen Lockout Overlay (3 chances x 10 mins) --}}
        <template x-if="isOfflineFrozen">
            <div class="fixed inset-0 z-[999999] bg-base-900/98 backdrop-blur-3xl flex flex-col items-center justify-center text-center p-6 text-white select-none">
                <div class="max-w-xl w-full bg-base-100/10 border-2 border-warning/40 rounded-3xl p-8 shadow-2xl backdrop-blur-md flex flex-col items-center">
                    <div class="w-24 h-24 rounded-full bg-warning/20 border-2 border-warning flex items-center justify-center mb-4 animate-pulse">
                        <x-heroicon-o-wifi class="size-12 text-warning" />
                    </div>

                    <div class="badge badge-warning font-black text-xs py-2.5 px-4 mb-2">
                        🔒 تم تجميد وقت الامتحان بالكامل (Offline Freeze Lock)
                    </div>

                    <h2 class="text-2xl font-black text-white">
                        انقطع الاتصال بالإنترنت — وقتك محفوظ ومجمّد
                    </h2>

                    <p class="text-xs text-white/80 my-3 leading-relaxed max-w-md">
                        لضمان العدالة وتكافؤ الفرص، تم قفل شاشة الامتحان وحجب الأسئلة مؤقتاً حتى عودة الاتصال.
                        جميع إجاباتك السابقة محفوظة ومؤمنة في متصفحك.
                    </p>

                    <div class="grid grid-cols-2 gap-4 w-full my-4 bg-black/40 p-4 rounded-2xl border border-white/10">
                        <div class="text-center">
                            <span class="text-[11px] text-white/60 block">الوقت المتبقي لمهلة التجميد:</span>
                            <span class="text-3xl font-black text-warning numeric" x-text="formattedFreezeRemaining"></span>
                        </div>
                        <div class="text-center border-r border-white/10 pr-4">
                            <span class="text-[11px] text-white/60 block">فرص التجميد المستخدمة:</span>
                            <span class="text-3xl font-black text-info numeric">
                                <span x-text="freezeCount"></span> / <span x-text="maxFreezeChances"></span>
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 w-full mt-2">
                        <button type="button" @click="handleInternetRestoration()" 
                                class="btn btn-warning btn-block font-black text-sm flex-1 gap-2 shadow-lg shadow-warning/20">
                            <x-heroicon-o-bolt class="size-5" />
                            <span>استعادة الاتصال واستئناف الامتحان فوراً</span>
                        </button>
                    </div>

                    <span class="text-[10px] text-white/50 mt-3">
                        سيتم استئناف الامتحان تلقائياً فور عودة اتصال الشبكة في جهازك.
                    </span>
                </div>
            </div>
        </template>

        {{-- 3. Anti-Cheat Blackout Screen Overlay (Exit / Copy lockout) --}}
        <template x-if="blackoutActive">
            <div class="fixed inset-0 z-[99999] bg-black flex flex-col items-center justify-center text-center p-6 text-white select-none">
                <div class="w-24 h-24 rounded-full bg-error/20 border-2 border-error flex items-center justify-center mb-4 text-3xl font-extrabold text-error" x-text="blackoutTimer"></div>
                <h3 class="text-2xl font-extrabold mb-2 text-white">⚠️ تم كشف محاولة مخالفة أمنية!</h3>
                <p class="text-sm max-w-md mb-4 text-white/80 leading-relaxed">
                    <span x-text="blackoutReason"></span>
                    <br>
                    <span class="text-warning font-bold block mt-3">تم حجب الشاشة مؤقتاً لحماية أسئلة الاختبار وستفتح تلقائياً.</span>
                </p>
                <div class="badge badge-error font-bold text-xs py-2 px-3">
                    مؤشر النزاهة الحالي: <span class="numeric mr-1" x-text="integrityIndex + '%'"></span>
                </div>
            </div>
        </template>

        {{-- PIP Picture-in-Picture Floating Camera Widget with Green Landmarks & Circle Boundary --}}
        <div id="aegis-vision-deterrent" class="fixed bottom-6 left-6 w-36 h-36 rounded-full overflow-hidden shadow-2xl border-4 border-success z-40 bg-black transition-colors duration-300 flex items-center justify-center relative">
            <video id="aegis-pip-video" autoplay playsinline muted class="w-full h-full object-cover transform scale-x-[-1]"></video>
            <canvas id="aegis-pip-canvas" class="absolute inset-0 w-full h-full transform scale-x-[-1] pointer-events-none z-10" width="144" height="144"></canvas>
            <div class="absolute bottom-1 bg-black/80 px-2.5 py-0.5 rounded-full text-[9px] font-bold text-success flex items-center gap-1 z-20">
                <span class="size-1.5 rounded-full bg-success animate-ping"></span>
                <span>بصمة حية</span>
            </div>
        </div>

        {{-- Top Simulation & Proctoring Bar --}}
        <div class="alert alert-info alert-soft flex flex-wrap items-center justify-between gap-3 text-xs py-3 px-4 rounded-box border border-info/20 shadow-sm">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="relative flex size-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success opacity-75"></span>
                        <span class="relative inline-flex size-2.5 rounded-full bg-success font-bold"></span>
                    </span>
                    <span class="font-extrabold">المراقبة الذكية الفائقة نشطة (15 سؤال / 7 دقائق)</span>
                </div>

                <div class="badge badge-warning font-bold gap-1 text-xs py-1.5 px-3">
                    <span>⚠️ الإنذارات:</span>
                    <span x-text="violationCount"></span> / 3
                </div>

                {{-- Live Audio VAD Meter Bar --}}
                <div class="hidden sm:flex items-center gap-2 bg-base-100/80 px-2.5 py-1 rounded-btn text-xs border border-base-300">
                    <span>🎙️ رصد الصوت (VAD):</span>
                    <div class="w-14 bg-base-300 h-2 rounded-full overflow-hidden">
                        <div id="audio-vad-bar" class="bg-success h-full w-2 transition-all duration-200"></div>
                    </div>
                </div>
            </div>

            {{-- Interactive Test Controls for Sandbox Evaluation --}}
            <div class="flex items-center gap-2">
                <button type="button" @click="simulateOfflineToggle()" class="btn btn-xs btn-warning font-black gap-1 shadow-sm">
                    <x-heroicon-o-wifi class="size-3.5" />
                    <span>🔌 تجربة تجميد انقطاع الإنترنت</span>
                </button>

                <button type="button" @click="triggerExitBlackout('محاولة خروج تجريبية للتحقق من قوة الحماية')" class="btn btn-xs btn-error btn-outline font-bold">
                    <span>⚠️ محاكاة محاولة خروج</span>
                </button>
            </div>
        </div>

        {{-- Main Quiz Header --}}
        <header class="surface flex flex-wrap items-center justify-between gap-4 p-4 rounded-box shadow-sm border border-base-300">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-extrabold">{{ $runner['exam']['title'] }}</h1>
                    <span class="badge badge-primary badge-sm font-bold">امتحان تجريبي حي</span>
                </div>
                <p class="muted text-xs mt-0.5">
                    سؤال <span class="numeric font-bold" x-text="currentIndex + 1"></span> من
                    <span class="numeric font-bold">15</span>
                    — تمت الإجابة عن <span class="numeric font-bold text-primary" x-text="answeredCount"></span> من 15
                </p>
            </div>

            <div class="flex items-center gap-6">
                <div class="text-center">
                    <p class="muted text-[11px] font-bold">الوقت المتبقي (7 دقائق)</p>
                    <p class="numeric text-2xl font-black" :class="remainingSeconds < 60 ? 'text-error animate-pulse' : 'text-primary'" x-text="formattedRemaining"></p>
                </div>

                <div class="w-36">
                    <div class="flex items-center justify-between text-[10px] font-bold mb-1">
                        <span>مؤشر النزاهة</span>
                        <span class="numeric" :class="integrityIndex < 70 ? 'text-error' : 'text-success'" x-text="integrityIndex + '%'"></span>
                    </div>
                    <div class="w-full bg-base-300 h-2 rounded-full overflow-hidden">
                        <div class="h-full transition-all duration-300"
                             :class="integrityIndex < 70 ? 'bg-error' : 'bg-success'"
                             :style="'width: ' + integrityIndex + '%'"></div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Question Card --}}
        <x-ui.card class="flex-1 relative overflow-hidden border border-base-300 shadow-md">
            <template x-if="current">
                <div class="flex flex-col gap-5">
                    <div class="flex items-start justify-between gap-4 border-b border-base-200 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="badge badge-primary font-bold">سؤال محمي #<span x-text="currentIndex + 1"></span></span>
                            <span class="badge badge-neutral badge-soft font-semibold text-xs" x-text="current.type_label"></span>
                            <span class="text-xs text-muted font-medium" x-text="current.points + ' درجات'"></span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="toggleBookmark()"
                                class="btn btn-xs rounded-btn gap-1"
                                :class="isBookmarked(currentIndex) ? 'btn-warning' : 'btn-ghost text-muted'">
                                <span>🔖</span>
                                <span x-text="isBookmarked(currentIndex) ? 'تمت إضافة علامة' : 'وضع علامة'"></span>
                            </button>

                            <button type="button" @click="clearAnswer()" x-show="isAnswered(current)"
                                class="btn btn-ghost btn-xs text-error gap-1">
                                <x-heroicon-o-trash class="size-3.5" />
                                إلغاء التحديد
                            </button>
                        </div>
                    </div>

                    {{-- Protected Question Canvas with Magnifying Lens Spotlight --}}
                    <div id="question-container" class="relative w-full rounded-box overflow-hidden bg-white border border-primary/30 p-2 shadow-sm my-2">
                        <canvas id="question-canvas" class="w-full h-36 rounded-box bg-white"></canvas>
                    </div>

                    {{-- Options: Multiple Choice & True/False --}}
                    <template x-if="current.type === 'multiple_choice' || current.type === 'true_false'">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
                            <template x-for="(option, optIdx) in current.options" :key="option">
                                <label class="border-base-300 hover:border-primary flex cursor-pointer items-center gap-3 rounded-box border p-4 transition-all"
                                    :class="(current.answer ?? [])[0] === option ? 'border-primary bg-primary/10 shadow-sm' : 'bg-base-100'">
                                    <input type="radio" class="radio radio-primary radio-sm" :name="'sq' + current.id"
                                        :checked="(current.answer ?? [])[0] === option" @change="selectSingle(option)">
                                    <span class="badge badge-neutral badge-sm font-bold" x-text="['أ', 'ب', 'ج', 'د'][optIdx] ?? (optIdx + 1)"></span>
                                    <span class="font-bold text-xs lg:text-sm" x-text="option"></span>
                                </label>
                            </template>
                        </div>
                    </template>

                    {{-- Options: Multiple Select --}}
                    <template x-if="current.type === 'multiple_select'">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
                            <template x-for="(option, optIdx) in current.options" :key="option">
                                <label class="border-base-300 hover:border-primary flex cursor-pointer items-center gap-3 rounded-box border p-4 transition-all"
                                    :class="(current.answer ?? []).includes(option) ? 'border-primary bg-primary/10 shadow-sm' : 'bg-base-100'">
                                    <input type="checkbox" class="checkbox checkbox-primary checkbox-sm"
                                        :checked="(current.answer ?? []).includes(option)" @change="toggleMulti(option)">
                                    <span class="badge badge-neutral badge-sm font-bold" x-text="['أ', 'ب', 'ج', 'د'][optIdx] ?? (optIdx + 1)"></span>
                                    <span class="font-bold text-xs lg:text-sm" x-text="option"></span>
                                </label>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            <x-slot:footer>
                <div class="flex justify-between items-center">
                    <button type="button" class="btn btn-ghost btn-sm" @click="previous()" :disabled="currentIndex === 0">
                        &rarr; السابق
                    </button>

                    <div class="flex gap-2">
                        <button type="button" class="btn btn-outline btn-error btn-sm font-bold" @click="confirmSubmit()">إنهاء وتسليم الاختبار</button>
                        <button type="button" class="btn btn-primary btn-sm font-bold" @click="next()"
                            :disabled="currentIndex === questions.length - 1">
                            التالي &larr;
                        </button>
                    </div>
                </div>
            </x-slot:footer>
        </x-ui.card>

        {{-- 15-Question Navigation Grid --}}
        <div class="surface p-4 rounded-box border border-base-300 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="muted text-xs font-bold">خريطة الأسئلة الـ 15 (Quiz Navigation):</p>
                <span class="text-[11px] text-muted">اضغط على أي رقم للانتقال المباشر</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="(q, index) in questions" :key="index">
                    <button type="button" @click="goto(index)"
                        class="btn btn-sm relative font-black min-w-10"
                        :class="{
                            'btn-primary ring-2 ring-primary ring-offset-2': currentIndex === index,
                            'btn-success text-white': isAnswered(q) && currentIndex !== index,
                            'btn-warning btn-outline': isBookmarked(index) && !isAnswered(q) && currentIndex !== index,
                            'btn-ghost border border-base-300': !isAnswered(q) && !isBookmarked(index) && currentIndex !== index
                        }">
                        <span x-text="index + 1"></span>
                        <template x-if="isBookmarked(index)">
                            <span class="absolute -top-1 -right-1 text-[10px]">🔖</span>
                        </template>
                    </button>
                </template>
            </div>
        </div>

        {{-- Submit Confirmation Modal --}}
        <dialog id="sandbox-submit-modal" class="modal">
            <div class="modal-box">
                <h3 class="font-black text-lg">تأكيد تسليم الامتحان التجريبي</h3>
                <p class="py-4 text-xs leading-relaxed text-base-content/80">
                    أجبت عن <strong class="text-primary font-black" x-text="answeredCount"></strong> من أصل <strong>15 سؤال</strong>.
                    هل تود تسليم الاختبار الآن والانتقال لرؤية النتيجة وتعبئة الاستبيان؟
                </p>
                <div class="modal-action">
                    <form method="dialog">
                        <button class="btn btn-ghost btn-sm">متابعة الإجابة</button>
                    </form>
                    <button type="button" class="btn btn-primary btn-sm font-bold" @click="finishExam()">
                        تسليم وعرض النتيجة
                    </button>
                </div>
            </div>
        </dialog>

        {{-- Post-Exam Score & Comprehensive Survey Modal --}}
        <template x-if="surveyOpen">
            <div class="fixed inset-0 z-[999999] bg-base-900/95 backdrop-blur-2xl flex items-center justify-center p-4 overflow-y-auto">
                <div class="card bg-base-100 max-w-2xl w-full border border-primary/40 shadow-2xl my-8">
                    <div class="card-body p-6 lg:p-8">
                        {{-- Results Header --}}
                        <div class="text-center border-b border-base-200 pb-5">
                            <div class="inline-flex size-14 items-center justify-center rounded-2xl bg-success/15 text-success mb-2">
                                <x-heroicon-o-check-badge class="size-8" />
                            </div>
                            <h2 class="text-2xl font-black text-base-content">
                                تم إكمال الامتحان التجريبي بنجاح!
                            </h2>
                            <p class="muted text-xs mt-1">
                                إليك ملخص أدائك الأمني والأكاديمي في الجلسة:
                            </p>

                            <div class="grid grid-cols-3 gap-3 mt-4">
                                <div class="bg-base-200 p-3 rounded-xl">
                                    <span class="text-[10px] text-muted block">الدرجة النهائية</span>
                                    <span class="text-xl font-black text-primary numeric">
                                        <span x-text="score"></span> / <span x-text="totalPoints"></span>
                                    </span>
                                </div>
                                <div class="bg-base-200 p-3 rounded-xl">
                                    <span class="text-[10px] text-muted block">مؤشر النزاهة</span>
                                    <span class="text-xl font-black text-success numeric" x-text="integrityIndex + '%'"></span>
                                </div>
                                <div class="bg-base-200 p-3 rounded-xl">
                                    <span class="text-[10px] text-muted block">الوقت المستغرق</span>
                                    <span class="text-xl font-black text-base-content numeric" x-text="formattedTimeTaken"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Survey Form --}}
                        <template x-if="!surveySubmitted">
                            <div class="mt-4 space-y-4">
                                <div>
                                    <span class="badge badge-info font-bold text-xs mb-1">استبيان تقييم التجربة وتحليل المشاعر</span>
                                    <h3 class="text-base font-extrabold text-base-content">
                                        ما هو رأيك في فكرة المنصة ونظام منع الغش وتجميد الإنترنت؟
                                    </h3>
                                </div>

                                {{-- Question 1: Anti-cheat support --}}
                                <div>
                                    <label class="label text-xs font-bold text-base-content">
                                        1. ما مدى تأييدك لتطبيق هذا النظام لمنع الغش والتسريب في الامتحانات؟
                                    </label>
                                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
                                        <label class="btn btn-xs font-bold cursor-pointer" :class="surveyData.support_anti_cheat === 'strongly_support' ? 'btn-success text-white' : 'btn-outline'">
                                            <input type="radio" value="strongly_support" x-model="surveyData.support_anti_cheat" class="hidden">
                                            مؤيد بشدة ⭐⭐⭐
                                        </label>
                                        <label class="btn btn-xs font-bold cursor-pointer" :class="surveyData.support_anti_cheat === 'support' ? 'btn-primary' : 'btn-outline'">
                                            <input type="radio" value="support" x-model="surveyData.support_anti_cheat" class="hidden">
                                            مؤيد
                                        </label>
                                        <label class="btn btn-xs font-bold cursor-pointer" :class="surveyData.support_anti_cheat === 'neutral' ? 'btn-neutral' : 'btn-outline'">
                                            <input type="radio" value="neutral" x-model="surveyData.support_anti_cheat" class="hidden">
                                            محايد
                                        </label>
                                        <label class="btn btn-xs font-bold cursor-pointer" :class="surveyData.support_anti_cheat === 'oppose' ? 'btn-warning' : 'btn-outline'">
                                            <input type="radio" value="oppose" x-model="surveyData.support_anti_cheat" class="hidden">
                                            معارض
                                        </label>
                                        <label class="btn btn-xs font-bold cursor-pointer" :class="surveyData.support_anti_cheat === 'strongly_oppose' ? 'btn-error' : 'btn-outline'">
                                            <input type="radio" value="strongly_oppose" x-model="surveyData.support_anti_cheat" class="hidden">
                                            معارض بشدة
                                        </label>
                                    </div>
                                </div>

                                {{-- Question 2: Ratings for Features --}}
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div class="bg-base-200/60 p-3 rounded-xl">
                                        <label class="text-[11px] font-bold block mb-1">ميزة تجميد الوقت عند انقطاع الإنترنت:</label>
                                        <select x-model.number="surveyData.time_freeze_rating" class="select select-bordered select-xs w-full font-bold">
                                            <option value="5">⭐⭐⭐⭐⭐ ممتاز جداً</option>
                                            <option value="4">⭐⭐⭐⭐ جيد جداً</option>
                                            <option value="3">⭐⭐⭐ متوسط</option>
                                            <option value="2">⭐⭐ ضعيف</option>
                                        </select>
                                    </div>

                                    <div class="bg-base-200/60 p-3 rounded-xl">
                                        <label class="text-[11px] font-bold block mb-1">ميزة التحقق ومطابقة الوجه:</label>
                                        <select x-model.number="surveyData.face_match_rating" class="select select-bordered select-xs w-full font-bold">
                                            <option value="5">⭐⭐⭐⭐⭐ ممتازة ومطمئنة</option>
                                            <option value="4">⭐⭐⭐⭐ جيدة</option>
                                            <option value="3">⭐⭐⭐ مقبولة</option>
                                            <option value="2">⭐⭐ غير مريحة</option>
                                        </select>
                                    </div>

                                    <div class="bg-base-200/60 p-3 rounded-xl">
                                        <label class="text-[11px] font-bold block mb-1">سهولة وسلاسة الواجهة:</label>
                                        <select x-model.number="surveyData.overall_rating" class="select select-bordered select-xs w-full font-bold">
                                            <option value="5">⭐⭐⭐⭐⭐ سريعة ومريحة</option>
                                            <option value="4">⭐⭐⭐⭐ جيدة جداً</option>
                                            <option value="3">⭐⭐⭐ متوسطة</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Question 3: Written Feedback for Sentiment Analysis --}}
                                <div>
                                    <label class="label text-xs font-bold text-base-content">
                                        اكتب تعليقك أو انطباعك (سيتم تحليله بمحرك الذكاء الاصطناعي للمشاعر):
                                    </label>
                                    <textarea x-model="surveyData.feedback_text" rows="3" class="textarea textarea-bordered w-full text-xs font-medium" placeholder="شاركنا ملاحظاتك حول النظام، الأمان، أو اقتراحاتك للتطوير..."></textarea>
                                </div>

                                <div class="flex items-center justify-between pt-2">
                                    <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">تخطي</a>
                                    <button type="button" @click="submitSurvey()" class="btn btn-primary btn-sm font-black gap-2" :disabled="submitting">
                                        <span x-show="!submitting">إرسال الاستبيان وتحليل المشاعر &larr;</span>
                                        <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Survey Success / Sentiment Result Preview --}}
                        <template x-if="surveySubmitted">
                            <div class="text-center py-6 space-y-4">
                                <div class="inline-flex size-14 items-center justify-center rounded-full bg-success/20 text-success animate-bounce">
                                    <x-heroicon-o-sparkles class="size-8" />
                                </div>
                                <h3 class="text-xl font-black text-success">
                                    تم استلام استبيانك وتحليله بنجاح!
                                </h3>

                                <template x-if="sentimentResult">
                                    <div class="bg-base-200 p-4 rounded-2xl max-w-md mx-auto text-xs space-y-2 border border-base-300">
                                        <div class="flex items-center justify-between font-bold">
                                            <span>تصنيف المشاعر:</span>
                                            <span class="badge badge-success text-white font-black" x-text="sentimentResult.sentiment_label === 'positive' ? 'إيجابي ومؤيد ✨' : 'محايد'"></span>
                                        </div>
                                        <div class="flex items-center justify-between font-bold">
                                            <span>مؤشر الدعم المحسوب:</span>
                                            <span class="numeric text-primary font-black" x-text="(sentimentResult.sentiment_score > 0 ? '+' : '') + sentimentResult.sentiment_score"></span>
                                        </div>
                                    </div>
                                </template>

                                <div class="flex flex-col sm:flex-row justify-center gap-3 pt-4">
                                    <a href="{{ route('sandbox.surveys') }}" class="btn btn-success font-black btn-sm">
                                        <x-heroicon-o-chart-pie class="size-4" />
                                        <span>عرض لوحة تحليلات الاستبيانات والمشاعر</span>
                                    </a>
                                    <a href="{{ route('home') }}" class="btn btn-outline btn-sm">العودة للرئيسية</a>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-layouts.exam>
