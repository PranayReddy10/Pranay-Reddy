@props(['action', 'label' => 'Delete', 'confirm' => 'Delete this record? This cannot be undone.'])
<form method="POST" action="{{ $action }}" class="inline" onsubmit="return confirm(@js($confirm))">
    @csrf @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => 'btn btn-danger']) }}><x-icon name="trash"/> {{ $label }}</button>
</form>
