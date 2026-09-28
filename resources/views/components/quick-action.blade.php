{{-- One-tap "mark paid / renewed / received" button that posts to an action route. --}}
@props(['action', 'label', 'tone' => 'in', 'confirm' => null])
<form method="POST" action="{{ $action }}" class="inline" @if ($confirm) onsubmit="return confirm(@js($confirm))" @endif>
    @csrf
    <button type="submit" class="btn btn-sm btn-{{ $tone }}"><x-icon name="check"/> {{ $label }}</button>
</form>
