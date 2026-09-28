<x-layout title="Projects">
    <x-slot:actions>
        <a class="btn btn-primary" href="{{ route('projects.create') }}"><x-icon name="plus"/> New project</a>
    </x-slot:actions>

    <div class="card">
        <form method="GET" class="filters">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search projects">
            <select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (config('ledger.project_statuses') as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
            </select>
            <select name="type" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach (config('ledger.project_types') as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach
            </select>
            <label class="check"><input type="checkbox" name="shared" value="1" @checked(request()->boolean('shared')) onchange="this.form.submit()"> Shared with partner</label>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Project</th>
                        <th class="hide-sm">Billing</th>
                        <th class="hide-sm">Next bill</th>
                        <th class="right">Income</th>
                        <th class="right hide-sm">Spent</th>
                        <th class="right">My net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $p)
                        @php $net = $p->income_total - $p->share_total - $p->spent_total; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('projects.show', $p) }}"><strong>{{ $p->name }}</strong></a>
                                @if ($p->status !== 'active')<span class="badge">{{ config('ledger.project_statuses')[$p->status] }}</span>@endif
                                @if ($p->isShared())<span class="badge badge-brand">{{ pct($p->partner_share_percent) }} {{ $p->partner->name }}</span>@endif
                                <span class="sub">{{ $p->client?->name ?? config('ledger.project_types')[$p->type] }} · {{ $p->domains_count }} {{ \Illuminate\Support\Str::plural('domain', $p->domains_count) }}</span>
                            </td>
                            <td class="hide-sm">
                                @if ($p->isRecurring()){{ money($p->billing_amount) }} <span class="sub">{{ \App\Support\Ledger::cycleLabel($p->billing_cycle) }}</span>@else<span class="muted">—</span>@endif
                            </td>
                            <td class="hide-sm">@if ($p->isRecurring())<x-due :date="$p->next_billing_date"/>@else<span class="muted">—</span>@endif</td>
                            <td class="right num in">{{ money($p->income_total) }}</td>
                            <td class="right num out hide-sm">{{ money($p->spent_total) }}</td>
                            <td class="right num {{ $net >= 0 ? 'in' : 'out' }}"><strong>{{ money($net) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">No projects yet. <a href="{{ route('projects.create') }}">Create your first one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($projects->hasPages())<div class="pagination">{{ $projects->links('partials.pagination') }}</div>@endif
    </div>
</x-layout>
