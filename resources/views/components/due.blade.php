@props(['date'])
@php [$state, $text] = \App\Support\Ledger::dueState($date); @endphp
@if ($date)
    <span class="nowrap">{{ $date->format('d M Y') }}</span>
    <span class="badge badge-{{ $state }}">{{ $text }}</span>
@else
    <span class="muted">—</span>
@endif
