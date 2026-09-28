<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Server;
use App\Models\Transaction;
use App\Support\Finance;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Finance $finance): View
    {
        $today = Carbon::today();

        return view('dashboard', [
            'month' => $finance->summary($today->copy()->startOfMonth(), $today->copy()->endOfMonth()),
            'year' => $finance->summary($today->copy()->startOfYear(), $today->copy()->endOfYear()),
            'allTime' => $finance->summary(Carbon::create(2000), $today->copy()->endOfDay()),
            'series' => $finance->monthly($today->copy()->subMonths(11)->startOfMonth(), $today),
            'upcoming' => $finance->upcoming(config('ledger.due_soon_days')),
            'recurring' => $finance->recurring(),
            'outstanding' => $finance->outstanding(),
            'personal' => $finance->personalSpending($today->copy()->startOfMonth(), $today->copy()->endOfMonth()),
            'counts' => [
                'projects' => Project::active()->count(),
                'domains' => Domain::active()->count(),
                'servers' => Server::active()->count(),
            ],
            'partners' => Partner::all()->map(fn (Partner $p) => ['partner' => $p, 'balance' => $p->balance()])
                ->filter(fn ($row) => abs($row['balance']) > 0.009),
            'recent' => Transaction::with(['project', 'partner'])->latest('date')->latest('id')->limit(8)->get(),
        ]);
    }
}
