<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = Account::withCount(['domains', 'servers'])
            ->withSum(['transactions as spent_total' => fn ($q) => $q->where('type', 'expense')], 'amount')
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->orderBy('provider')->orderBy('label')
            ->get();

        return view('accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('accounts.form', ['account' => new Account(['type' => 'registrar'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = Account::create($this->validated($request));

        return redirect()->route('accounts.show', $account)->with('status', 'Account added.');
    }

    public function show(Account $account): View
    {
        $account->load(['domains.project', 'servers', 'transactions' => fn ($q) => $q->latest('date')->limit(50)]);

        return view('accounts.show', [
            'account' => $account,
            'spent' => (float) $account->transactions()->where('type', 'expense')->sum('amount'),
            'earned' => (float) $account->transactions()->where('type', 'income')->sum('amount'),
        ]);
    }

    public function edit(Account $account): View
    {
        return view('accounts.form', compact('account'));
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $account->update($this->validated($request));

        return redirect()->route('accounts.show', $account)->with('status', 'Account updated.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $account->delete();

        return redirect()->route('accounts.index')->with('status', 'Account deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(config('ledger.account_types')))],
            'login_email' => ['nullable', 'string', 'max:255'],
            'dashboard_url' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
