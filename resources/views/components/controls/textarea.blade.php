@props([
    'label' => '',
    'name' => '',
    'placeholder' => '',
    'value' => null,
    'variant' => 'primary',
])

@php
    $content = old($name, $value);
@endphp

@php
    $palette = [
        'primary'   => 'input-primary',
        'secondary'   => 'input-secondary',
    ][$variant] ?? '';
@endphp

<label {{ $attributes->merge(['class' => "flex flex-col $palette"]) }}>
     @if(filled($label))
        <p class="label">{{ $label }}</p>
    @endif
    <textarea name="{{ $name }}" placeholder="{{ $placeholder }}">{{ $content }}</textarea>
</label>
@error($name)
    <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
@enderror