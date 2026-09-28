@php $editing = $server->exists; @endphp
<x-layout :title="$editing ? 'Edit '.$server->name : 'Add server'" :crumb="'<a href=\''.route('servers.index').'\'>Hosting / servers</a>'">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('servers.update', $server) : route('servers.store') }}" class="stack">
                @csrf @if ($editing) @method('PUT') @endif
                <div class="form-grid">
                    <x-input name="name" label="Server name" :value="$server->name" placeholder="Hostinger Business #1" required/>
                    <x-select name="account_id" label="Hosting account" :options="$accounts->mapWithKeys(fn ($a) => [$a->id => $a->display_name])" :value="$server->account_id" placeholder="— Choose account —"/>
                    <x-input name="plan" label="Plan" :value="$server->plan" placeholder="VPS 2GB / Shared Premium"/>
                    <x-input name="ip_address" label="IP address" :value="$server->ip_address"/>
                    <x-input name="location" label="Location / region" :value="$server->location"/>
                    <x-select name="billing_cycle" label="Billing cycle" :options="collect(config('ledger.billing_cycles'))->map->label->all()" :value="$server->billing_cycle"/>
                    <x-input name="cost" label="Cost per cycle" type="number" step="0.01" min="0" :value="$server->cost ?? 0" required/>
                    <x-input name="next_due_date" label="Next due date" type="date" :value="$server->next_due_date?->toDateString()"/>
                    <x-checkbox name="auto_renew" label="Auto-renews / auto-debit" :checked="$server->auto_renew"/>
                    <x-checkbox name="is_active" label="Active" :checked="$server->is_active"/>
                    <x-textarea name="notes" label="Notes" :value="$server->notes"/>
                </div>
                <div class="form-actions">
                    @if ($editing)<x-delete-button :action="route('servers.destroy', $server)" confirm="Delete this server? Domains on it will be unlinked."/>@endif
                    <a class="btn" href="{{ $editing ? route('servers.show', $server) : route('servers.index') }}">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save server</button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
