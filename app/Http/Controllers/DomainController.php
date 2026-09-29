<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DomainController extends Controller
{
    public function index(Request $request): View
    {
        $domains = Domain::with(['account', 'server', 'project'])
            ->when($request->input('q'), fn ($q, $term) => $q->where('name', 'like', "%$term%"))
            ->when($request->input('account'), fn ($q, $id) => $q->where('account_id', $id))
            ->when($request->input('server'), fn ($q, $id) => $q->where('server_id', $id))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s), fn ($q) => $q->where('status', 'active'))
            ->orderByRaw('expires_on IS NULL')->orderBy('expires_on')
            ->paginate(50)->withQueryString();

        return view('domains.index', [
            'domains' => $domains,
            'accounts' => Account::orderBy('provider')->get(),
            'servers' => Server::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('domains.form', $this->formData(new Domain([
            'paid_by' => 'me',
            'status' => 'active',
            'project_id' => $request->input('project'),
            'registered_on' => Carbon::today(),
            'expires_on' => Carbon::today()->addYear(),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $domain = DB::transaction(function () use ($request, $data) {
            $domain = Domain::create($data);

            // Optionally book the purchase as an expense straight away.
            if ($request->boolean('record_purchase') && $request->filled('purchase_amount')) {
                Transaction::create([
                    'date' => $domain->registered_on ?? Carbon::today(),
                    'type' => 'expense',
                    'category' => 'domain',
                    'amount' => $request->input('purchase_amount'),
                    'domain_id' => $domain->id,
                    'account_id' => $domain->account_id,
                    'project_id' => $domain->project_id,
                    'description' => "Domain purchase: {$domain->name}",
                ]);
            }

            return $domain;
        });

        return redirect()->route('domains.show', $domain)->with('status', 'Domain added.');
    }

    public function show(Domain $domain): View
    {
        $domain->load(['account', 'server', 'project.client', 'transactions' => fn ($q) => $q->latest('date')]);

        return view('domains.show', ['domain' => $domain]);
    }

    public function edit(Domain $domain): View
    {
        return view('domains.form', $this->formData($domain));
    }

    public function update(Request $request, Domain $domain): RedirectResponse
    {
        $domain->update($this->validated($request, $domain));

        return redirect()->route('domains.show', $domain)->with('status', 'Domain updated.');
    }

    public function destroy(Domain $domain): RedirectResponse
    {
        $domain->delete();

        return redirect()->route('domains.index')->with('status', 'Domain deleted.');
    }

    /**
     * Mark a domain renewed and (if I pay) book the cost. The new expiry is either the exact
     * date given, or, for one-tap buttons, the current expiry plus N years.
     */
    public function renew(Request $request, Domain $domain): RedirectResponse
    {
        $data = $request->validate([
            'years' => ['nullable', 'integer', 'min:1', 'max:10'],
            'new_expires_on' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        $base = $domain->expires_on ?? Carbon::today();
        $newExpiry = isset($data['new_expires_on'])
            ? Carbon::parse($data['new_expires_on'])
            : $base->copy()->addYearsNoOverflow((int) ($data['years'] ?? 1));
        $years = max(1, (int) round($base->diffInMonths($newExpiry) / 12));

        DB::transaction(function () use ($domain, $data, $years, $newExpiry) {
            $amount = $data['amount'] ?? $domain->renewal_cost * $years;

            if ($domain->paid_by === 'me' && $amount > 0) {
                Transaction::create([
                    'date' => $data['date'] ?? Carbon::today(),
                    'type' => 'expense',
                    'category' => 'domain',
                    'amount' => $amount,
                    'domain_id' => $domain->id,
                    'account_id' => $domain->account_id,
                    'project_id' => $domain->project_id,
                    'payment_method' => $data['payment_method'] ?? null,
                    'description' => "Domain renewal: {$domain->name} ({$years}y)",
                ]);
            }

            $domain->update(['expires_on' => $newExpiry, 'status' => 'active']);
        });

        return back()->with('status', "{$domain->name} renewed until {$domain->expires_on->format('d M Y')}.");
    }

    private function formData(Domain $domain): array
    {
        return [
            'domain' => $domain,
            'accounts' => Account::orderBy('provider')->get(),
            'servers' => Server::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?Domain $domain = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('domains', 'name')->ignore($domain)],
            'project_id' => ['nullable', 'exists:projects,id'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'server_id' => ['nullable', 'exists:servers,id'],
            'registered_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date'],
            'renewal_cost' => ['required', 'numeric', 'min:0'],
            'paid_by' => ['required', Rule::in(['me', 'client'])],
            'status' => ['required', Rule::in(array_keys(config('ledger.domain_statuses')))],
            'notes' => ['nullable', 'string'],
        ]);

        $data['name'] = strtolower(trim($data['name']));

        return $data + ['auto_renew' => $request->boolean('auto_renew')];
    }
}
