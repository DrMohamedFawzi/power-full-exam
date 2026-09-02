<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Overwatch\Http\Requests\IndexThreatsRequest;
use App\Modules\Overwatch\Queries\ThreatsQuery;
use Illuminate\Contracts\View\View;

final class ThreatsController extends Controller
{
    public function index(IndexThreatsRequest $request, ThreatsQuery $threats): View
    {
        $filters = $request->validated();

        return view('overwatch.threats.index', [
            'threats' => $threats($filters),
            'filters' => $filters,
        ]);
    }
}
