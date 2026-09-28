<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Transaction;
use App\Support\Ledger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::with(['client', 'project', 'items', 'payments'])
            ->when($request->input('project'), fn ($q, $id) => $q->where('project_id', $id))
            ->when($request->input('client'), fn ($q, $id) => $q->where('client_id', $id))
            ->latest('issue_date')->latest('id')
            ->get();

        if ($state = $request->input('state')) {
            $invoices = $invoices->filter(fn (Invoice $i) => $state === 'due'
                ? in_array($i->state(), ['unpaid', 'partial', 'overdue'], true)
                : $i->state() === $state)->values();
        }

        $open = $invoices->filter(fn (Invoice $i) => ! in_array($i->status, ['draft', 'cancelled'], true));

        return view('invoices.index', [
            'invoices' => $invoices,
            'totals' => [
                'billed' => $open->sum(fn (Invoice $i) => $i->total()),
                'paid' => $open->sum(fn (Invoice $i) => $i->paid()),
                'balance' => $open->sum(fn (Invoice $i) => $i->balance()),
                'overdue' => $open->filter(fn (Invoice $i) => $i->state() === 'overdue')->sum(fn (Invoice $i) => $i->balance()),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $project = Project::find($request->input('project'));
        $invoice = new Invoice([
            'project_id' => $project?->id,
            'client_id' => $project?->client_id ?? $request->input('client'),
            'issue_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(7),
            'status' => 'sent',
            'notes' => Setting::get('invoice_terms'),
        ]);

        // Pre-fill a sensible first draft from the project.
        $items = collect();
        if ($project) {
            $invoice->title = $request->boolean('final') ? "Final bill · {$project->name}" : $project->name;
            if ($project->build_fee > 0) {
                $items->push(['description' => "Website design & development — {$project->name}", 'quantity' => 1, 'rate' => $project->build_fee]);
            }
            if ($project->isRecurring() && ! $request->boolean('final')) {
                $items->push(['description' => 'Hosting & maintenance ('.strtolower(Ledger::cycleLabel($project->billing_cycle)).')', 'quantity' => 1, 'rate' => $project->billing_amount]);
            }
        }

        return view('invoices.form', $this->formData($invoice, $items));
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $items] = $this->validated($request);

        $invoice = DB::transaction(function () use ($data, $items) {
            $invoice = Invoice::create($data);
            $invoice->items()->createMany($items);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('status', "Bill {$invoice->number} created.");
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['items', 'payments', 'client', 'project']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'profile' => Setting::allValues(),
            'unlinked' => $invoice->project_id
                ? Transaction::where('project_id', $invoice->project_id)->where('type', 'income')
                    ->where('category', '!=', 'ad_revenue')->whereNull('invoice_id')->latest('date')->get()
                : collect(),
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        $invoice->load('items');

        return view('invoices.form', $this->formData($invoice, $invoice->items->map->only(['description', 'quantity', 'rate'])));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        [$data, $items] = $this->validated($request, $invoice);

        DB::transaction(function () use ($invoice, $data, $items) {
            $invoice->update($data);
            $invoice->items()->delete();
            $invoice->items()->createMany($items);
        });

        return redirect()->route('invoices.show', $invoice)->with('status', 'Bill updated.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('status', 'Bill deleted. Its payments are kept as project income.');
    }

    /** Record money received against this bill. */
    public function pay(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->load(['items', 'payments']);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'category' => ['required', Rule::in(array_keys(config('ledger.income_categories')))],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        Transaction::create($data + [
            'type' => 'income',
            'invoice_id' => $invoice->id,
            'project_id' => $invoice->project_id,
            'client_id' => $invoice->client_id,
            'description' => "Payment for {$invoice->number}",
        ]);

        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent']);
        }

        return back()->with('status', 'Payment recorded.');
    }

    /** Attach an existing project payment (e.g. an advance) to this bill. */
    public function link(Request $request, Invoice $invoice): RedirectResponse
    {
        $transaction = Transaction::where('type', 'income')->whereNull('invoice_id')
            ->where('project_id', $invoice->project_id)
            ->findOrFail($request->input('transaction_id'));

        $transaction->update(['invoice_id' => $invoice->id]);

        return back()->with('status', 'Payment linked to this bill.');
    }

    public function unlink(Invoice $invoice, Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->invoice_id === $invoice->id, 404);
        $transaction->update(['invoice_id' => null]);

        return back()->with('status', 'Payment unlinked (still counted as project income).');
    }

    /** New share link — the old one stops working. */
    public function regenerateLink(Invoice $invoice): RedirectResponse
    {
        $invoice->forceFill(['share_token' => Str::random(40)])->save();

        return back()->with('status', 'New share link created. The old link no longer works.');
    }

    /** The client-facing bill, reachable only through its secret link. */
    public function public(string $token): View
    {
        $invoice = Invoice::with(['items', 'payments', 'client', 'project'])
            ->where('share_token', $token)->where('status', '!=', 'draft')->firstOrFail();

        return view('invoices.public', ['invoice' => $invoice, 'profile' => Setting::allValues()]);
    }

    private function formData(Invoice $invoice, $items): array
    {
        $items = collect(old('items', $items))->values();

        return [
            'invoice' => $invoice,
            'items' => $items->isEmpty() ? collect([['description' => '', 'quantity' => 1, 'rate' => '']]) : $items,
            'projects' => Project::with('client')->orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(),
        ];
    }

    /** @return array{0:array,1:array} */
    private function validated(Request $request, ?Invoice $invoice = null): array
    {
        $data = $request->validate([
            'number' => ['nullable', 'string', 'max:50', Rule::unique('invoices', 'number')->ignore($invoice)],
            'title' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'status' => ['required', Rule::in(['draft', 'sent', 'cancelled'])],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_label' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = collect($data['items'])
            ->filter(fn ($i) => filled($i['description'] ?? null))
            ->values()
            ->map(fn ($i, $pos) => [
                'description' => $i['description'],
                'quantity' => $i['quantity'] ?? 1,
                'rate' => $i['rate'] ?? 0,
                'position' => $pos,
            ])->all();

        if (! $items) {
            throw ValidationException::withMessages(['items' => 'Add at least one line item.']);
        }

        if (empty($data['client_id']) && ! empty($data['project_id'])) {
            $data['client_id'] = Project::find($data['project_id'])?->client_id;
        }

        unset($data['items']);
        $data['discount'] ??= 0;
        $data['tax_percent'] ??= 0;
        if (blank($data['number'] ?? null)) {
            unset($data['number']);
        }

        return [$data, $items];
    }
}
