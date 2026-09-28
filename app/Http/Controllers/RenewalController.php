<?php

namespace App\Http\Controllers;

use App\Support\Finance;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RenewalController extends Controller
{
    public function __invoke(Request $request, Finance $finance): View
    {
        $days = (int) $request->input('days', 90);
        $days = in_array($days, [30, 60, 90, 180, 365], true) ? $days : 90;

        $items = $finance->upcoming($days);

        return view('renewals.index', [
            'days' => $days,
            'items' => $items,
            'totalOut' => $items->where('direction', 'out')->sum('amount'),
            'totalIn' => $items->where('direction', 'in')->sum('amount'),
        ]);
    }
}
