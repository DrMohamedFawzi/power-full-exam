<?php

declare(strict_types=1);

namespace App\Support\Navigation;

use App\Modules\Identity\Models\User;
use App\Support\Enums\Role;
use Illuminate\Support\Facades\Route;

/**
 * The single source of truth for sidebar navigation.
 *
 * Items whose route does not exist yet are silently skipped, so a module can be
 * built out without breaking every other page's chrome.
 */
final class Navigation
{
    /**
     * @return array<int, array{label: string, items: array<int, NavItem>}>
     */
    public static function for(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $groups = match ($user->role) {
            Role::Student => self::student(),
            Role::Teacher => self::teacher(),
            Role::Institution => self::institution(),
        };

        return array_values(array_filter(array_map(
            static fn (array $group): array => [
                'label' => $group['label'],
                'items' => array_values(array_filter(
                    $group['items'],
                    static fn (NavItem $item): bool => Route::has($item->route),
                )),
            ],
            $groups,
        ), static fn (array $group): bool => $group['items'] !== []));
    }

    /** @return array<int, array{label: string, items: array<int, NavItem>}> */
    private static function student(): array
    {
        return [
            ['label' => 'الرئيسية', 'items' => [
                new NavItem('لوحة التحكم', 'student.dashboard', 'home'),
                new NavItem('صفوفي', 'student.classrooms.index', 'academic-cap'),
            ]],
            ['label' => 'الاختبارات', 'items' => [
                new NavItem('الاختبارات المتاحة', 'student.exams.index', 'clipboard-document-list'),
                new NavItem('نتائجي', 'student.results.index', 'chart-bar'),
            ]],
            ['label' => 'الحساب', 'items' => [
                new NavItem('أجهزتي', 'student.devices.index', 'device-phone-mobile'),
                new NavItem('الملف الشخصي', 'profile.edit', 'user-circle'),
            ]],
        ];
    }

    /** @return array<int, array{label: string, items: array<int, NavItem>}> */
    private static function teacher(): array
    {
        return [
            ['label' => 'الرئيسية', 'items' => [
                new NavItem('لوحة التحكم', 'teacher.dashboard', 'home'),
            ]],
            ['label' => 'التدريس', 'items' => [
                new NavItem('الصفوف', 'teacher.classrooms.index', 'academic-cap'),
                new NavItem('طلبات الانضمام', 'teacher.enrollments.index', 'user-plus'),
                new NavItem('الاختبارات', 'teacher.exams.index', 'clipboard-document-list'),
            ]],
            ['label' => 'المتابعة', 'items' => [
                new NavItem('المراقبة المباشرة', 'teacher.monitor.index', 'eye'),
                new NavItem('التقارير والنتائج', 'teacher.results.index', 'chart-bar'),
            ]],
            ['label' => 'الحساب', 'items' => [
                new NavItem('الملف الشخصي', 'profile.edit', 'user-circle'),
            ]],
        ];
    }

    /** @return array<int, array{label: string, items: array<int, NavItem>}> */
    private static function institution(): array
    {
        return [
            ['label' => 'الرئيسية', 'items' => [
                new NavItem('لوحة التحكم', 'institution.dashboard', 'home'),
            ]],
            ['label' => 'الإدارة', 'items' => [
                new NavItem('المعلّمون', 'institution.teachers.index', 'users'),
                new NavItem('الطلاب', 'institution.students.index', 'academic-cap'),
                new NavItem('الاختبارات', 'institution.exams.index', 'clipboard-document-list'),
            ]],
            ['label' => 'الأمن', 'items' => [
                new NavItem('مركز الرصد', 'overwatch.dashboard', 'shield-check'),
                new NavItem('التهديدات', 'overwatch.threats.index', 'bug-ant'),
                new NavItem('الحظر', 'overwatch.bans.index', 'no-symbol'),
            ]],
            ['label' => 'الحساب', 'items' => [
                new NavItem('الملف الشخصي', 'profile.edit', 'user-circle'),
            ]],
        ];
    }
}
