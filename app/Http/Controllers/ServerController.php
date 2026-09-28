<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Server;
use App\Models\Transaction;
use App\Support\Ledger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function index(): View
    {
        $servers = Server::with('account')->withCount('domains')
            ->orderByDesc('is_active')->orderBy('next_due_date')->get();

        return view('servers.index', [
            'servers' => $servers,
            'monthlyTotal' => $servers->where('is_active', true)->sum(fn (Server $s) => Ledger::monthly($s->cost, $s->billing_cycle)),
            'yearlyTotal' => $servers->where('is_active', true)->sum(fn (Server $s) => $s->yearlyCost()),
        ]);
    }

    public function create(): View
    {
        return view('servers.form', $this->formData(new Server(['billing_cycle' => 'monthly', 'is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $server = Server::create($this->validated($request));

        return redirect()->route('servers.show', $server)->with('status', 'Server added.');
    }

    public function show(Server $server): View
    {
        $server->load(['account', 'domains.project', 'transactions' => fn ($q) => $q->latest('date')]);

        return view('servers.show', [
            'server' => $server,
            'spent' => (float) $server->transactions->where('type', 'expense')->sum('amount'),
        ]);
    }

    public function edit(Server $server): View
    {
        return view('servers.form', $this->formData($server));
    }

    public function update(Request $request, Server $server): RedirectResponse
    {
        $server->update($this->validated($request));

        return redirect()->route('servers.show', $server)->with('status', 'Server updated.');
    }

    public function destroy(Server $server): RedirectResponse
    {
        $server->delete();

        return redirect()->route('servers.index')->with('status', 'Server deleted.');
    }

    /** Record a hosting bill as paid and roll the due date forward one cycle. */
    public function pay(Request $request, Server $server): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0'],
            'date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($server, $data) {
            $amount = $data['amount'] ?? $server->cost;

            if ($amount > 0) {
                Transaction::create([
                    'date' => $data['date'] ?? Carbon::today(),
                    'type' => 'expense',
                    'category' => 'hosting',
                    'amount' => $amount,
                    'server_id' => $server->id,
                    'account_id' => $server->account_id,
                    'payment_method' => $data['payment_method'] ?? null,
                    'description' => "Hosting: {$server->name} (".Ledger::cycleLabel($server->billing_cycle).')',
                ]);
            }

            $server->update(['next_due_date' => Ledger::advance($server->next_due_date, $server->billing_cycle)]);
        });

        return back()->with('status', "{$server->name} marked paid. Next due {$server->next_due_date->format('d M Y')}.");
    }

    private function formData(Server $server): array
    {
        return [
            'server' => $server,
            'accounts' => Account::orderBy('provider')->get(),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'ip_address' => ['nullable', 'string', 'max:100'],
            'plan' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'billing_cycle' => ['required', Rule::in(array_keys(config('ledger.billing_cycles')))],
            'cost' => ['required', 'numeric', 'min:0'],
            'next_due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        return $data + [
            'auto_renew' => $request->boolean('auto_renew'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
