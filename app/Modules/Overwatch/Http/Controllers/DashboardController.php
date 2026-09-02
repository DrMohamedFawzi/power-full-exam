<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Overwatch\Queries\DashboardQuery;
use Illuminate\Contracts\View\View;

final class DashboardController extends Controller
{
    public function __invoke(DashboardQuery $dashboard): View
    {
        return view('overwatch.dashboard', $dashboard());
    }
}
