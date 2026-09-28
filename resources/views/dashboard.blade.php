<x-layout title="Dashboard" :crumb="now()->format('l, d M Y')">
    <x-slot:actions>
        <a class="btn btn-in" href="{{ route('transactions.create', ['type' => 'income']) }}"><x-icon name="plus"/> Income</a>
        <a class="btn btn-out" href="{{ route('transactions.create', ['type' => 'expense']) }}"><x-icon name="plus"/> Expense</a>
    </x-slot:actions>

    <div class="grid grid-4">
        <x-stat label="Net this month" :value="money($month['net'])" :tone="$month['net'] >= 0 ? 'in' : 'out'"
                :hint="'In '.money($month['my_income']).' · Out '.money($month['spending'])"/>
        <x-stat label="Net this year" :value="money($year['net'])" :tone="$year['net'] >= 0 ? 'in' : 'out'"
                :hint="'In '.money($year['my_income']).' · Out '.money($year['spending'])"/>
        <x-stat label="Ad revenue this year" :value="money($year['ad_revenue'])"
                :hint="$year['partner_share'] > 0 ? 'Partner share '.money($year['partner_share']) : 'All yours'"/>
        <x-stat label="Recurring run-rate / yr" :value="money($recurring['net'])" :tone="$recurring['net'] >= 0 ? 'in' : 'out'"
                :hint="'Billing '.money($recurring['billing']).' − hosting & domains '.money($recurring['hosting'] + $recurring['domains'])"/>
    </div>

    <div class="grid grid-main">
        <div class="card">
            <div class="card-head">
                <h2>Last 12 months</h2>
                <div class="legend"><span><i style="background:var(--in)"></i>My income</span><span><i style="background:var(--out)"></i>Spending</span></div>
            </div>
            <div class="card-body">
                @php $max = max(1, $series->max('income'), $series->max('spending')); @endphp
                <div class="bars">
                    @foreach ($series as $m)
                        <div class="col" title="{{ $m['label'] }} — in {{ money($m['income']) }}, out {{ money($m['spending']) }}, net {{ money($m['net']) }}">
                            <div class="pair">
                                <div class="bar b-in" style="height: {{ round($m['income'] / $max * 100, 1) }}%"></div>
                                <div class="bar b-out" style="height: {{ round($m['spending'] / $max * 100, 1) }}%"></div>
                            </div>
                            <div class="lbl">{{ \Illuminate\Support\Str::before($m['label'], ' ') }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>At a glance</h2></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Active projects</dt><dd><a href="{{ route('projects.index', ['status' => 'active']) }}">{{ $counts['projects'] }}</a></dd>
                    <dt>Active domains</dt><dd><a href="{{ route('domains.index') }}">{{ $counts['domains'] }}</a></dd>
                    <dt>Active servers</dt><dd><a href="{{ route('servers.index') }}">{{ $counts['servers'] }}</a></dd>
                    <dt>Client billing / yr</dt><dd class="num in">{{ money($recurring['billing']) }}</dd>
                    <dt>Hosting / yr</dt><dd class="num out">{{ money($recurring['hosting']) }}</dd>
                    <dt>Domains / yr</dt><dd class="num out">{{ money($recurring['domains']) }}</dd>
                    <dt>All-time net</dt><dd class="num {{ $allTime['net'] >= 0 ? 'in' : 'out' }}">{{ money($allTime['net']) }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="grid grid-main">
        <div class="card">
            <div class="card-head">
                <h2>Due in the next {{ config('ledger.due_soon_days') }} days</h2>
                <a href="{{ route('renewals') }}" class="btn btn-sm">All dues</a>
            </div>
            @include('renewals._list', ['items' => $upcoming])
        </div>

        <div>
            <div class="card">
                <div class="card-head"><h2>Partner balances</h2><a href="{{ route('partners.index') }}" class="btn btn-sm">Partners</a></div>
                <ul class="list">
                    @forelse ($partners as $row)
                        <li>
                            <div class="grow"><a class="title" href="{{ route('partners.show', $row['partner']) }}">{{ $row['partner']->name }}</a><small>{{ $row['balance'] > 0 ? 'I owe' : 'Overpaid' }}</small></div>
                            <strong class="num {{ $row['balance'] > 0 ? 'out' : 'in' }}">{{ money(abs($row['balance'])) }}</strong>
                        </li>
                    @empty
                        <li class="muted">All partner shares are settled.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-head"><h2>Recent activity</h2><a href="{{ route('transactions.index') }}" class="btn btn-sm">All</a></div>
                <ul class="list">
                    @forelse ($recent as $t)
                        <li>
                            <div class="grow">
                                <span class="title">{{ $t->description ?: $t->category_label }}</span>
                                <small>{{ $t->date->format('d M') }} · {{ $t->project?->name ?? $t->partner?->name ?? $t->category_label }}</small>
                            </div>
                            <span class="num {{ $t->type === 'income' ? 'in' : 'out' }}">{{ $t->type === 'income' ? '+' : '−' }}{{ money($t->amount) }}</span>
                        </li>
                    @empty
                        <li class="muted">Nothing recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layout>
