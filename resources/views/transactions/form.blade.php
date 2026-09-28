@php
    $editing = $transaction->exists;
    $type = old('type', $transaction->type ?? 'expense');
    $category = old('category', $transaction->category);
    $methods = array_combine(config('ledger.payment_methods'), config('ledger.payment_methods'));
@endphp
<x-layout :title="$editing ? 'Edit transaction' : 'New transaction'" :crumb="'<a href=\''.route('transactions.index').'\'>Transactions</a>'">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('transactions.update', $transaction) : route('transactions.store') }}" class="stack" data-txn-form>
                @csrf @if ($editing) @method('PUT') @endif
                <input type="hidden" name="redirect_to" value="{{ old('redirect_to', $editing ? '' : url()->previous()) }}">

                <div style="display:flex;gap:8px">
                    <label class="btn btn-in" style="flex:1"><input type="radio" name="type" value="income" @checked($type === 'income')> Money in</label>
                    <label class="btn btn-out" style="flex:1"><input type="radio" name="type" value="expense" @checked($type === 'expense')> Money out</label>
                </div>

                <div class="form-grid">
                    <label class="field">
                        <span>Category</span>
                        <select name="category" required>
                            <optgroup label="Income" data-type="income">
                                @foreach (config('ledger.income_categories') as $k => $v)<option value="{{ $k }}" @selected($category === $k)>{{ $v }}</option>@endforeach
                            </optgroup>
                            <optgroup label="Expense" data-type="expense">
                                @foreach (config('ledger.expense_categories') as $k => $v)<option value="{{ $k }}" @selected($category === $k)>{{ $v }}</option>@endforeach
                            </optgroup>
                        </select>
                        @error('category')<span class="error">{{ $message }}</span>@enderror
                    </label>
                    <x-input name="amount" label="Amount" type="number" step="0.01" min="0.01" :value="$transaction->amount" required inputmode="decimal"/>
                    <x-input name="date" label="Date" type="date" :value="$transaction->date?->toDateString()" required/>
                    <x-select name="payment_method" label="Payment method" :options="$methods" :value="$transaction->payment_method" placeholder="—"/>
                    <x-input name="description" label="Description" :value="$transaction->description" class="full" placeholder="What was this for?"/>
                </div>

                <fieldset>
                    <legend>Link to</legend>
                    <div class="form-grid">
                        <label class="field">
                            <span>Project</span>
                            <select name="project_id">
                                <option value="">— None —</option>
                                @foreach ($projects as $p)
                                    <option value="{{ $p->id }}" data-share="{{ $p->isShared() ? $p->partner_share_percent : 0 }}" data-partner="{{ $p->partner?->name }}" @selected((string) old('project_id', $transaction->project_id) === (string) $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <span class="help" data-share-hint></span>
                        </label>
                        <x-select name="client_id" label="Client" :options="$clients->pluck('name', 'id')" :value="$transaction->client_id" placeholder="— From project —" data-show="income"/>
                        <x-select name="account_id" label="Account (paid from / received in)" :options="$accounts->mapWithKeys(fn ($a) => [$a->id => $a->display_name])" :value="$transaction->account_id" placeholder="— None —"/>
                        <x-select name="domain_id" label="Domain" :options="$domains->pluck('name', 'id')" :value="$transaction->domain_id" placeholder="— None —"/>
                        <x-select name="server_id" label="Server" :options="$servers->pluck('name', 'id')" :value="$transaction->server_id" placeholder="— None —"/>
                        <x-select name="partner_id" label="Partner (for payouts)" :options="$partners->pluck('name', 'id')" :value="$transaction->partner_id" placeholder="— None —" data-show="expense"/>
                        <x-input name="partner_share" label="Partner share of this income" type="number" step="0.01" min="0" :value="$editing ? $transaction->partner_share : null"
                            help="Leave empty to auto-split ad revenue using the project's %." data-show="income"/>
                        <x-input name="reference" label="Reference / invoice #" :value="$transaction->reference"/>
                    </div>
                </fieldset>

                <div class="form-actions">
                    @if ($editing)<x-delete-button :action="route('transactions.destroy', $transaction)"/>@endif
                    <a class="btn" href="{{ route('transactions.index') }}">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
