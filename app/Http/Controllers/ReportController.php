<?php

namespace App\Http\Controllers;

use App\Support\Finance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request, Finance $finance): View
    {
        $year = (int) $request->input('year', now()->year);
        $from = Carbon::create($year)->startOfYear();
        $to = Carbon::create($year)->endOfYear();

        return view('reports.index', [
            'year' => $year,
            'summary' => $finance->summary($from, $to),
            'previous' => $finance->summary($from->copy()->subYear(), $to->copy()->subYear()),
            'months' => $finance->monthly($from, $to),
            'incomeByCategory' => $finance->byCategory('income', $from, $to),
            'spendingByCategory' => $finance->byCategory('expense', $from, $to),
            'projects' => $finance->projectBreakdown($from, $to),
            'accounts' => $finance->spendingByAccount($from, $to),
            'recurring' => $finance->recurring(),
        ]);
    }
}
