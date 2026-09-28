@props(['transactions', 'showProject' => true])
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Details</th>
                @if ($showProject)<th class="hide-sm">Project</th>@endif
                <th class="right">Amount</th>
                <th class="hide-sm"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $t)
                <tr>
                    <td class="nowrap">{{ $t->date->format('d M Y') }}</td>
                    <td>
                        {{ $t->description ?: $t->category_label }}
                        <span class="sub">{{ $t->category_label }}@if ($t->payment_method) · {{ $t->payment_method }}@endif</span>
                    </td>
                    @if ($showProject)
                        <td class="hide-sm">@if ($t->project)<a href="{{ route('projects.show', $t->project) }}">{{ $t->project->name }}</a>@else<span class="muted">—</span>@endif</td>
                    @endif
                    <td class="right num">
                        <span class="{{ $t->type === 'income' ? 'in' : 'out' }}">{{ $t->type === 'income' ? '+' : '−' }}{{ money($t->amount) }}</span>
                        @if ($t->partner_share > 0)<span class="sub">partner {{ money($t->partner_share) }}</span>@endif
                    </td>
                    <td class="right hide-sm"><a class="btn btn-sm" href="{{ route('transactions.edit', $t) }}">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No transactions yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
