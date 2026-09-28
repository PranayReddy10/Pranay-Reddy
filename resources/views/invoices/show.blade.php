@php
    $client = $invoice->client;
    $digits = preg_replace('/\D/', '', (string) $client?->phone);
    $digits = strlen($digits) === 10 ? '91'.$digits : $digits;
    $message = 'Hi '.($client?->name ?? 'there').', here is your bill '.$invoice->number
        .($invoice->title ? ' for '.$invoice->title : '').'. Total '.money($invoice->total())
        .($invoice->paid() > 0 ? ', paid '.money($invoice->paid()) : '')
        .', balance due '.money($invoice->balance())
        .($invoice->due_date && $invoice->balance() > 0 ? ' by '.$invoice->due_date->format('d M Y') : '')
        .'. View / download: '.$invoice->shareUrl();
    $wa = 'https://wa.me/'.($digits ?: '').'?text='.rawurlencode($message);
    $mail = 'mailto:'.($client?->email ?? '').'?subject='.rawurlencode('Bill '.$invoice->number).'&body='.rawurlencode($message);
@endphp
<x-layout :title="'Bill '.$invoice->number" :crumb="'<a href=\''.route('invoices.index').'\'>Bills</a>'.($invoice->project ? ' · <a href=\''.route('projects.show', $invoice->project).'\'>'.e($invoice->project->name).'</a>' : '')">
    <x-slot:actions>
        <button type="button" class="btn" onclick="window.print()"><x-icon name="download"/> PDF / print</button>
        <a class="btn" href="{{ route('invoices.edit', $invoice) }}"><x-icon name="edit"/> Edit</a>
    </x-slot:actions>

    <div class="grid grid-4 no-print">
        <x-stat label="Bill total" :value="money($invoice->total())"/>
        <x-stat label="Received" :value="money($invoice->paid())" tone="in" :hint="$invoice->payments->count().' '.\Illuminate\Support\Str::plural('payment', $invoice->payments->count())"/>
        <x-stat label="Balance due" :value="money($invoice->balance())" :tone="$invoice->balance() > 0 ? 'out' : 'in'"/>
        <x-stat label="Status" :value="$invoice->stateLabel()" :hint="$invoice->due_date ? 'Due '.$invoice->due_date->format('d M Y') : null"/>
    </div>

    <div class="grid grid-main">
        <div class="card bill-card">@include('invoices._document')</div>

        <div class="no-print">
            <div class="card">
                <div class="card-head"><h2>Share with client</h2></div>
                <div class="card-body stack-sm">
                    @if ($invoice->status === 'draft')
                        <p class="muted" style="margin:0">This bill is a <strong>draft</strong>. Change its status to “Sent” to enable the share link.</p>
                    @else
                        <div class="copy-row">
                            <input type="text" readonly value="{{ $invoice->shareUrl() }}" data-copy-source onclick="this.select()">
                            <button type="button" class="btn" data-copy>Copy</button>
                        </div>
                        <div class="share-buttons">
                            <a class="btn btn-in" href="{{ $wa }}" target="_blank" rel="noopener">WhatsApp</a>
                            <a class="btn" href="{{ $mail }}">Email</a>
                            <button type="button" class="btn" data-share data-title="Bill {{ $invoice->number }}" data-text="{{ $message }}" data-url="{{ $invoice->shareUrl() }}" hidden>Share…</button>
                            <a class="btn" href="{{ $invoice->shareUrl() }}" target="_blank" rel="noopener"><x-icon name="external"/> Open</a>
                        </div>
                        <small>Anyone with the link can view this bill (no login). It shows live payments and balance.</small>
                        <form method="POST" action="{{ route('invoices.regenerate', $invoice) }}" onsubmit="return confirm('Create a new link? The current link will stop working.')">
                            @csrf <button type="submit" class="btn btn-sm">Reset link</button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($invoice->status !== 'cancelled' && $invoice->balance() > 0)
                <div class="card">
                    <div class="card-head"><h2>Record payment received</h2></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('invoices.pay', $invoice) }}" class="stack">
                            @csrf
                            <x-input name="amount" label="Amount" type="number" step="0.01" min="0.01" :value="$invoice->balance()" required help="Enter less for an advance / part payment."/>
                            <x-input name="date" label="Date" type="date" :value="now()->toDateString()" required/>
                            <x-select name="category" label="Count as" :options="config('ledger.income_categories')" value="build_fee"/>
                            <x-select name="payment_method" label="Method" :options="array_combine(config('ledger.payment_methods'), config('ledger.payment_methods'))" placeholder="—"/>
                            <x-input name="reference" label="UTR / reference"/>
                            <button type="submit" class="btn btn-primary">Save payment</button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($invoice->payments->isNotEmpty())
                <div class="card">
                    <div class="card-head"><h2>Payments on this bill</h2></div>
                    <ul class="list">
                        @foreach ($invoice->payments as $pay)
                            <li>
                                <div class="grow"><span class="title">{{ money($pay->amount) }}</span><small>{{ $pay->date->format('d M Y') }} · {{ $pay->payment_method ?: $pay->category_label }}</small></div>
                                <form method="POST" action="{{ route('invoices.unlink', [$invoice, $pay]) }}" onsubmit="return confirm('Unlink this payment from the bill? It stays as project income.')">
                                    @csrf @method('DELETE') <button type="submit" class="btn btn-sm">Unlink</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($unlinked->isNotEmpty())
                <div class="card">
                    <div class="card-head"><h2>Earlier project payments</h2></div>
                    <div class="card-body">
                        <p class="muted" style="margin-top:0">Advances recorded on {{ $invoice->project->name }} that aren’t on any bill yet. Link them so this bill shows them as paid.</p>
                        <ul class="list" style="margin:0 -16px -16px">
                            @foreach ($unlinked as $t)
                                <li>
                                    <div class="grow"><span class="title">{{ money($t->amount) }}</span><small>{{ $t->date->format('d M Y') }} · {{ $t->description ?: $t->category_label }}</small></div>
                                    <form method="POST" action="{{ route('invoices.link', $invoice) }}">
                                        @csrf <input type="hidden" name="transaction_id" value="{{ $t->id }}">
                                        <button type="submit" class="btn btn-sm btn-in">Link</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if (! ($profile['business_name'] ?? null) && ! ($profile['upi_id'] ?? null))
                <div class="alert alert-err">Add your name, phone and UPI ID in <a href="{{ route('settings') }}">Settings</a> so they appear on bills.</div>
            @endif
        </div>
    </div>
</x-layout>
