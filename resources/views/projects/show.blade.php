<x-layout :title="$project->name" :crumb="'<a href=\''.route('projects.index').'\'>Projects</a>'">
    <x-slot:actions>
        @if ($project->isRecurring())
            <x-quick-action :action="route('projects.collect', $project)" :label="'Received '.money($project->billing_amount)" tone="in"
                :confirm="'Record '.money($project->billing_amount).' from the client and move the next bill date forward?'"/>
        @endif
        <a class="btn" href="{{ route('transactions.create', ['type' => 'income', 'project' => $project->id, 'category' => $project->type === 'client' ? 'build_fee' : 'ad_revenue']) }}"><x-icon name="plus"/> Income</a>
        <a class="btn" href="{{ route('transactions.create', ['type' => 'expense', 'project' => $project->id]) }}"><x-icon name="plus"/> Expense</a>
        <a class="btn" href="{{ route('projects.edit', $project) }}"><x-icon name="edit"/> Edit</a>
    </x-slot:actions>

    <div class="grid grid-4">
        <x-stat label="Total received" :value="money($income)" tone="in" :hint="$adRevenue > 0 ? 'incl. ads '.money($adRevenue) : null"/>
        <x-stat label="Partner share" :value="money($share)" :hint="$project->partner ? $project->partner->name.' · '.pct($project->partner_share_percent) : 'Not shared'"/>
        <x-stat label="Spent on project" :value="money($spent)" tone="out"/>
        <x-stat label="My net" :value="money($net)" :tone="$net >= 0 ? 'in' : 'out'"/>
    </div>

    <div class="grid grid-main">
        <div class="card">
            <div class="card-head"><h2>Transactions</h2></div>
            <x-txn-table :transactions="$transactions" :show-project="false"/>
        </div>

        <div>
            <div class="card">
                <div class="card-head"><h2>Details</h2></div>
                <div class="card-body">
                    <dl class="kv">
                        <dt>Status</dt><dd>{{ config('ledger.project_statuses')[$project->status] }}</dd>
                        <dt>Type</dt><dd>{{ config('ledger.project_types')[$project->type] }}</dd>
                        <dt>Client</dt><dd>@if ($project->client)<a href="{{ route('clients.show', $project->client) }}">{{ $project->client->name }}</a>@else — @endif</dd>
                        <dt>Website</dt><dd>@if ($project->url)<a href="{{ \Illuminate\Support\Str::start($project->url, 'https://') }}" target="_blank" rel="noopener">{{ $project->url }}</a>@else — @endif</dd>
                        <dt>Build fee</dt><dd class="num">{{ money($project->build_fee) }}</dd>
                        <dt>Billing</dt><dd>@if ($project->isRecurring()){{ money($project->billing_amount) }} {{ strtolower(\App\Support\Ledger::cycleLabel($project->billing_cycle)) }}@else None @endif</dd>
                        @if ($project->isRecurring())<dt>Next bill</dt><dd><x-due :date="$project->next_billing_date"/></dd>@endif
                        <dt>Started</dt><dd>{{ $project->started_on?->format('d M Y') ?? '—' }}</dd>
                    </dl>
                    @if ($project->notes)<p style="white-space:pre-line;margin-bottom:0">{{ $project->notes }}</p>@endif
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Domains</h2><a class="btn btn-sm" href="{{ route('domains.create', ['project' => $project->id]) }}"><x-icon name="plus"/> Add</a></div>
                <ul class="list">
                    @forelse ($project->domains as $d)
                        <li>
                            <div class="grow">
                                <a class="title" href="{{ route('domains.show', $d) }}">{{ $d->name }}</a>
                                <small>{{ $d->account?->display_name ?? 'No account' }} · {{ $d->server?->name ?? 'No server' }}</small>
                            </div>
                            <div class="right"><x-due :date="$d->expires_on"/></div>
                        </li>
                    @empty
                        <li class="muted">No domains linked.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layout>
