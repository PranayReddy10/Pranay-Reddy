@php
    $cats = config('ledger.personal_categories');
    $prev = $month->copy()->subMonth()->format('Y-m');
    $next = $month->copy()->addMonth()->format('Y-m');
    $max = max(1, $trend->max('income'), $trend->max('personal'));
    $budgetLeft = $budgetTotal - $total;
@endphp
<x-layout title="Personal spending" :crumb="$month->format('F Y').' · kept separate from business books'">
    <x-slot:actions>
        <a class="btn" href="{{ route('personal.index', ['month' => $prev]) }}" aria-label="Previous month">‹</a>
        <form method="GET" class="inline"><input type="month" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" style="width:auto"></form>
        <a class="btn" href="{{ route('personal.index', ['month' => $next]) }}" aria-label="Next month">›</a>
        <a class="btn btn-primary" href="{{ route('personal.create') }}"><x-icon name="plus"/> Add</a>
    </x-slot:actions>

    <div class="grid grid-4">
        <x-stat label="Spent this month" :value="money($total)" tone="out" :hint="$expenses->count().' entries'"/>
        <x-stat label="Budget left" :value="$budgetTotal > 0 ? money($budgetLeft) : 'No budget'" :tone="$budgetTotal > 0 ? ($budgetLeft >= 0 ? 'in' : 'out') : null"
                :hint="$budgetTotal > 0 ? 'of '.money($budgetTotal).($daysLeft ? ' · '.$daysLeft.' days left' : '') : 'Set limits below'"/>
        <x-stat label="Business net earned" :value="money($business['net'])" :tone="$business['net'] >= 0 ? 'in' : 'out'" hint="Same month, from work"/>
        <x-stat label="Saved (earned − personal)" :value="money($savings)" :tone="$savings >= 0 ? 'in' : 'out'"
                :hint="$savings < 0 ? 'Spent more than work earned' : ($business['net'] > 0 ? 'Savings rate '.round($savings / $business['net'] * 100).'%' : null)"/>
    </div>

    <div class="grid grid-main">
        <div class="card">
            <div class="card-head">
                <h2>{{ $month->format('F') }} entries</h2>
                <form method="GET" class="inline">
                    <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                    <select name="category" onchange="this.form.submit()" style="width:auto">
                        <option value="">All categories</option>
                        @foreach ($cats as $k => $v)<option value="{{ $k }}" @selected(request('category') === $k)>{{ $v }}</option>@endforeach
                    </select>
                </form>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Details</th><th class="right">Amount</th><th class="hide-sm"></th></tr></thead>
                    <tbody>
                        @forelse ($expenses as $e)
                            <tr>
                                <td class="nowrap">{{ $e->date->format('d M') }}</td>
                                <td>{{ $e->description ?: $e->category_label }}<span class="sub">{{ collect([$e->category_label, $e->paid_to, $e->payment_method])->filter()->join(' · ') }}</span></td>
                                <td class="right num out">{{ money($e->amount) }}</td>
                                <td class="right hide-sm"><a class="btn btn-sm" href="{{ route('personal.edit', $e) }}">Edit</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">Nothing recorded for {{ $month->format('F Y') }}. <a href="{{ route('personal.create') }}">Add an expense</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>By category</h2></div>
            <ul class="list breakdown">
                @php $shown = $byCategory->keys()->merge($budgets->keys())->unique(); @endphp
                @forelse ($shown as $cat)
                    @php
                        $spent = $byCategory[$cat] ?? 0;
                        $limit = $budgets[$cat] ?? 0;
                        $width = $limit > 0 ? min(100, $spent / $limit * 100) : ($total > 0 ? $spent / $total * 100 : 0);
                        $over = $limit > 0 && $spent > $limit;
                    @endphp
                    <li>
                        <div class="row">
                            <span>{{ $cats[$cat] ?? $cat }}</span>
                            <span class="num {{ $over ? 'out' : '' }}">{{ money($spent) }}@if ($limit > 0) <small>/ {{ money($limit) }}</small>@endif</span>
                        </div>
                        <div class="meter"><span class="{{ $over ? 'm-out' : ($limit > 0 ? 'm-in' : 'm-out') }}" style="width: {{ round($width, 1) }}%"></span></div>
                    </li>
                @empty
                    <li class="muted">No spending yet this month.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Earned vs personal spending — 12 months</h2>
            <div class="legend"><span><i style="background:var(--in)"></i>Business net income</span><span><i style="background:var(--out)"></i>Personal spending</span></div>
        </div>
        <div class="card-body">
            <div class="bars">
                @foreach ($trend as $m)
                    <div class="col" title="{{ $m['label'] }} — earned {{ money($m['net']) }}, spent {{ money($m['personal']) }}">
                        <div class="pair">
                            <div class="bar b-in" style="height: {{ round(max($m['net'], 0) / $max * 100, 1) }}%"></div>
                            <div class="bar b-out" style="height: {{ round($m['personal'] / $max * 100, 1) }}%"></div>
                        </div>
                        <div class="lbl">{{ \Illuminate\Support\Str::before($m['label'], ' ') }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <details class="card">
        <summary class="card-head" style="cursor:pointer"><h2>Monthly budgets</h2><small>tap to edit</small></summary>
        <div class="card-body">
            <form method="POST" action="{{ route('personal.budgets') }}" class="stack">
                @csrf
                <div class="form-grid">
                    @foreach ($cats as $k => $v)
                        <label class="field"><span>{{ $v }}</span><input type="number" step="1" min="0" name="budgets[{{ $k }}]" value="{{ isset($budgets[$k]) ? (float) $budgets[$k] : '' }}" placeholder="No limit"></label>
                    @endforeach
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-primary">Save budgets</button></div>
            </form>
        </div>
    </details>
</x-layout>
