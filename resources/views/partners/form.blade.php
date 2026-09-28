@php $editing = $partner->exists; @endphp
<x-layout :title="$editing ? 'Edit '.$partner->name : 'Add partner'" :crumb="'<a href=\''.route('partners.index').'\'>Partners</a>'">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('partners.update', $partner) : route('partners.store') }}" class="stack">
                @csrf @if ($editing) @method('PUT') @endif
                <div class="form-grid">
                    <x-input name="name" label="Name" :value="$partner->name" required/>
                    <x-input name="phone" label="Phone" :value="$partner->phone"/>
                    <x-input name="email" label="Email" type="email" :value="$partner->email"/>
                    <x-input name="upi_or_bank" label="UPI ID / bank details" :value="$partner->upi_or_bank"/>
                    <x-textarea name="notes" label="Notes / agreement" :value="$partner->notes"/>
                </div>
                <div class="form-actions">
                    @if ($editing)<x-delete-button :action="route('partners.destroy', $partner)"/>@endif
                    <a class="btn" href="{{ $editing ? route('partners.show', $partner) : route('partners.index') }}">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save partner</button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
