<x-layout :title="$partner->name" :crumb="'<a href=\''.route('partners.index').'\'>Partners</a>'">
    <x-slot:actions>
        <a class="btn" href="{{ route('partners.edit', $partner) }}"><x-icon name="edit"/> Edit</a>
    </x-slot:actions>

    <div class="grid grid-3">
        <x-stat label="Share earned (all time)" :value="money($earned)"/>
        <x-stat label="Paid out" :value="money($paid)"/>
        <x-stat label="I still owe" :value="money($balance)" :tone="$balance > 0 ? 'out' : 'in'"/>
    </div>

    <div class="grid grid-main">
        <div class="card">
            <div class="card-head"><h2>Share ledger</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Details</th><th class="right">Their share</th><th class="right">Paid</th></tr></thead>
                    <tbody>
                        @forelse ($ledger as $t)
                            <tr>
                                <td class="nowrap">{{ $t->date->format('d M Y') }}</td>
                                <td>{{ $t->description ?: $t->category_label }}<span class="sub">{{ $t->project?->name }}@if ($t->type === 'income') · ads {{ money($t->amount) }}@endif</span></td>
                                <td class="right num">@if ($t->type === 'income'){{ money($t->partner_share) }}@endif</td>
                                <td class="right num">@if ($t->type === 'expense'){{ money($t->amount) }}@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">No shared revenue yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div>
            <div class="card">
                <div class="card-head"><h2>Record payout</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('partners.payout', $partner) }}" class="stack">
                        @csrf
                        <x-input name="amount" label="Amount sent" type="number" step="0.01" min="0.01" :value="$balance > 0 ? $balance : null" required/>
                        <x-input name="date" label="Date" type="date" :value="now()->toDateString()" required/>
                        <x-select name="payment_method" label="Method" :options="array_combine(config('ledger.payment_methods'), config('ledger.payment_methods'))" placeholder="—"/>
                        <x-input name="description" label="Note"/>
                        <button type="submit" class="btn btn-primary">Record payout</button>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-head"><h2>Shared projects</h2></div>
                <ul class="list">
                    @forelse ($partner->projects as $p)
                        <li><div class="grow"><a class="title" href="{{ route('projects.show', $p) }}">{{ $p->name }}</a></div><span class="badge badge-brand">{{ pct($p->partner_share_percent) }}</span></li>
                    @empty
                        <li class="muted">Assign this partner on a project's edit page.</li>
                    @endforelse
                </ul>
            </div>
            @if ($partner->upi_or_bank || $partner->email)
                <div class="card"><div class="card-body"><dl class="kv">
                    <dt>UPI / bank</dt><dd>{{ $partner->upi_or_bank ?: '—' }}</dd>
                    <dt>Email</dt><dd>{{ $partner->email ?: '—' }}</dd>
                </dl></div></div>
            @endif
        </div>
    </div>
</x-layout>
