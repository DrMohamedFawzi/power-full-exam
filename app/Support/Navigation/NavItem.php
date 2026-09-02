<?php

declare(strict_types=1);

namespace App\Support\Navigation;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

final readonly class NavItem
{
    public function __construct(
        public string $label,
        public string $route,
        public string $icon,
        public ?string $badge = null,
    ) {}

    public function url(): string
    {
        return Route::has($this->route) ? route($this->route) : '#';
    }

    /** Active when the current route matches, or is nested beneath it. */
    public function isActive(): bool
    {
        $current = (string) request()->route()?->getName();

        return $current === $this->route
            || Str::startsWith($current, Str::beforeLast($this->route, '.index').'.');
    }
}
