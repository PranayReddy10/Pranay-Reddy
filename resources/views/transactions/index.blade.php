<x-layout title="Transactions">
    <x-slot:actions>
        <a class="btn" href="{{ route('transactions.export', request()->query()) }}"><x-icon name="download"/> CSV</a>
        <a class="btn btn-in" href="{{ route('transactions.create', ['type' => 'income']) }}"><x-icon name="plus"/> Income</a>
        <a class="btn btn-out" href="{{ route('transactions.create', ['type' => 'expense']) }}"><x-icon name="plus"/> Expense</a>
    </x-slot:actions>

    <div class="grid grid-4">
        <x-stat label="Income (filtered)" :value="money($totals['income'])" tone="in"/>
        <x-stat label="Partner share" :value="money($totals['share'])"/>
        <x-stat label="Expenses (filtered)" :value="money($totals['expense'])" tone="out"/>
        <x-stat label="Net" :value="money($totals['income'] - $totals['share'] - $totals['expense'])"/>
    </div>

    <div class="card">
        <form method="GET" class="filters">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search description">
            <select name="type">
                <option value="">Income &amp; expense</option>
                <option value="income" @selected(request('type') === 'income')>Income</option>
                <option value="expense" @selected(request('type') === 'expense')>Expense</option>
            </select>
            <select name="category">
                <option value="">All categories</option>
                <optgroup label="Income">@foreach (config('ledger.income_categories') as $k => $v)<option value="{{ $k }}" @selected(request('category') === $k)>{{ $v }}</option>@endforeach</optgroup>
                <optgroup label="Expense">@foreach (config('ledger.expense_categories') as $k => $v)<option value="{{ $k }}" @selected(request('category') === $k)>{{ $v }}</option>@endforeach</optgroup>
            </select>
            <select name="project">
                <option value="">All projects</option>
                @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(request('project') == $p->id)>{{ $p->name }}</option>@endforeach
            </select>
            <select name="account">
                <option value="">All accounts</option>
                @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected(request('account') == $a->id)>{{ $a->display_name }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" title="From">
            <input type="date" name="to" value="{{ request('to') }}" title="To">
            <button type="submit" class="btn">Filter</button>
            @if (request()->query())<a class="btn" href="{{ route('transactions.index') }}">Reset</a>@endif
        </form>
        <x-txn-table :transactions="$transactions"/>
        @if ($transactions->hasPages())<div class="pagination">{{ $transactions->links('partials.pagination') }}</div>@endif
    </div>
</x-layout>
