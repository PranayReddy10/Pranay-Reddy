<x-layout title="Clients">
    <x-slot:actions>
        <a class="btn btn-primary" href="{{ route('clients.create') }}"><x-icon name="plus"/> Add client</a>
    </x-slot:actions>
    <div class="card">
        <form method="GET" class="filters"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search clients"></form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Client</th><th class="hide-sm">Contact</th><th class="right">Projects</th><th class="right">Total paid</th></tr></thead>
                <tbody>
                    @forelse ($clients as $c)
                        <tr>
                            <td><a href="{{ route('clients.show', $c) }}"><strong>{{ $c->name }}</strong></a><span class="sub">{{ $c->company }}</span></td>
                            <td class="hide-sm">{{ $c->phone }}<span class="sub">{{ $c->email }}</span></td>
                            <td class="right num">{{ $c->projects_count }}</td>
                            <td class="right num in">{{ money($c->paid_total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No clients yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($clients->hasPages())<div class="pagination">{{ $clients->links('partials.pagination') }}</div>@endif
    </div>
</x-layout>
