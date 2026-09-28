<x-layout :title="$domain->name" :crumb="'<a href=\''.route('domains.index').'\'>Domains</a>'">
    <x-slot:actions>
        <a class="btn" href="https://{{ $domain->name }}" target="_blank" rel="noopener"><x-icon name="external"/> Visit</a>
        <a class="btn" href="{{ route('domains.edit', $domain) }}"><x-icon name="edit"/> Edit</a>
    </x-slot:actions>

    <div class="grid grid-main">
        <div>
            <div class="card">
                <div class="card-head"><h2>Details</h2></div>
                <div class="card-body">
                    <dl class="kv">
                        <dt>Status</dt><dd>{{ config('ledger.domain_statuses')[$domain->status] }}</dd>
                        <dt>Expires</dt><dd><x-due :date="$domain->expires_on"/></dd>
                        <dt>Registered</dt><dd>{{ $domain->registered_on?->format('d M Y') ?? '—' }}</dd>
                        <dt>Registrar account</dt><dd>@if ($domain->account)<a href="{{ route('accounts.show', $domain->account) }}">{{ $domain->account->display_name }}</a>@if ($domain->account->login_email)<br><small>{{ $domain->account->login_email }}</small>@endif @else — @endif</dd>
                        <dt>Hosted on</dt><dd>@if ($domain->server)<a href="{{ route('servers.show', $domain->server) }}">{{ $domain->server->name }}</a>@if ($domain->server->ip_address)<br><small>{{ $domain->server->ip_address }}</small>@endif @else — @endif</dd>
                        <dt>Project</dt><dd>@if ($domain->project)<a href="{{ route('projects.show', $domain->project) }}">{{ $domain->project->name }}</a>@if ($domain->project->client) · {{ $domain->project->client->name }}@endif @else — @endif</dd>
                        <dt>Renewal cost</dt><dd class="num">{{ money($domain->renewal_cost) }} / year · {{ $domain->paid_by === 'me' ? 'I pay' : 'Client pays' }}</dd>
                        <dt>Auto-renew</dt><dd>{{ $domain->auto_renew ? 'On' : 'Off' }}</dd>
                    </dl>
                    @if ($domain->notes)<p style="white-space:pre-line;margin-bottom:0">{{ $domain->notes }}</p>@endif
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Payment history</h2><span class="num out">{{ money($domain->transactions->where('type', 'expense')->sum('amount')) }}</span></div>
                <x-txn-table :transactions="$domain->transactions" :show-project="false"/>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Record renewal</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('domains.renew', $domain) }}" class="stack">
                    @csrf
                    <x-select name="years" label="Renewed for" :options="[1 => '1 year', 2 => '2 years', 3 => '3 years', 5 => '5 years']" value="1"/>
                    <x-input name="amount" label="Amount paid" type="number" step="0.01" min="0" :value="$domain->renewal_cost"
                        :help="$domain->paid_by === 'client' ? 'Client pays — no expense is booked, only the expiry moves.' : 'Booked as a domain expense on '.($domain->account?->display_name ?? 'no account').'.'"/>
                    <x-input name="date" label="Paid on" type="date" :value="now()->toDateString()"/>
                    <x-select name="payment_method" label="Paid with" :options="array_combine(config('ledger.payment_methods'), config('ledger.payment_methods'))" placeholder="—"/>
                    <button type="submit" class="btn btn-primary">Mark renewed</button>
                </form>
            </div>
        </div>
    </div>
</x-layout>
