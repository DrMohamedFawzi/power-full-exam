@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

echo.
echo ╔══════════════════════════════════════════════════════════╗
echo ║   ⚙️   Aegis-X — PHP 8.4 Post-Install Config            ║
echo ║   يُضبط php.ini ليدعم Laravel 13 + اختبار الحمل         ║
echo ╚══════════════════════════════════════════════════════════╝
echo.

:: ── إيجاد PHP 8.4 ────────────────────────────────────────────────
set PHP84=
for %%p in (
    "C:\Program Files\PHP\v8.4\php.exe"
    "C:\Program Files\PHP\php.exe"
    "C:\php\php.exe"
    "C:\php84\php.exe"
) do (
    if exist %%p (
        set PHP84=%%~p
        set PHP84DIR=%%~dp
        goto :found
    )
)
where php >nul 2>&1
if %ERRORLEVEL% == 0 (
    for /f "tokens=*" %%i in ('where php') do (
        set PHP84=%%i
        for %%d in ("%%i\.." ) do set PHP84DIR=%%~fd\
        goto :found
    )
)
echo [ERROR] PHP 8.4 غير موجود بعد. شغّل winget install PHP.PHP.8.4 أولاً.
pause
exit /b 1

:found
echo [✓] PHP 8.4 موجود: %PHP84%
"%PHP84%" --version
echo.

:: ── إيجاد php.ini ─────────────────────────────────────────────────
set INI=%PHP84DIR%php.ini
if not exist "%INI%" (
    if exist "%PHP84DIR%php.ini-production" (
        copy "%PHP84DIR%php.ini-production" "%INI%"
        echo [✓] نسخ php.ini-production → php.ini
    ) else (
        echo [ERROR] ملف php.ini غير موجود في %PHP84DIR%
        pause
        exit /b 1
    )
)

:: ── تفعيل الـ extensions المطلوبة ──────────────────────────────────
echo [*] تفعيل extensions...
set INI_CONTENT=

:: تفعيل extension_dir
powershell -Command "(Get-Content '%INI%') -replace '^;extension_dir = \"ext\"', 'extension_dir = \"ext\"' | Set-Content '%INI%'"

:: تفعيل كل extension مطلوب
for %%e in (mbstring openssl pdo_mysql mysqli fileinfo curl gd intl zip bcmath opcache) do (
    powershell -Command "(Get-Content '%INI%') -replace '^;extension=%%e', 'extension=%%e' | Set-Content '%INI%'"
    echo    [✓] extension=%%e
)

:: ── ضبط إعدادات الأداء ─────────────────────────────────────────────
echo.
echo [*] ضبط إعدادات الأداء لاختبار الحمل...

powershell -Command @"
    `$ini = Get-Content '%INI%'
    `$ini = `$ini -replace '^memory_limit = .*', 'memory_limit = 512M'
    `$ini = `$ini -replace '^max_execution_time = .*', 'max_execution_time = 60'
    `$ini = `$ini -replace '^upload_max_filesize = .*', 'upload_max_filesize = 32M'
    `$ini = `$ini -replace '^post_max_size = .*', 'post_max_size = 32M'
    `$ini = `$ini -replace '^;opcache.enable=.*', 'opcache.enable=1'
    `$ini = `$ini -replace '^;opcache.memory_consumption=.*', 'opcache.memory_consumption=256'
    `$ini = `$ini -replace '^;opcache.max_accelerated_files=.*', 'opcache.max_accelerated_files=20000'
    `$ini = `$ini -replace '^;opcache.revalidate_freq=.*', 'opcache.revalidate_freq=0'
    `$ini = `$ini -replace '^;opcache.validate_timestamps=.*', 'opcache.validate_timestamps=0'
    `$ini | Set-Content '%INI%'
"@
echo    [✓] memory_limit = 512M
echo    [✓] OPcache مُفعَّل ومُحسَّن للإنتاج

:: ── اختبار سريع ──────────────────────────────────────────────────
echo.
echo [*] اختبار التحقق من النجاح...
"%PHP84%" -r "echo 'PHP OK: ' . PHP_VERSION . PHP_EOL;"
"%PHP84%" -r "extension_loaded('pdo_mysql') ? print('[✓] pdo_mysql OK'.PHP_EOL) : print('[✗] pdo_mysql MISSING'.PHP_EOL);"
"%PHP84%" -r "extension_loaded('mbstring') ? print('[✓] mbstring OK'.PHP_EOL) : print('[✗] mbstring MISSING'.PHP_EOL);"

:: ── إضافة PHP 8.4 إلى PATH مؤقتاً ───────────────────────────────
echo.
echo [*] إضافة PHP 8.4 إلى PATH (للجلسة الحالية فقط)...
setx PHP84_PATH "%PHP84DIR%" /M >nul 2>&1
echo    [✓] يمكنك الآن تشغيل: %PHP84% artisan ...

echo.
echo ╔══════════════════════════════════════════════════════════╗
echo ║  ✅ الإعداد اكتمل! الخطوة التالية:                       ║
echo ║                                                          ║
echo ║  شغّل: loadtest-setup.bat                               ║
echo ╚══════════════════════════════════════════════════════════╝
echo.
pause
