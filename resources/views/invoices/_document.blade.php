{{-- The bill itself. Used in-app and on the public share page; prints cleanly to A4 / PDF. --}}
@php
    $p = fn ($k) => $profile[$k] ?? null;
    $from = $p('business_name') ?: ($p('owner_name') ?: config('app.name'));
    $balance = $invoice->balance();
    $upi = \App\Support\Ledger::upiLink($p('upi_id'), $from, $balance, $invoice->number);
@endphp
<article class="bill">
    <header class="bill-head">
        <div>
            <div class="bill-from">{{ $from }}</div>
            @if ($p('business_name') && $p('owner_name'))<div>{{ $p('owner_name') }}</div>@endif
            @if ($p('address'))<div class="pre">{{ $p('address') }}</div>@endif
            <div>{{ collect([$p('phone'), $p('email'), $p('website')])->filter()->join(' · ') }}</div>
            @if ($p('gstin'))<div>GSTIN: {{ $p('gstin') }}</div>@endif
        </div>
        <div class="bill-meta">
            <div class="bill-word">{{ $invoice->tax_percent > 0 ? 'TAX INVOICE' : 'BILL' }}</div>
            <div class="bill-no">{{ $invoice->number }}</div>
            <span class="badge {{ $invoice->stateBadge() }}">{{ $invoice->stateLabel() }}</span>
        </div>
    </header>

    <section class="bill-parties">
        <div>
            <div class="bill-label">Bill to</div>
            @if ($invoice->client)
                <strong>{{ $invoice->client->company ?: $invoice->client->name }}</strong>
                @if ($invoice->client->company)<div>{{ $invoice->client->name }}</div>@endif
                <div>{{ collect([$invoice->client->phone, $invoice->client->email])->filter()->join(' · ') }}</div>
            @else
                <span class="muted">—</span>
            @endif
        </div>
        <div>
            @if ($invoice->title || $invoice->project)
                <div class="bill-label">For</div>
                <strong>{{ $invoice->title ?: $invoice->project?->name }}</strong>
                @if ($invoice->project?->url)<div>{{ $invoice->project->url }}</div>@endif
            @endif
        </div>
        <div class="bill-dates">
            <div><span class="bill-label">Issued</span> {{ $invoice->issue_date->format('d M Y') }}</div>
            @if ($invoice->due_date)<div><span class="bill-label">Due</span> {{ $invoice->due_date->format('d M Y') }}</div>@endif
        </div>
    </section>

    <table class="bill-items">
        <thead><tr><th>#</th><th>Description</th><th class="right">Qty</th><th class="right">Rate</th><th class="right">Amount</th></tr></thead>
        <tbody>
            @foreach ($invoice->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="right num">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="right num">{{ money($item->rate) }}</td>
                    <td class="right num">{{ money($item->amount()) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="bill-bottom">
        <div class="bill-pay">
            @if ($balance > 0 && $upi)
                <div class="bill-label">Scan to pay via UPI</div>
                <div class="bill-qr">{!! \Illuminate\Support\Str::after(\App\Support\Ledger::qrSvg($upi, 140), '?>') !!}</div>
                <div>{{ $p('upi_id') }}</div>
                <a class="btn btn-primary no-print upi-btn" href="{{ $upi }}">Pay {{ money($balance) }} via UPI</a>
            @endif
            @if ($p('bank_details'))
                <div class="bill-label" style="margin-top:10px">Bank transfer</div>
                <div class="pre">{{ $p('bank_details') }}</div>
            @endif
        </div>
        <table class="bill-totals">
            <tr><td>Subtotal</td><td class="right num">{{ money($invoice->subtotal()) }}</td></tr>
            @if ($invoice->discount > 0)<tr><td>Discount</td><td class="right num">−{{ money($invoice->discount) }}</td></tr>@endif
            @if ($invoice->tax_percent > 0)<tr><td>{{ $invoice->tax_label ?: 'Tax' }} ({{ pct($invoice->tax_percent) }})</td><td class="right num">{{ money($invoice->taxAmount()) }}</td></tr>@endif
            <tr class="grand"><td>Total</td><td class="right num">{{ money($invoice->total()) }}</td></tr>
            @foreach ($invoice->payments as $pay)
                <tr class="paid"><td>Paid {{ $pay->date->format('d M Y') }}@if ($pay->payment_method) · {{ $pay->payment_method }}@endif</td><td class="right num">−{{ money($pay->amount) }}</td></tr>
            @endforeach
            <tr class="due"><td>{{ $invoice->status === 'cancelled' ? 'Cancelled' : 'Balance due' }}</td><td class="right num">{{ money($balance) }}</td></tr>
        </table>
    </div>

    @if ($invoice->notes)<footer class="bill-notes pre">{{ $invoice->notes }}</footer>@endif
</article>
