<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Transaction;
use App\Support\Ledger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::with(['client', 'partner'])->withCount('domains')
            ->withSum(['transactions as income_total' => fn ($q) => $q->where('type', 'income')], 'amount')
            ->withSum(['transactions as share_total' => fn ($q) => $q->where('type', 'income')], 'partner_share')
            ->withSum(['transactions as spent_total' => fn ($q) => $q->where('type', 'expense')->where('category', '!=', 'partner_payout')], 'amount')
            ->when($request->input('q'), fn ($q, $term) => $q->where('name', 'like', "%$term%"))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->boolean('shared'), fn ($q) => $q->whereNotNull('partner_id')->where('partner_share_percent', '>', 0))
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'paused' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->paginate(30)->withQueryString();

        return view('projects.index', compact('projects'));
    }

    public function create(Request $request): View
    {
        return view('projects.form', $this->formData(new Project([
            'type' => 'client',
            'status' => 'active',
            'billing_cycle' => 'none',
            'client_id' => $request->input('client'),
            'started_on' => Carbon::today(),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $project = Project::create($this->validated($request));

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function show(Project $project): View
    {
        $project->load(['client', 'partner', 'domains.account', 'domains.server', 'invoices.items', 'invoices.payments']);
        $transactions = $project->transactions()->latest('date')->latest('id')->get();

        $income = (float) $transactions->where('type', 'income')->sum('amount');
        $share = (float) $transactions->where('type', 'income')->sum('partner_share');
        $spent = (float) $transactions->where('type', 'expense')->where('category', '!=', 'partner_payout')->sum('amount');

        return view('projects.show', [
            'project' => $project,
            'transactions' => $transactions,
            'income' => $income,
            'share' => $share,
            'spent' => $spent,
            'net' => $income - $share - $spent,
            'adRevenue' => (float) $transactions->where('category', 'ad_revenue')->sum('amount'),
            'billing' => $project->billingSummary(),
        ]);
    }

    public function edit(Project $project): View
    {
        return view('projects.form', $this->formData($project));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validated($request));

        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }

    /** Client paid this cycle's bill: book income and roll the billing date forward. */
    public function collect(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0'],
            'date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($project, $data) {
            $amount = $data['amount'] ?? $project->billing_amount;

            if ($amount > 0) {
                Transaction::create([
                    'date' => $data['date'] ?? Carbon::today(),
                    'type' => 'income',
                    'category' => 'client_payment',
                    'amount' => $amount,
                    'project_id' => $project->id,
                    'client_id' => $project->client_id,
                    'payment_method' => $data['payment_method'] ?? null,
                    'description' => "{$project->name} · ".Ledger::cycleLabel($project->billing_cycle).' fee',
                ]);
            }

            $project->update(['next_billing_date' => Ledger::advance($project->next_billing_date, $project->billing_cycle)]);
        });

        return back()->with('status', "Payment recorded. Next bill {$project->next_billing_date->format('d M Y')}.");
    }

    private function formData(Project $project): array
    {
        return [
            'project' => $project,
            'clients' => Client::orderBy('name')->get(),
            'partners' => Partner::orderBy('name')->get(),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'partner_id' => ['nullable', 'exists:partners,id'],
            'partner_share_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'type' => ['required', Rule::in(array_keys(config('ledger.project_types')))],
            'status' => ['required', Rule::in(array_keys(config('ledger.project_statuses')))],
            'build_fee' => ['nullable', 'numeric', 'min:0'],
            'billing_cycle' => ['required', Rule::in(['none', ...array_keys(config('ledger.billing_cycles'))])],
            'billing_amount' => ['nullable', 'numeric', 'min:0'],
            'next_billing_date' => ['nullable', 'date'],
            'started_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['partner_share_percent'] = $data['partner_id'] ? ($data['partner_share_percent'] ?? 0) : 0;
        $data['build_fee'] ??= 0;
        $data['billing_amount'] ??= 0;

        return $data;
    }
}
