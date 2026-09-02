<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Enums;

use App\Support\Enums\Severity;

enum AttackType: string
{
    case SqlInjection = 'sql_injection';
    case CrossSiteScripting = 'xss';
    case PathTraversal = 'path_traversal';
    case CommandInjection = 'command_injection';
    case RateAbuse = 'rate_abuse';
    case BruteForce = 'brute_force';
    case Honeypot = 'honeypot';
    case Automation = 'automation';

    public function label(): string
    {
        return match ($this) {
            self::SqlInjection => 'حقن SQL',
            self::CrossSiteScripting => 'حقن سكربت',
            self::PathTraversal => 'تجاوز المسارات',
            self::CommandInjection => 'حقن أوامر',
            self::RateAbuse => 'إفراط في الطلبات',
            self::BruteForce => 'تخمين كلمات المرور',
            self::Honeypot => 'مصيدة',
            self::Automation => 'أداة آلية',
        };
    }

    public function severity(): Severity
    {
        return match ($this) {
            self::RateAbuse, self::Automation => Severity::Medium,
            self::BruteForce, self::Honeypot, self::CrossSiteScripting => Severity::High,
            self::SqlInjection, self::PathTraversal, self::CommandInjection => Severity::Critical,
        };
    }

    public function color(): string
    {
        return $this->severity()->color();
    }
}
