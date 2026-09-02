@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

echo.
echo ╔══════════════════════════════════════════════════════════╗
echo ║   🚀  Aegis-X Load Test Setup Script                    ║
echo ║   الإعداد الكامل لاختبار 50,000 طالب                    ║
echo ╚══════════════════════════════════════════════════════════╝
echo.

:: ─── تحديد PHP 8.4 ─────────────────────────────────────────────────────────
set PHP84=
for %%p in (
    "C:\Program Files\PHP\v8.4\php.exe"
    "C:\Program Files\PHP\php.exe"
    "C:\php\php.exe"
    "C:\php8.4\php.exe"
) do (
    if exist %%p (
        set PHP84=%%~p
        goto :found_php
    )
)

:: البحث في PATH
where php >nul 2>&1
if %ERRORLEVEL% == 0 (
    for /f "tokens=*" %%i in ('where php') do (
        set PHP84=%%i
        goto :found_php
    )
)

echo [ERROR] PHP 8.4 غير موجود. يرجى تثبيته أولاً:
echo         winget install PHP.PHP.8.4
echo.
pause
exit /b 1

:found_php
echo [✓] PHP موجود: %PHP84%
"%PHP84%" --version
echo.

:: ─── تحديد Composer ─────────────────────────────────────────────────────────
set COMPOSER=
for %%c in (
    "C:\xampp\php\composer.phar"
    "C:\ProgramData\ComposerSetup\bin\composer.bat"
    "composer"
) do (
    if exist %%c (
        set COMPOSER_CMD=%PHP84% %%~c
        goto :found_composer
    )
)

:: البحث في PATH
where composer >nul 2>&1
if %ERRORLEVEL% == 0 (
    set COMPOSER_CMD=composer
    goto :found_composer
)

echo [ERROR] Composer غير موجود. يرجى تثبيته.
pause
exit /b 1

:found_composer
echo [✓] Composer موجود
echo.

:: ─── الخطوة 1: نسخ بيئة الاختبار ───────────────────────────────────────────
echo [1/7] تفعيل بيئة الاختبار (.env.loadtest)...
if not exist .env.backup (
    copy /Y .env .env.backup >nul
    echo        ← تم حفظ .env الأصلي في .env.backup
)
copy /Y .env.loadtest .env >nul
echo [✓] تم تفعيل .env.loadtest
echo.

:: ─── الخطوة 2: تثبيت الـ dependencies ─────────────────────────────────────
echo [2/7] التحقق من vendor/...
if not exist vendor\autoload.php (
    echo        Installing composer dependencies...
    %COMPOSER_CMD% install --no-interaction --prefer-dist --optimize-autoloader 2>&1
) else (
    echo [✓] vendor/ موجود - تخطّي
)
echo.

:: ─── الخطوة 3: إنشاء قاعدة البيانات ────────────────────────────────────────
echo [3/7] إنشاء قاعدة بيانات الاختبار (aegis_x_loadtest)...
"C:\xampp\mysql\bin\mysql.exe" -uroot -e "CREATE DATABASE IF NOT EXISTS aegis_x_loadtest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1
echo [✓] قاعدة البيانات جاهزة
echo.

:: ─── الخطوة 4: Optimize Clear ───────────────────────────────────────────────
echo [4/7] مسح الكاش...
"%PHP84%" artisan config:clear 2>&1
"%PHP84%" artisan cache:clear 2>&1
"%PHP84%" artisan route:clear 2>&1
echo [✓] تم مسح الكاش
echo.

:: ─── الخطوة 5: Migrate ──────────────────────────────────────────────────────
echo [5/7] بناء قاعدة البيانات (migrate:fresh)...
echo        قد يستغرق 10-30 ثانية...
"%PHP84%" artisan migrate:fresh --force 2>&1
echo [✓] Migrations اكتملت
echo.

:: ─── الخطوة 6: Seed ─────────────────────────────────────────────────────────
echo [6/7] توليد 50,000 طالب (LoadTestSeeder)...
echo        هذا سيستغرق 30-120 ثانية...
echo.
"%PHP84%" artisan db:seed --class=LoadTestSeeder --force 2>&1
echo.
echo [✓] Seeding اكتمل
echo.

:: ─── الخطوة 7: Cache للإنتاج ────────────────────────────────────────────────
echo [7/7] تفعيل كاش الإنتاج...
"%PHP84%" artisan config:cache 2>&1
"%PHP84%" artisan route:cache 2>&1
"%PHP84%" artisan event:cache 2>&1
echo [✓] Production cache مفعّل
echo.

:: ─── تشغيل Queue Worker ──────────────────────────────────────────────────────
echo [+] تشغيل Queue Worker في الخلفية...
start "Aegis Queue Worker" /MIN "%PHP84%" artisan queue:work --sleep=1 --tries=1 --max-time=3600
echo [✓] Queue Worker شغّال في الخلفية
echo.

:: ─── تشغيل PHP Dev Server ─────────────────────────────────────────────────────
echo [+] تشغيل PHP Development Server على http://localhost:8000...
echo     (اضغط Ctrl+C في هذه النافذة لإيقافه)
echo.
echo ╔══════════════════════════════════════════════════════════╗
echo ║  🎉 البيئة جاهزة! الخطوة التالية:                      ║
echo ║                                                          ║
echo ║  في terminal جديد، شغّل k6:                             ║
echo ║                                                          ║
echo ║  k6 run load-test\k6-script.js                          ║
echo ║    --env BASE_URL=http://localhost:8000                  ║
echo ║    --env EXAM_ID=1                                       ║
echo ║    --env STUDENT_USERNAME=student1@aegis-x.test         ║
echo ║    --env STUDENT_PASSWORD=password123                    ║
echo ║    --stage 2m:500,5m:500,1m:0                           ║
echo ╚══════════════════════════════════════════════════════════╝
echo.
echo السيرفر شغّال على: http://localhost:8000
echo.
"%PHP84%" artisan serve --host=0.0.0.0 --port=8000 --tries=1
