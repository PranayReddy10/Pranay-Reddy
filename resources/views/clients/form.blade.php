@php $editing = $client->exists; @endphp
<x-layout :title="$editing ? 'Edit '.$client->name : 'Add client'" :crumb="'<a href=\''.route('clients.index').'\'>Clients</a>'">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('clients.update', $client) : route('clients.store') }}" class="stack">
                @csrf @if ($editing) @method('PUT') @endif
                <div class="form-grid">
                    <x-input name="name" label="Name" :value="$client->name" required/>
                    <x-input name="company" label="Company / business" :value="$client->company"/>
                    <x-input name="email" label="Email" type="email" :value="$client->email"/>
                    <x-input name="phone" label="Phone / WhatsApp" :value="$client->phone"/>
                    <x-textarea name="notes" label="Notes" :value="$client->notes"/>
                </div>
                <div class="form-actions">
                    @if ($editing)<x-delete-button :action="route('clients.destroy', $client)" confirm="Delete this client? Their projects are kept but unlinked."/>@endif
                    <a class="btn" href="{{ $editing ? route('clients.show', $client) : route('clients.index') }}">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save client</button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
