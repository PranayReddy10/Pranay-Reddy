<x-layout title="Bills" crumb="Invoices you send to clients">
    <x-slot:actions>
        <a class="btn btn-primary" href="{{ route('invoices.create') }}"><x-icon name="plus"/> New bill</a>
    </x-slot:actions>

    <div class="grid grid-4">
        <x-stat label="Billed" :value="money($totals['billed'])"/>
        <x-stat label="Received" :value="money($totals['paid'])" tone="in"/>
        <x-stat label="Balance to collect" :value="money($totals['balance'])" :tone="$totals['balance'] > 0 ? 'out' : null"/>
        <x-stat label="Overdue" :value="money($totals['overdue'])" :tone="$totals['overdue'] > 0 ? 'out' : null"/>
    </div>

    <div class="card">
        <form method="GET" class="filters">
            <select name="state" onchange="this.form.submit()">
                <option value="">All bills</option>
                @foreach (['due' => 'Unpaid & part-paid', 'overdue' => 'Overdue', 'paid' => 'Paid', 'draft' => 'Drafts', 'cancelled' => 'Cancelled'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('state') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Bill</th><th class="hide-sm">Client / project</th><th class="hide-sm">Due</th><th class="right">Total</th><th class="right">Balance</th></tr></thead>
                <tbody>
                    @forelse ($invoices as $inv)
                        <tr>
                            <td>
                                <a href="{{ route('invoices.show', $inv) }}"><strong>{{ $inv->number }}</strong></a>
                                <span class="badge {{ $inv->stateBadge() }}">{{ $inv->stateLabel() }}</span>
                                <span class="sub">{{ $inv->issue_date->format('d M Y') }}</span>
                            </td>
                            <td class="hide-sm">{{ $inv->billTo() ?? '—' }}<span class="sub">{{ $inv->title ?: $inv->project?->name }}</span></td>
                            <td class="hide-sm">{{ $inv->due_date?->format('d M Y') ?? '—' }}</td>
                            <td class="right num">{{ money($inv->total()) }}</td>
                            <td class="right num {{ $inv->balance() > 0 ? 'out' : 'in' }}"><strong>{{ money($inv->balance()) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No bills yet. Open a project and choose <strong>Create bill</strong>, or start a <a href="{{ route('invoices.create') }}">new bill</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout>
