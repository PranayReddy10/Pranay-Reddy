@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null, 'help' => null, 'class' => ''])
@php $current = (string) old($name, $value); @endphp
<label class="field {{ $class }}">
    <span>{{ $label }}</span>
    <select name="{{ $name }}" {{ $attributes }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $key => $text)
            <option value="{{ $key }}" @selected($current === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>
    @if ($help)<span class="help">{{ $help }}</span>@endif
    @error($name)<span class="error">{{ $message }}</span>@enderror
</label>
