<x-layout title="Accounts" crumb="Registrar, hosting and ad-network logins">
    <x-slot:actions>
        <a class="btn btn-primary" href="{{ route('accounts.create') }}"><x-icon name="plus"/> Add account</a>
    </x-slot:actions>

    <div class="card">
        <form method="GET" class="filters">
            <select name="type" onchange="this.form.submit()">
                <option value="">All account types</option>
                @foreach (config('ledger.account_types') as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach
            </select>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Account</th><th class="hide-sm">Type</th><th class="right">Domains</th><th class="right">Servers</th><th class="right">Total spent</th></tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $a)
                        <tr>
                            <td><a href="{{ route('accounts.show', $a) }}"><strong>{{ $a->provider }}</strong></a> · {{ $a->label }}<span class="sub">{{ $a->login_email ?: '—' }}</span></td>
                            <td class="hide-sm"><span class="badge">{{ config('ledger.account_types')[$a->type] ?? $a->type }}</span></td>
                            <td class="right num">{{ $a->domains_count }}</td>
                            <td class="right num">{{ $a->servers_count }}</td>
                            <td class="right num out">{{ money($a->spent_total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No accounts yet. Add each registrar / hosting login you use (e.g. GoDaddy personal, Hostinger client-X).</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="muted" style="margin-top:12px">Passwords are intentionally not stored here — keep them in a password manager.</p>
</x-layout>
