@php
    $change = fn ($now, $before) => $before != 0 ? round(($now - $before) / abs($before) * 100) : null;
    $netChange = $change($summary['net'], $previous['net']);
    $spendChange = $change($summary['spending'], $previous['spending']);
    $maxMonth = max(1, $months->max('income'), $months->max('spending'));
    $incomeTotal = max(1, $incomeByCategory->sum());
    $spendTotal = max(1, $spendingByCategory->sum());
@endphp
<x-layout title="Spending analysis" :crumb="'Financial year '.$year">
    <x-slot:actions>
        <form method="GET" class="inline">
            <select name="year" onchange="this.form.submit()" style="width:auto">
                @foreach (range(now()->year + 1, now()->year - 6) as $y)<option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>@endforeach
            </select>
        </form>
        <a class="btn" href="{{ route('transactions.export', ['from' => $year.'-01-01', 'to' => $year.'-12-31']) }}"><x-icon name="download"/> Export {{ $year }}</a>
    </x-slot:actions>

    <div class="grid grid-4">
        <x-stat label="Gross income" :value="money($summary['income'])" tone="in" :hint="'Ads '.money($summary['ad_revenue'])"/>
        <x-stat label="Partner share" :value="money($summary['partner_share'])" :hint="'Kept '.money($summary['my_income'])"/>
        <x-stat label="Spending" :value="money($summary['spending'])" tone="out"
                :hint="$spendChange === null ? 'No data for '.($year - 1) : ($spendChange >= 0 ? '▲ ' : '▼ ').abs($spendChange).'% vs '.($year - 1)"/>
        <x-stat label="Net profit" :value="money($summary['net'])" :tone="$summary['net'] >= 0 ? 'in' : 'out'"
                :hint="$netChange === null ? 'No data for '.($year - 1) : ($netChange >= 0 ? '▲ ' : '▼ ').abs($netChange).'% vs '.($year - 1)"/>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Month by month</h2>
            <div class="legend"><span><i style="background:var(--in)"></i>My income</span><span><i style="background:var(--out)"></i>Spending</span></div>
        </div>
        <div class="card-body">
            <div class="bars">
                @foreach ($months as $m)
                    <div class="col" title="{{ $m['label'] }} — in {{ money($m['income']) }}, out {{ money($m['spending']) }}">
                        <div class="pair">
                            <div class="bar b-in" style="height: {{ round($m['income'] / $maxMonth * 100, 1) }}%"></div>
                            <div class="bar b-out" style="height: {{ round($m['spending'] / $maxMonth * 100, 1) }}%"></div>
                        </div>
                        <div class="lbl">{{ \Illuminate\Support\Str::before($m['label'], ' ') }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Month</th><th class="right">My income</th><th class="right">Spending</th><th class="right">Net</th></tr></thead>
                <tbody>
                    @foreach ($months as $m)
                        <tr>
                            <td><a href="{{ route('transactions.index', ['from' => $m['key'].'-01', 'to' => \Illuminate\Support\Carbon::parse($m['key'].'-01')->endOfMonth()->toDateString()]) }}">{{ $m['label'] }}</a></td>
                            <td class="right num in">{{ money($m['income']) }}</td>
                            <td class="right num out">{{ money($m['spending']) }}</td>
                            <td class="right num {{ $m['net'] >= 0 ? 'in' : 'out' }}">{{ money($m['net']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="right num">{{ money($months->sum('income')) }}</td>
                        <td class="right num">{{ money($months->sum('spending')) }}</td>
                        <td class="right num">{{ money($months->sum('net')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-head"><h2>Where the money went</h2><span class="num out">{{ money($spendingByCategory->sum()) }}</span></div>
            <ul class="list breakdown">
                @forelse ($spendingByCategory as $cat => $total)
                    <li>
                        <div class="row"><a href="{{ route('transactions.index', ['category' => $cat, 'from' => $year.'-01-01', 'to' => $year.'-12-31']) }}">{{ \App\Support\Ledger::categoryLabel($cat) }}</a><span class="num">{{ money($total) }} <small>{{ round($total / $spendTotal * 100) }}%</small></span></div>
                        <div class="meter"><span class="m-out" style="width: {{ round($total / $spendTotal * 100, 1) }}%"></span></div>
                    </li>
                @empty
                    <li class="muted">No spending recorded in {{ $year }}.</li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="card-head"><h2>Where the money came from</h2><span class="num in">{{ money($incomeByCategory->sum()) }}</span></div>
            <ul class="list breakdown">
                @forelse ($incomeByCategory as $cat => $total)
                    <li>
                        <div class="row"><a href="{{ route('transactions.index', ['category' => $cat, 'from' => $year.'-01-01', 'to' => $year.'-12-31']) }}">{{ \App\Support\Ledger::categoryLabel($cat) }}</a><span class="num">{{ money($total) }} <small>{{ round($total / $incomeTotal * 100) }}%</small></span></div>
                        <div class="meter"><span class="m-in" style="width: {{ round($total / $incomeTotal * 100, 1) }}%"></span></div>
                    </li>
                @empty
                    <li class="muted">No income recorded in {{ $year }}.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Profit by project</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Project</th><th class="right">Income</th><th class="right hide-sm">Partner</th><th class="right hide-sm">Spent</th><th class="right">My net</th></tr></thead>
                <tbody>
                    @forelse ($projects as $row)
                        <tr>
                            <td><a href="{{ route('projects.show', $row['project']) }}">{{ $row['project']->name }}</a><span class="sub">{{ $row['project']->client?->name ?? config('ledger.project_types')[$row['project']->type] }}</span></td>
                            <td class="right num in">{{ money($row['income']) }}</td>
                            <td class="right num hide-sm">{{ money($row['share']) }}</td>
                            <td class="right num out hide-sm">{{ money($row['spending']) }}</td>
                            <td class="right num {{ $row['net'] >= 0 ? 'in' : 'out' }}"><strong>{{ money($row['net']) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No project-linked transactions in {{ $year }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="muted" style="padding:0 16px 12px;margin:0;font-size:12px">Hosting bills are shared across projects on a server, so they're counted in totals but not per project unless you link them.</p>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-head"><h2>Spending by account</h2></div>
            <ul class="list">
                @forelse ($accounts as $row)
                    <li>
                        <div class="grow"><a class="title" href="{{ route('accounts.show', $row['account']) }}">{{ $row['account']->display_name }}</a><small>{{ $row['count'] }} payments</small></div>
                        <span class="num out">{{ money($row['total']) }}</span>
                    </li>
                @empty
                    <li class="muted">No account-linked spending.</li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="card-head"><h2>Recurring commitments (per year)</h2></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Client billing</dt><dd class="num in">{{ money($recurring['billing']) }}</dd>
                    <dt>Hosting</dt><dd class="num out">{{ money($recurring['hosting']) }}</dd>
                    <dt>Domain renewals</dt><dd class="num out">{{ money($recurring['domains']) }}</dd>
                    <dt><strong>Run-rate net</strong></dt><dd class="num {{ $recurring['net'] >= 0 ? 'in' : 'out' }}"><strong>{{ money($recurring['net']) }}</strong></dd>
                </dl>
                <p class="muted" style="margin-bottom:0;font-size:12px">Based on active projects, active servers and active domains you pay for. Excludes one-off build fees and ad revenue.</p>
            </div>
        </div>
    </div>
</x-layout>
