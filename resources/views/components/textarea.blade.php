@props(['name', 'label', 'value' => null, 'class' => 'full'])
<label class="field {{ $class }}">
    <span>{{ $label }}</span>
    <textarea name="{{ $name }}" rows="3" {{ $attributes }}>{{ old($name, $value) }}</textarea>
    @error($name)<span class="error">{{ $message }}</span>@enderror
</label>
