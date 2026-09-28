<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function index(): View
    {
        $partners = Partner::withCount('projects')->orderBy('name')->get()
            ->map(fn (Partner $p) => [
                'partner' => $p,
                'earned' => $p->shareEarned(),
                'paid' => $p->paidOut(),
                'balance' => $p->balance(),
            ]);

        return view('partners.index', compact('partners'));
    }

    public function create(): View
    {
        return view('partners.form', ['partner' => new Partner]);
    }

    public function store(Request $request): RedirectResponse
    {
        $partner = Partner::create($this->validated($request));

        return redirect()->route('partners.show', $partner)->with('status', 'Partner added.');
    }

    public function show(Partner $partner): View
    {
        $partner->load('projects');
        $projectIds = $partner->projects->pluck('id');

        $ledger = Transaction::with('project')
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('type', 'income')->whereIn('project_id', $projectIds)->where('partner_share', '>', 0))
                ->orWhere(fn ($q) => $q->where('category', 'partner_payout')->where('partner_id', $partner->id)))
            ->latest('date')->latest('id')
            ->get();

        return view('partners.show', [
            'partner' => $partner,
            'ledger' => $ledger,
            'earned' => $partner->shareEarned(),
            'paid' => $partner->paidOut(),
            'balance' => $partner->balance(),
        ]);
    }

    public function edit(Partner $partner): View
    {
        return view('partners.form', compact('partner'));
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $partner->update($this->validated($request));

        return redirect()->route('partners.show', $partner)->with('status', 'Partner updated.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $partner->delete();

        return redirect()->route('partners.index')->with('status', 'Partner deleted.');
    }

    /** Record money I sent to the partner for their share. */
    public function payout(Request $request, Partner $partner): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Transaction::create($data + [
            'type' => 'expense',
            'category' => 'partner_payout',
            'partner_id' => $partner->id,
            'description' => $data['description'] ?? "Ad share payout to {$partner->name}",
        ]);

        return back()->with('status', 'Payout recorded.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'upi_or_bank' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
