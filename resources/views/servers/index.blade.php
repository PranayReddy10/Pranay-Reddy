<x-layout title="Hosting / servers">
    <x-slot:actions>
        <a class="btn btn-primary" href="{{ route('servers.create') }}"><x-icon name="plus"/> Add server</a>
    </x-slot:actions>

    <div class="grid grid-3">
        <x-stat label="Hosting cost / month" :value="money($monthlyTotal)" tone="out" hint="Active servers, normalised"/>
        <x-stat label="Hosting cost / year" :value="money($yearlyTotal)" tone="out"/>
        <x-stat label="Active servers" :value="$servers->where('is_active', true)->count()" :hint="$servers->sum('domains_count').' domains hosted'"/>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Server</th>
                        <th class="hide-sm">Account</th>
                        <th class="right">Cost</th>
                        <th>Next due</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($servers as $s)
                        <tr @if (! $s->is_active) style="opacity:.55" @endif>
                            <td>
                                <a href="{{ route('servers.show', $s) }}"><strong>{{ $s->name }}</strong></a>
                                @unless ($s->is_active)<span class="badge">inactive</span>@endunless
                                <span class="sub">{{ collect([$s->plan, $s->ip_address, $s->domains_count.' '.\Illuminate\Support\Str::plural('domain', $s->domains_count)])->filter()->join(' · ') }}</span>
                            </td>
                            <td class="hide-sm">@if ($s->account)<a href="{{ route('accounts.show', $s->account) }}">{{ $s->account->display_name }}</a>@else<span class="muted">—</span>@endif</td>
                            <td class="right num">{{ money($s->cost) }}<span class="sub">{{ \App\Support\Ledger::cycleLabel($s->billing_cycle) }}</span></td>
                            <td>@if ($s->is_active)<x-due :date="$s->next_due_date"/>@else<span class="muted">—</span>@endif</td>
                            <td class="right">
                                @if ($s->is_active)
                                    <x-quick-action :action="route('servers.pay', $s)" label="Paid" tone="out"
                                        :confirm="'Book '.money($s->cost).' for '.$s->name.' and move the due date forward one cycle?'"/>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No servers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout>
