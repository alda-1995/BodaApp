@props([
    'label' => '',
    'name' => '',
    'id' => '',
    'value' => '',
    'required' => false,
    'autofocus' => false,
    'disabled' => false,
    'placeholder' => '',
    'variant' => 'primary',
    'autocomplete' => 'new-password',
])

@php
    $palette = [
        'primary' => 'input-password',
        'secondary' => 'input-secondary',
    ][$variant] ?? '';
@endphp

<label {{ $attributes->merge(['class' => "flex flex-col $palette"]) }}>
    @if(filled($label))
        <p class="label">{{ $label }}</p>
    @endif
    <div x-data="{ show: false }" class="relative w-full">
        <input
            :type="show ? 'text' : 'password'"
            type="password"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            autocomplete="{{ $autocomplete }}"
            @if($required) required @endif
            @if($autofocus) autofocus @endif
            @if($disabled) disabled @endif
        />
        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500"
            @click="show = !show" tabindex="-1">
            <span x-show="!show">
                <x-icons.eye />
            </span>
            <span x-show="show">
                <x-icons.eye-slash />
            </span>
        </button>
    </div>
</label>
@error($name)
    <span class="error text-red-500 text-sm mb-4 block">{{ $message }}</span>
@enderror