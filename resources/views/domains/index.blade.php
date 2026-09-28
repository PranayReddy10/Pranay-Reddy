<x-layout title="Domains">
    <x-slot:actions>
        <a class="btn btn-primary" href="{{ route('domains.create') }}"><x-icon name="plus"/> Add domain</a>
    </x-slot:actions>

    <div class="card">
        <form method="GET" class="filters">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search domains">
            <select name="account" onchange="this.form.submit()">
                <option value="">All registrar accounts</option>
                @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected(request('account') == $a->id)>{{ $a->display_name }}</option>@endforeach
            </select>
            <select name="server" onchange="this.form.submit()">
                <option value="">All servers</option>
                @foreach ($servers as $s)<option value="{{ $s->id }}" @selected(request('server') == $s->id)>{{ $s->name }}</option>@endforeach
            </select>
            <select name="status" onchange="this.form.submit()">
                @foreach (config('ledger.domain_statuses') as $k => $v)<option value="{{ $k }}" @selected(request('status', 'active') === $k)>{{ $v }}</option>@endforeach
            </select>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th class="hide-sm">Registrar account</th>
                        <th class="hide-sm">Hosted on</th>
                        <th>Expires</th>
                        <th class="right">Renewal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($domains as $d)
                        <tr>
                            <td>
                                <a href="{{ route('domains.show', $d) }}"><strong>{{ $d->name }}</strong></a>
                                @if ($d->auto_renew)<span class="badge">auto</span>@endif
                                <span class="sub">{{ $d->project?->name ?? 'No project' }}</span>
                            </td>
                            <td class="hide-sm">@if ($d->account)<a href="{{ route('accounts.show', $d->account) }}">{{ $d->account->display_name }}</a>@else<span class="muted">—</span>@endif</td>
                            <td class="hide-sm">@if ($d->server)<a href="{{ route('servers.show', $d->server) }}">{{ $d->server->name }}</a>@else<span class="muted">—</span>@endif</td>
                            <td><x-due :date="$d->expires_on"/></td>
                            <td class="right num">{{ money($d->renewal_cost) }}@if ($d->paid_by === 'client')<span class="sub">client pays</span>@endif</td>
                            <td class="right">
                                @if ($d->status === 'active')
                                    <x-quick-action :action="route('domains.renew', $d)" label="Renew 1y" tone="out"
                                        :confirm="'Mark '.$d->name.' renewed for 1 year'.($d->paid_by === 'me' ? ' and book '.money($d->renewal_cost).' as expense' : '').'?'"/>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">No domains match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($domains->hasPages())<div class="pagination">{{ $domains->links('partials.pagination') }}</div>@endif
    </div>
</x-layout>
