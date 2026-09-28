@php $editing = $domain->exists; @endphp
<x-layout :title="$editing ? 'Edit '.$domain->name : 'Add domain'" :crumb="'<a href=\''.route('domains.index').'\'>Domains</a>'">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('domains.update', $domain) : route('domains.store') }}" class="stack">
                @csrf @if ($editing) @method('PUT') @endif
                <div class="form-grid">
                    <x-input name="name" label="Domain name" :value="$domain->name" placeholder="example.com" required/>
                    <x-select name="project_id" label="Project" :options="$projects->pluck('name', 'id')" :value="$domain->project_id" placeholder="— None —"/>
                    <x-select name="account_id" label="Registrar account" :options="$accounts->mapWithKeys(fn ($a) => [$a->id => $a->display_name])" :value="$domain->account_id" placeholder="— Choose account —"
                        help="Which login this domain sits in."/>
                    <x-select name="server_id" label="Hosted on server" :options="$servers->pluck('name', 'id')" :value="$domain->server_id" placeholder="— Not hosted / external —"/>
                    <x-input name="registered_on" label="Registered on" type="date" :value="$domain->registered_on?->toDateString()"/>
                    <x-input name="expires_on" label="Expires on" type="date" :value="$domain->expires_on?->toDateString()"/>
                    <x-input name="renewal_cost" label="Yearly renewal cost" type="number" step="0.01" min="0" :value="$domain->renewal_cost ?? 0" required/>
                    <x-select name="paid_by" label="Who pays renewal" :options="['me' => 'I pay (my expense)', 'client' => 'Client pays directly']" :value="$domain->paid_by"/>
                    <x-select name="status" label="Status" :options="config('ledger.domain_statuses')" :value="$domain->status"/>
                    <div class="field" style="align-self:end"><x-checkbox name="auto_renew" label="Auto-renew enabled at registrar" :checked="$domain->auto_renew"/></div>
                    @unless ($editing)
                        <fieldset class="full">
                            <legend>Purchase</legend>
                            <div class="form-grid">
                                <x-checkbox name="record_purchase" label="Record purchase as an expense now" :checked="true"/>
                                <x-input name="purchase_amount" label="Purchase amount" type="number" step="0.01" min="0" help="Leave empty to skip."/>
                            </div>
                        </fieldset>
                    @endunless
                    <x-textarea name="notes" label="Notes (DNS, nameservers, etc.)" :value="$domain->notes"/>
                </div>
                <div class="form-actions">
                    @if ($editing)<x-delete-button :action="route('domains.destroy', $domain)"/>@endif
                    <a class="btn" href="{{ $editing ? route('domains.show', $domain) : route('domains.index') }}">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save domain</button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
