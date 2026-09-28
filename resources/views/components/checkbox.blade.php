@props(['name', 'label', 'checked' => false])
<label class="check">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked)) {{ $attributes }}>
    <span>{{ $label }}</span>
</label>
