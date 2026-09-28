@props(['label', 'value', 'hint' => null, 'tone' => null])
<div {{ $attributes->merge(['class' => 'card stat']) }}>
    <div class="label">{{ $label }}</div>
    <div class="value {{ $tone }}">{{ $value }}</div>
    @if ($hint)<div class="hint">{{ $hint }}</div>@endif
</div>
