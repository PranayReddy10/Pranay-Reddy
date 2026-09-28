<x-layout title="Partners" crumb="Friends who share ad revenue on some projects">
    <x-slot:actions>
        <a class="btn btn-primary" href="{{ route('partners.create') }}"><x-icon name="plus"/> Add partner</a>
    </x-slot:actions>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Partner</th><th class="right">Projects</th><th class="right hide-sm">Share earned</th><th class="right hide-sm">Paid out</th><th class="right">Balance owed</th></tr></thead>
                <tbody>
                    @forelse ($partners as $row)
                        <tr>
                            <td><a href="{{ route('partners.show', $row['partner']) }}"><strong>{{ $row['partner']->name }}</strong></a><span class="sub">{{ $row['partner']->phone }}</span></td>
                            <td class="right num">{{ $row['partner']->projects_count }}</td>
                            <td class="right num hide-sm">{{ money($row['earned']) }}</td>
                            <td class="right num hide-sm">{{ money($row['paid']) }}</td>
                            <td class="right num {{ $row['balance'] > 0 ? 'out' : 'in' }}"><strong>{{ money($row['balance']) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No partners. Most projects are yours alone — add a partner only when a friend shares ad money.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout>
