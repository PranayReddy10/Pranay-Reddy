<x-layout title="Renewals & dues" crumb="Domains, hosting bills and client billing">
    <x-slot:actions>
        <form method="GET" class="inline">
            <select name="days" onchange="this.form.submit()" style="width:auto">
                @foreach ([30, 60, 90, 180, 365] as $d)
                    <option value="{{ $d }}" @selected($days === $d)>Next {{ $d }} days</option>
                @endforeach
            </select>
        </form>
    </x-slot:actions>

    <div class="grid grid-3">
        <x-stat label="To pay (domains + hosting)" :value="money($totalOut)" tone="out"/>
        <x-stat label="To collect from clients" :value="money($totalIn)" tone="in"/>
        <x-stat label="Overdue items" :value="$items->filter(fn ($i) => $i['date']->isPast() && ! $i['date']->isToday())->count()"/>
    </div>

    <div class="card">
        <div class="card-head"><h2>{{ $items->count() }} items due (including overdue)</h2></div>
        @include('renewals._list', ['items' => $items])
    </div>

    <p class="muted" style="margin-top:12px">Tapping <strong>Paid / Renewed / Received</strong> books the transaction at the listed amount and rolls the due date forward by one cycle (domains by one year). Open the item to record a different amount.</p>
</x-layout>
