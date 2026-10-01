@props([
    'label' => '',
    'name' => '',
    'id' => '',
    'value' => '',
    'type' => 'text',
    'required' => false,
    'autofocus' => false,
    'disabled' => false,
    'placeholder' => '',
    'variant' => 'primary',
    'autocomplete' => 'on',
])
@php
    $palette = [
        'primary' => 'input-primary',
        'secondary' => 'input-secondary',
    ][$variant] ?? '';
@endphp

<label {{ $attributes->merge(['class' => "flex flex-col $palette"]) }}>
    @if(filled($label))
        <p class="label">{{ $label }}</p>
    @endif
    <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" autocomplete="{{ $autocomplete }}" @if ($autofocus) autofocus @endif @if ($disabled) disabled @endif placeholder="{{ $placeholder }}"
        class="form-input"
        value="{{ old($name, $value) }}" />
</label>
@error($name)
    <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
@enderror