<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Wires up every bounded context listed in config('aegis.modules').
 *
 * A module needs no registration of its own. Everything is convention:
 *
 *     routes.php | Routes/*.php      HTTP routes
 *     Policies/*Policy.php           authorization, matched to the same-named model
 *     Console/Commands/*.php         Artisan commands
 *     Console/schedule.php           scheduled tasks
 *     events.php                     event => listeners
 */
final class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ((array) config('aegis.modules', []) as $module) {
            $this->loadRoutes($module);
            $this->registerPolicies($module);
            $this->registerCommands($module);
            $this->loadSchedule($module);
            $this->registerListeners($module);
        }
    }

    /**
     * Events are how a module reacts to something happening in a module BELOW
     * it without that module knowing it exists — the only sanctioned way to
     * communicate upward against the dependency rule.
     */
    private function registerListeners(string $module): void
    {
        $path = app_path("Modules/{$module}/events.php");

        if (! is_file($path)) {
            return;
        }

        /** @var array<class-string, list<class-string>> $events */
        $events = require $path;

        foreach ($events as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    /**
     * A module may keep one `routes.php`, or split its routes across `Routes/*.php`
     * when several surfaces of the same context are developed independently.
     */
    private function loadRoutes(string $module): void
    {
        $paths = array_merge(
            (array) glob(app_path("Modules/{$module}/routes.php")),
            (array) glob(app_path("Modules/{$module}/Routes/*.php")),
        );

        foreach (array_filter($paths, 'is_file') as $path) {
            $isApi = Str::endsWith($path, 'api.php') || str_contains($path, '/Routes/api.php');
            if ($isApi) {
                Route::prefix('api')->middleware('api')->group($path);
            } else {
                Route::middleware('web')->group($path);
            }
        }
    }

    /**
     * Maps App\Modules\{Module}\Models\Foo => App\Modules\{Module}\Policies\FooPolicy.
     */
    private function registerPolicies(string $module): void
    {
        $directory = app_path("Modules/{$module}/Policies");

        if (! is_dir($directory)) {
            return;
        }

        foreach (glob($directory.'/*Policy.php') ?: [] as $file) {
            $policy = basename($file, '.php');
            $model = Str::beforeLast($policy, 'Policy');

            $modelClass = "App\\Modules\\{$module}\\Models\\{$model}";
            $policyClass = "App\\Modules\\{$module}\\Policies\\{$policy}";

            if (class_exists($modelClass) && class_exists($policyClass)) {
                Gate::policy($modelClass, $policyClass);
            }
        }
    }

    /**
     * A module may keep Artisan commands in `Console/Commands/` or directly
     * in `Console/` (e.g. FlushHeartbeatsCommand); picked up by convention
     * the same way routes and policies are.
     */
    private function registerCommands(string $module): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $commands = [];

        // ── Console/Commands/*.php (standard location) ──────────────────
        $commandsDir = app_path("Modules/{$module}/Console/Commands");
        if (is_dir($commandsDir)) {
            foreach (glob($commandsDir.'/*.php') ?: [] as $file) {
                $commands[] = "App\\Modules\\{$module}\\Console\\Commands\\".basename($file, '.php');
            }
        }

        // ── Console/*.php (flat — e.g. FlushHeartbeatsCommand) ─────────
        $consoleDir = app_path("Modules/{$module}/Console");
        if (is_dir($consoleDir)) {
            foreach (glob($consoleDir.'/*.php') ?: [] as $file) {
                $filename = basename($file, '.php');
                if ($filename !== 'schedule') {
                    $commands[] = "App\\Modules\\{$module}\\Console\\".$filename;
                }
            }
        }

        if (! empty($commands)) {
            $this->commands($commands);
        }
    }

    /**
     * A module may keep one `Console/schedule.php` defining its own
     * `Schedule::command(...)` entries — loaded the same way routes are,
     * without any module needing to touch the shared routes/console.php.
     */
    private function loadSchedule(string $module): void
    {
        $path = app_path("Modules/{$module}/Console/schedule.php");

        if (is_file($path)) {
            require $path;
        }
    }
}
