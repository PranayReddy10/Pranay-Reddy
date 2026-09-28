<x-layout title="Settings" crumb="Your details printed on bills">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('settings') }}" class="stack">
                @csrf @method('PUT')
                <div class="form-grid">
                    @foreach (config('ledger.profile_fields') as $key => $label)
                        @if (in_array($key, ['address', 'bank_details', 'invoice_terms'], true))
                            <x-textarea :name="$key" :label="$label" :value="$profile[$key] ?? null"/>
                        @else
                            <x-input :name="$key" :label="$label" :value="$profile[$key] ?? ($key === 'invoice_prefix' ? 'INV' : null)"
                                :help="$key === 'upi_id' ? 'Adds a UPI QR code and “Pay via UPI” button to bills with a balance.' : null"/>
                        @endif
                    @endforeach
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-primary">Save settings</button></div>
            </form>
        </div>
    </div>
</x-layout>
