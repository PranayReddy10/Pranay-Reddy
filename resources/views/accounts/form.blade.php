@php $editing = $account->exists; @endphp
<x-layout :title="$editing ? 'Edit account' : 'Add account'" :crumb="'<a href=\''.route('accounts.index').'\'>Accounts</a>'">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('accounts.update', $account) : route('accounts.store') }}" class="stack">
                @csrf @if ($editing) @method('PUT') @endif
                <div class="form-grid">
                    <x-input name="provider" label="Provider" :value="$account->provider" placeholder="GoDaddy, Hostinger, AWS, AdSense…" required list="providers"/>
                    <x-input name="label" label="Account label" :value="$account->label" placeholder="Personal, Client-X, Second Gmail…" required/>
                    <x-select name="type" label="Type" :options="config('ledger.account_types')" :value="$account->type"/>
                    <x-input name="login_email" label="Login email / username" :value="$account->login_email"/>
                    <x-input name="dashboard_url" label="Panel URL" type="url" :value="$account->dashboard_url" placeholder="https://"/>
                    <x-textarea name="notes" label="Notes (no passwords)" :value="$account->notes"/>
                </div>
                <datalist id="providers">
                    @foreach (['GoDaddy', 'Namecheap', 'Hostinger', 'BigRock', 'Cloudflare', 'Google Domains', 'Porkbun', 'AWS', 'DigitalOcean', 'Vultr', 'Contabo', 'Hetzner', 'Google AdSense', 'Ezoic', 'Media.net'] as $p)<option value="{{ $p }}">@endforeach
                </datalist>
                <div class="form-actions">
                    @if ($editing)<x-delete-button :action="route('accounts.destroy', $account)" confirm="Delete this account? Domains and servers in it will be unlinked."/>@endif
                    <a class="btn" href="{{ $editing ? route('accounts.show', $account) : route('accounts.index') }}">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save account</button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
