@php $editing = $invoice->exists; @endphp
<x-layout :title="$editing ? 'Edit bill '.$invoice->number : 'New bill'" :crumb="'<a href=\''.route('invoices.index').'\'>Bills</a>'">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('invoices.update', $invoice) : route('invoices.store') }}" class="stack" data-invoice-form>
                @csrf @if ($editing) @method('PUT') @endif
                <div class="form-grid">
                    <x-select name="project_id" label="Project" :options="$projects->mapWithKeys(fn ($p) => [$p->id => $p->name.($p->client ? ' — '.$p->client->name : '')])" :value="$invoice->project_id" placeholder="— None —"/>
                    <x-select name="client_id" label="Bill to (client)" :options="$clients->mapWithKeys(fn ($c) => [$c->id => $c->company ? $c->name.' ('.$c->company.')' : $c->name])" :value="$invoice->client_id" placeholder="— From project —"/>
                    <x-input name="title" label="Title" :value="$invoice->title" placeholder="Final bill · Website development"/>
                    <x-input name="number" label="Bill number" :value="$invoice->number" :placeholder="$editing ? '' : \App\Models\Invoice::nextNumber().' (auto)'"/>
                    <x-input name="issue_date" label="Bill date" type="date" :value="$invoice->issue_date?->toDateString()" required/>
                    <x-input name="due_date" label="Due date" type="date" :value="$invoice->due_date?->toDateString()"/>
                </div>

                <fieldset>
                    <legend>Items</legend>
                    <div class="items-table">
                        <div class="items-head"><span>Description</span><span>Qty</span><span>Rate</span><span class="right">Amount</span><span></span></div>
                        <div data-items>
                            @foreach ($items as $i => $item)
                                <div class="item-row" data-item>
                                    <input name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}" placeholder="Description" aria-label="Description">
                                    <input name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" type="number" step="0.01" min="0" aria-label="Quantity" data-qty>
                                    <input name="items[{{ $i }}][rate]" value="{{ $item['rate'] ?? '' }}" type="number" step="0.01" min="0" aria-label="Rate" data-rate>
                                    <span class="right num" data-line>0</span>
                                    <button type="button" class="btn btn-sm" data-remove aria-label="Remove line"><x-icon name="trash"/></button>
                                </div>
                            @endforeach
                        </div>
                        @error('items')<span class="error">{{ $message }}</span>@enderror
                        <button type="button" class="btn btn-sm" data-add-item style="margin-top:8px"><x-icon name="plus"/> Add line</button>
                    </div>
                    <div class="form-grid" style="margin-top:14px">
                        <x-input name="discount" label="Discount (amount)" type="number" step="0.01" min="0" :value="$invoice->discount ?? 0" data-discount/>
                        <div class="form-grid" style="gap:10px">
                            <x-input name="tax_label" label="Tax name" :value="$invoice->tax_label" placeholder="GST"/>
                            <x-input name="tax_percent" label="Tax %" type="number" step="0.01" min="0" max="100" :value="$invoice->tax_percent ?? 0" data-tax/>
                        </div>
                    </div>
                    <div class="items-total">Total <strong class="num" data-total>{{ config('ledger.currency') }}0.00</strong></div>
                </fieldset>

                <div class="form-grid">
                    <x-select name="status" label="Status" :options="['sent' => 'Sent / active', 'draft' => 'Draft (not shareable, not counted)', 'cancelled' => 'Cancelled']" :value="$invoice->status"/>
                    <x-textarea name="notes" label="Notes / terms (printed on bill)" :value="$invoice->notes"/>
                </div>

                <div class="form-actions">
                    @if ($editing)<x-delete-button :action="route('invoices.destroy', $invoice)" confirm="Delete this bill? Payments stay as project income."/>@endif
                    <a class="btn" href="{{ $editing ? route('invoices.show', $invoice) : route('invoices.index') }}">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save bill</button>
                </div>
            </form>
        </div>
    </div>
    <script>window.LEDGER_CURRENCY = @js(config('ledger.currency'));</script>
</x-layout>
