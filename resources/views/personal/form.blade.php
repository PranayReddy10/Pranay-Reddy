@php $editing = $expense->exists; @endphp
<x-layout :title="$editing ? 'Edit personal expense' : 'Add personal expense'" :crumb="'<a href=\''.route('personal.index').'\'>Personal spending</a>'">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('personal.update', $expense) : route('personal.store') }}" class="stack">
                @csrf @if ($editing) @method('PUT') @endif
                <div class="form-grid">
                    <x-input name="amount" label="Amount" type="number" step="0.01" min="0.01" :value="$expense->amount" required autofocus inputmode="decimal"/>
                    <x-select name="category" label="Category" :options="config('ledger.personal_categories')" :value="$expense->category ?? 'food'"/>
                    <x-input name="date" label="Date" type="date" :value="$expense->date?->toDateString()" required/>
                    <x-select name="payment_method" label="Paid with" :options="array_combine(config('ledger.payment_methods'), config('ledger.payment_methods'))" :value="$expense->payment_method ?? 'UPI'" placeholder="—"/>
                    <x-input name="paid_to" label="Paid to" :value="$expense->paid_to" placeholder="Shop / person"/>
                    <x-input name="description" label="Note" :value="$expense->description"/>
                </div>
                <div class="form-actions">
                    @if ($editing)<x-delete-button :action="route('personal.destroy', $expense)"/>@endif
                    <a class="btn" href="{{ route('personal.index') }}">Cancel</a>
                    @unless ($editing)<button type="submit" name="add_another" value="1" class="btn">Save &amp; add another</button>@endunless
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
