<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Enums;

use App\Support\Enums\Severity;

/**
 * Every signal the client proctor can raise. The set is closed: the ingest
 * endpoint rejects anything not listed here, so a tampered client cannot invent
 * violation types or flood the table with junk.
 */
enum ViolationType: string
{
    // Window / focus
    case TabSwitch = 'tab_switch';
    case WindowBlur = 'window_blur';
    case FullscreenExit = 'fullscreen_exit';
    case MultiMonitor = 'multi_monitor';

    // Clipboard
    case CopyAttempt = 'copy_attempt';
    case PasteAttempt = 'paste_attempt';
    case ScreenshotAttempt = 'screenshot_attempt';

    // Tooling
    case DevtoolsOpened = 'devtools_opened';
    case DevtoolsShortcut = 'devtools_shortcut';

    // Device
    case DeviceChanged = 'device_changed';
    case ForbiddenDevice = 'forbidden_device';
    case HeadlessBrowser = 'headless_browser';

    // Environment
    case AudioDetected = 'audio_detected';
    case AudioViolation = 'audio_violation';
    case AudioTampered = 'audio_zero_tamper';
    case MicrophoneDenied = 'microphone_denied';
    case CameraDenied = 'camera_denied';

    // Vision & Motion
    case FaceMissing = 'face_missing';
    case MultipleFaces = 'multiple_faces';
    case GazeAway = 'gaze_away';
    case HeadMotion = 'head_motion';
    case IdentityMismatch = 'identity_mismatch';
    case IosDeviceBlocked = 'ios_device_blocked';
    case VisibilityLost = 'visibility_lost';

    // Behaviour
    case KeystrokeAnomaly = 'keystroke_anomaly';
    case ConnectionLost = 'connection_lost';
    case TimeManipulation = 'time_manipulation';

    public function label(): string
    {
        return match ($this) {
            self::TabSwitch => 'مغادرة صفحة الاختبار',
            self::WindowBlur => 'فقدان تركيز النافذة',
            self::FullscreenExit => 'الخروج من وضع ملء الشاشة',
            self::MultiMonitor => 'اكتشاف أكثر من شاشة',
            self::CopyAttempt => 'محاولة نسخ',
            self::PasteAttempt => 'محاولة لصق',
            self::ScreenshotAttempt => 'محاولة التقاط الشاشة',
            self::DevtoolsOpened => 'فتح أدوات المطوّر',
            self::DevtoolsShortcut => 'اختصار أدوات المطوّر',
            self::DeviceChanged => 'تغيير الجهاز أثناء الاختبار',
            self::ForbiddenDevice => 'جهاز غير مصرّح به',
            self::HeadlessBrowser => 'متصفح آلي',
            self::AudioDetected, self::AudioViolation => 'أصوات مشبوهة أو حديث بشري',
            self::AudioTampered => 'العبث بالميكروفون',
            self::MicrophoneDenied => 'رفض إذن الميكروفون',
            self::CameraDenied => 'رفض إذن الكاميرا',
            self::FaceMissing => 'عدم وجود وجه أمام الكاميرا',
            self::MultipleFaces => 'أكثر من شخص أمام الكاميرا',
            self::GazeAway => 'إبعاد النظر عن الشاشة',
            self::HeadMotion => 'حركة رأس وتدوير غير معتاد',
            self::IdentityMismatch => 'عدم مطابقة بصمة الشخص المعتمدة',
            self::IosDeviceBlocked => 'حظر جهاز iOS غير مدعوم',
            self::VisibilityLost => 'فقدان الرؤية ومغادرة التبويب',
            self::KeystrokeAnomaly => 'نمط كتابة غير معتاد',
            self::ConnectionLost => 'انقطاع الاتصال',
            self::TimeManipulation => 'العبث بوقت الجهاز',
        };
    }

    /** Default severity; the classifier may escalate on repetition. */
    public function severity(): Severity
    {
        return match ($this) {
            self::WindowBlur,
            self::MicrophoneDenied,
            self::CameraDenied,
            self::GazeAway => Severity::Low,

            self::TabSwitch,
            self::VisibilityLost,
            self::FullscreenExit,
            self::CopyAttempt,
            self::PasteAttempt,
            self::AudioDetected,
            self::AudioViolation,
            self::HeadMotion,
            self::FaceMissing,
            self::ConnectionLost => Severity::Medium,

            self::MultiMonitor,
            self::ScreenshotAttempt,
            self::DevtoolsShortcut,
            self::AudioTampered,
            self::MultipleFaces,
            self::IdentityMismatch,
            self::KeystrokeAnomaly => Severity::High,

            self::DevtoolsOpened,
            self::DeviceChanged,
            self::ForbiddenDevice,
            self::IosDeviceBlocked,
            self::HeadlessBrowser,
            self::TimeManipulation => Severity::Critical,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TabSwitch, self::WindowBlur, self::FullscreenExit, self::VisibilityLost => 'arrow-top-right-on-square',
            self::MultiMonitor => 'computer-desktop',
            self::CopyAttempt, self::PasteAttempt, self::ScreenshotAttempt => 'clipboard-document',
            self::DevtoolsOpened, self::DevtoolsShortcut => 'command-line',
            self::DeviceChanged, self::ForbiddenDevice, self::HeadlessBrowser, self::IosDeviceBlocked => 'device-phone-mobile',
            self::AudioDetected, self::AudioViolation, self::AudioTampered, self::MicrophoneDenied => 'microphone',
            self::FaceMissing, self::MultipleFaces, self::GazeAway, self::HeadMotion, self::IdentityMismatch, self::CameraDenied => 'eye',
            self::KeystrokeAnomaly => 'finger-print',
            self::ConnectionLost => 'wifi',
            self::TimeManipulation => 'clock',
        };
    }

    public function color(): string
    {
        return $this->severity()->color();
    }
}
