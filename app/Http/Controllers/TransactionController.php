<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Server;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->filtered($request);

        return view('transactions.index', [
            'transactions' => (clone $query)->with(['project', 'partner', 'account', 'domain', 'server'])
                ->latest('date')->latest('id')->paginate(50)->withQueryString(),
            'totals' => [
                'income' => (float) (clone $query)->where('type', 'income')->sum('amount'),
                'share' => (float) (clone $query)->where('type', 'income')->sum('partner_share'),
                'expense' => (float) (clone $query)->where('type', 'expense')->sum('amount'),
            ],
            'projects' => Project::orderBy('name')->get(),
            'accounts' => Account::orderBy('provider')->get(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->filtered($request)->with(['project', 'client', 'partner', 'account', 'domain', 'server'])
            ->orderBy('date')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Category', 'Amount', 'Partner share', 'Project', 'Client', 'Partner', 'Account', 'Domain', 'Server', 'Method', 'Reference', 'Description']);
            foreach ($rows as $t) {
                fputcsv($out, [
                    $t->date->toDateString(), $t->type, $t->category_label, $t->amount, $t->partner_share,
                    $t->project?->name, $t->client?->name, $t->partner?->name, $t->account?->display_name,
                    $t->domain?->name, $t->server?->name, $t->payment_method, $t->reference, $t->description,
                ]);
            }
            fclose($out);
        }, 'transactions-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function create(Request $request): View
    {
        return view('transactions.form', $this->formData(new Transaction([
            'date' => Carbon::today(),
            'type' => $request->input('type', 'expense'),
            'category' => $request->input('category'),
            'project_id' => $request->input('project'),
            'domain_id' => $request->input('domain'),
            'server_id' => $request->input('server'),
            'account_id' => $request->input('account'),
            'partner_id' => $request->input('partner'),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        Transaction::create($this->validated($request));

        $back = (string) $request->input('redirect_to');
        $safe = ($back === url('/') || str_starts_with($back, url('/').'/')) && ! str_contains($back, '/transactions/create');

        return redirect($safe ? $back : route('transactions.index'))->with('status', 'Transaction saved.');
    }

    public function edit(Transaction $transaction): View
    {
        return view('transactions.form', $this->formData($transaction));
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $transaction->update($this->validated($request));

        return redirect()->route('transactions.index')->with('status', 'Transaction updated.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return back()->with('status', 'Transaction deleted.');
    }

    private function filtered(Request $request)
    {
        return Transaction::query()
            ->when($request->input('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->input('category'), fn ($q, $v) => $q->where('category', $v))
            ->when($request->input('project'), fn ($q, $v) => $q->where('project_id', $v))
            ->when($request->input('account'), fn ($q, $v) => $q->where('account_id', $v))
            ->when($request->input('from'), fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->input('to'), fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($request->input('q'), fn ($q, $v) => $q->where(fn ($q) => $q
                ->where('description', 'like', "%$v%")->orWhere('reference', 'like', "%$v%")));
    }

    private function formData(Transaction $transaction): array
    {
        return [
            'transaction' => $transaction,
            'projects' => Project::with('partner')->orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(),
            'partners' => Partner::orderBy('name')->get(),
            'accounts' => Account::orderBy('provider')->get(),
            'domains' => Domain::orderBy('name')->get(),
            'servers' => Server::orderBy('name')->get(),
        ];
    }

    private function validated(Request $request): array
    {
        $type = $request->input('type');
        $categories = array_keys(config($type === 'income' ? 'ledger.income_categories' : 'ledger.expense_categories'));

        $data = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'category' => ['required', Rule::in($categories)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'partner_share' => ['nullable', 'numeric', 'min:0'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'partner_id' => ['nullable', 'exists:partners,id', Rule::requiredIf($request->input('category') === 'partner_payout')],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'domain_id' => ['nullable', 'exists:domains,id'],
            'server_id' => ['nullable', 'exists:servers,id'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $project = isset($data['project_id']) ? Project::find($data['project_id']) : null;

        if ($data['type'] === 'income') {
            $data['client_id'] ??= $project?->client_id;

            // Ad money on a shared project: split automatically unless I typed a figure.
            if (! $request->filled('partner_share')) {
                $data['partner_share'] = ($data['category'] === 'ad_revenue' && $project?->isShared())
                    ? round($data['amount'] * $project->partner_share_percent / 100, 2)
                    : 0;
            }

            $data['partner_share'] = min((float) $data['partner_share'], (float) $data['amount']);
        } else {
            $data['partner_share'] = 0;
        }

        return $data;
    }
}
