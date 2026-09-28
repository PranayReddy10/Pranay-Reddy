<x-layout :title="$server->name" :crumb="'<a href=\''.route('servers.index').'\'>Hosting / servers</a>'">
    <x-slot:actions>
        <a class="btn" href="{{ route('servers.edit', $server) }}"><x-icon name="edit"/> Edit</a>
    </x-slot:actions>

    <div class="grid grid-3">
        <x-stat label="Cost" :value="money($server->cost)" :hint="\App\Support\Ledger::cycleLabel($server->billing_cycle).' · '.money($server->yearlyCost()).' / year'"/>
        <x-stat label="Paid so far" :value="money($spent)" tone="out"/>
        <x-stat label="Domains hosted" :value="$server->domains->count()"/>
    </div>

    <div class="grid grid-main">
        <div>
            <div class="card">
                <div class="card-head"><h2>Domains on this server</h2></div>
                <ul class="list">
                    @forelse ($server->domains as $d)
                        <li>
                            <div class="grow"><a class="title" href="{{ route('domains.show', $d) }}">{{ $d->name }}</a><small>{{ $d->project?->name ?? 'No project' }}</small></div>
                            <x-due :date="$d->expires_on"/>
                        </li>
                    @empty
                        <li class="muted">No domains point here yet.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card">
                <div class="card-head"><h2>Payment history</h2></div>
                <x-txn-table :transactions="$server->transactions" :show-project="false"/>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-head"><h2>Details</h2></div>
                <div class="card-body">
                    <dl class="kv">
                        <dt>Status</dt><dd>{{ $server->is_active ? 'Active' : 'Inactive' }}</dd>
                        <dt>Next due</dt><dd><x-due :date="$server->next_due_date"/></dd>
                        <dt>Account</dt><dd>@if ($server->account)<a href="{{ route('accounts.show', $server->account) }}">{{ $server->account->display_name }}</a>@else — @endif</dd>
                        <dt>Plan</dt><dd>{{ $server->plan ?: '—' }}</dd>
                        <dt>IP</dt><dd>{{ $server->ip_address ?: '—' }}</dd>
                        <dt>Location</dt><dd>{{ $server->location ?: '—' }}</dd>
                        <dt>Auto-renew</dt><dd>{{ $server->auto_renew ? 'On' : 'Off' }}</dd>
                    </dl>
                    @if ($server->notes)<p style="white-space:pre-line;margin-bottom:0">{{ $server->notes }}</p>@endif
                </div>
            </div>
            @if ($server->is_active)
                <div class="card">
                    <div class="card-head"><h2>Record payment</h2></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('servers.pay', $server) }}" class="stack">
                            @csrf
                            <x-input name="amount" label="Amount paid" type="number" step="0.01" min="0" :value="$server->cost"/>
                            <x-input name="date" label="Paid on" type="date" :value="now()->toDateString()"/>
                            <x-select name="payment_method" label="Paid with" :options="array_combine(config('ledger.payment_methods'), config('ledger.payment_methods'))" placeholder="—"/>
                            <button type="submit" class="btn btn-primary">Mark paid &amp; roll due date</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layout>
