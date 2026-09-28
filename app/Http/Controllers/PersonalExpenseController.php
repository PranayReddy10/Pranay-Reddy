<?php

namespace App\Http\Controllers;

use App\Models\PersonalBudget;
use App\Models\PersonalExpense;
use App\Support\Finance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonalExpenseController extends Controller
{
    public function index(Request $request, Finance $finance): View
    {
        $month = $this->month($request);
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $expenses = PersonalExpense::whereBetween('date', [$from, $to])
            ->when($request->input('category'), fn ($q, $c) => $q->where('category', $c))
            ->latest('date')->latest('id')->get();

        $byCategory = PersonalExpense::whereBetween('date', [$from, $to])
            ->selectRaw('category, SUM(amount) as total')->groupBy('category')
            ->pluck('total', 'category')->map(fn ($v) => (float) $v);

        $budgets = PersonalBudget::pluck('monthly_limit', 'category')->map(fn ($v) => (float) $v);

        // Last 12 months of personal spending vs what the business earned me.
        $trend = collect();
        $spent = PersonalExpense::whereBetween('date', [$month->copy()->subMonths(11)->startOfMonth(), $to])
            ->get(['date', 'amount'])->groupBy(fn ($e) => $e->date->format('Y-m'))->map->sum('amount');
        foreach ($finance->monthly($month->copy()->subMonths(11)->startOfMonth(), $to) as $m) {
            $trend->push($m + ['personal' => round((float) ($spent[$m['key']] ?? 0), 2)]);
        }

        $business = $finance->summary($from, $to);
        $total = (float) $byCategory->sum();

        return view('personal.index', [
            'month' => $month,
            'expenses' => $expenses,
            'byCategory' => $byCategory->sortDesc(),
            'budgets' => $budgets,
            'total' => $total,
            'budgetTotal' => (float) $budgets->sum(),
            'business' => $business,
            'savings' => $business['net'] - $total,
            'trend' => $trend,
            'daysLeft' => $month->isSameMonth(Carbon::today()) ? Carbon::today()->diffInDays($to->copy()->startOfDay()) + 1 : 0,
        ]);
    }

    public function create(): View
    {
        return view('personal.form', ['expense' => new PersonalExpense(['date' => Carbon::today()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $expense = PersonalExpense::create($this->validated($request));

        if ($request->boolean('add_another')) {
            return redirect()->route('personal.create')->with('status', 'Saved. Add the next one.');
        }

        return redirect()->route('personal.index', ['month' => $expense->date->format('Y-m')])->with('status', 'Personal expense saved.');
    }

    public function edit(PersonalExpense $personal): View
    {
        return view('personal.form', ['expense' => $personal]);
    }

    public function update(Request $request, PersonalExpense $personal): RedirectResponse
    {
        $personal->update($this->validated($request));

        return redirect()->route('personal.index', ['month' => $personal->date->format('Y-m')])->with('status', 'Personal expense updated.');
    }

    public function destroy(PersonalExpense $personal): RedirectResponse
    {
        $personal->delete();

        return redirect()->route('personal.index', ['month' => $personal->date->format('Y-m')])->with('status', 'Personal expense deleted.');
    }

    public function budgets(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'budgets' => ['array'],
            'budgets.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach (config('ledger.personal_categories') as $key => $label) {
            $limit = $data['budgets'][$key] ?? null;
            if ($limit === null || (float) $limit <= 0) {
                PersonalBudget::where('category', $key)->delete();
            } else {
                PersonalBudget::updateOrCreate(['category' => $key], ['monthly_limit' => $limit]);
            }
        }

        return back()->with('status', 'Monthly budgets saved.');
    }

    private function month(Request $request): Carbon
    {
        try {
            return Carbon::createFromFormat('!Y-m', (string) $request->input('month', now()->format('Y-m')));
        } catch (\Throwable) {
            return Carbon::today()->startOfMonth();
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'category' => ['required', Rule::in(array_keys(config('ledger.personal_categories')))],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'paid_to' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
