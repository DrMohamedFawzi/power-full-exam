<?php

declare(strict_types=1);

use App\Modules\Overwatch\Listeners\RecordFailedLoginAttempt;
use Illuminate\Auth\Events\Failed;

/**
 * Event => listeners for this module. Auto-registered by ModuleServiceProvider.
 *
 * This is how a module reacts to something that happens in a module BELOW it
 * without that module having to know it exists.
 */
return [
    Failed::class => [
        RecordFailedLoginAttempt::class,
    ],
];
