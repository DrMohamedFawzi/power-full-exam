<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lets every module render pages with <x-layouts.app>, <x-layouts.guest>,
        // <x-layouts.exam> against resources/views/layouts/*.blade.php.
        Blade::anonymousComponentPath(resource_path('views'));

        // Every module's models live under App\Modules\{Module}\Models, but every
        // factory sits flat in database/factories/{Model}Factory. Laravel's
        // default guesser assumes App\Models\* and gets this wrong for every
        // module, so it is repointed once here instead of per model.
        Factory::guessFactoryNamesUsing(
            static fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory',
        );
    }
}
