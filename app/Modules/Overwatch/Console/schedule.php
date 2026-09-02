<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Overwatch scheduled tasks
|--------------------------------------------------------------------------
| Loaded by ModuleServiceProvider::loadSchedule() — kept in the Overwatch
| module rather than in the shared routes/console.php.
*/

Schedule::command('overwatch:prune')->daily();
