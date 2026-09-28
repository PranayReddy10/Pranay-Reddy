<x-layout :title="$account->display_name" :crumb="'<a href=\''.route('accounts.index').'\'>Accounts</a>'">
    <x-slot:actions>
        @if ($account->dashboard_url)<a class="btn" href="{{ $account->dashboard_url }}" target="_blank" rel="noopener"><x-icon name="external"/> Open panel</a>@endif
        <a class="btn" href="{{ route('accounts.edit', $account) }}"><x-icon name="edit"/> Edit</a>
    </x-slot:actions>

    <div class="grid grid-4">
        <x-stat label="Type" :value="config('ledger.account_types')[$account->type] ?? $account->type"/>
        <x-stat label="Login" :value="$account->login_email ?: '—'"/>
        <x-stat label="Total spent" :value="money($spent)" tone="out"/>
        <x-stat label="Income received" :value="money($earned)" tone="in"/>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-head"><h2>Domains in this account ({{ $account->domains->count() }})</h2></div>
            <ul class="list">
                @forelse ($account->domains->sortBy('expires_on') as $d)
                    <li>
                        <div class="grow"><a class="title" href="{{ route('domains.show', $d) }}">{{ $d->name }}</a><small>{{ $d->project?->name ?? 'No project' }} · {{ money($d->renewal_cost) }}/yr</small></div>
                        <div class="right"><x-due :date="$d->expires_on"/></div>
                    </li>
                @empty
                    <li class="muted">None.</li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="card-head"><h2>Servers in this account ({{ $account->servers->count() }})</h2></div>
            <ul class="list">
                @forelse ($account->servers as $s)
                    <li>
                        <div class="grow"><a class="title" href="{{ route('servers.show', $s) }}">{{ $s->name }}</a><small>{{ money($s->cost) }} {{ strtolower(\App\Support\Ledger::cycleLabel($s->billing_cycle)) }}</small></div>
                        <div class="right"><x-due :date="$s->next_due_date"/></div>
                    </li>
                @empty
                    <li class="muted">None.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Recent transactions</h2><a class="btn btn-sm" href="{{ route('transactions.index', ['account' => $account->id]) }}">All</a></div>
        <x-txn-table :transactions="$account->transactions"/>
    </div>

    @if ($account->notes)<div class="card"><div class="card-body" style="white-space:pre-line">{{ $account->notes }}</div></div>@endif
</x-layout>
