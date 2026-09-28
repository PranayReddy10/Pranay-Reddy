<x-layout :title="$client->name" :crumb="'<a href=\''.route('clients.index').'\'>Clients</a>'">
    <x-slot:actions>
        <a class="btn" href="{{ route('projects.create', ['client' => $client->id]) }}"><x-icon name="plus"/> Project</a>
        <a class="btn" href="{{ route('clients.edit', $client) }}"><x-icon name="edit"/> Edit</a>
    </x-slot:actions>

    <div class="grid grid-3">
        <x-stat label="Total paid" :value="money($totalPaid)" tone="in"/>
        <x-stat label="Projects" :value="$client->projects->count()"/>
        <x-stat label="Recurring / year" :value="money($client->projects->where('status', 'active')->sum(fn ($p) => $p->yearlyBilling()))" tone="in"/>
    </div>

    <div class="grid grid-main">
        <div class="card">
            <div class="card-head"><h2>Payments</h2></div>
            <x-txn-table :transactions="$client->transactions"/>
        </div>
        <div>
            <div class="card">
                <div class="card-head"><h2>Contact</h2></div>
                <div class="card-body">
                    <dl class="kv">
                        <dt>Company</dt><dd>{{ $client->company ?: '—' }}</dd>
                        <dt>Email</dt><dd>@if ($client->email)<a href="mailto:{{ $client->email }}">{{ $client->email }}</a>@else — @endif</dd>
                        <dt>Phone</dt><dd>@if ($client->phone)<a href="tel:{{ $client->phone }}">{{ $client->phone }}</a>@else — @endif</dd>
                    </dl>
                    @if ($client->notes)<p style="white-space:pre-line;margin-bottom:0">{{ $client->notes }}</p>@endif
                </div>
            </div>
            <div class="card">
                <div class="card-head"><h2>Projects</h2></div>
                <ul class="list">
                    @forelse ($client->projects as $p)
                        <li>
                            <div class="grow"><a class="title" href="{{ route('projects.show', $p) }}">{{ $p->name }}</a><small>{{ $p->domains->pluck('name')->join(', ') ?: 'No domains' }}</small></div>
                            @if ($p->isRecurring())<span class="num">{{ money($p->billing_amount) }}<small>/{{ \App\Support\Ledger::cycleMonths($p->billing_cycle) === 12 ? 'yr' : \App\Support\Ledger::cycleMonths($p->billing_cycle).'mo' }}</small></span>@endif
                        </li>
                    @empty
                        <li class="muted">No projects.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layout>
