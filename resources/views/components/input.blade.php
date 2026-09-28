@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'class' => ''])
<label class="field {{ $class }}">
    <span>{{ $label }}</span>
    <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}" {{ $attributes }}>
    @if ($help)<span class="help">{{ $help }}</span>@endif
    @error($name)<span class="error">{{ $message }}</span>@enderror
</label>
