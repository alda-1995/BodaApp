@props([
    'label' => '',
    'idParent' => null,
    'name' => '',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Seleccione una opción',
    'variant' => 'main',
])

@php
    $value = old($name, $selected);
    $items = $options;

    if (!array_key_exists('', $items)) {
        $items = ['' => $placeholder] + $items;
    }

    $palette = [
        'main' => 'select-main',
    ][$variant] ?? '';
@endphp

<div {{ $attributes->merge(['class' => "flex flex-col $palette"]) }} @if($idParent) id="{{ $idParent }}" @endif>
    @if(filled($label))
        <p class="label">{{ $label }}</p>
    @endif
    <div class="relative w-full select-container">
        <select
            name="{{ $name }}"
        >
            @foreach ($items as $optValue => $optLabel)
                <option
                    value="{{ $optValue }}"
                    @selected((string) $value === (string) $optValue)
                >
                    {{ $optLabel }}
                </option>
            @endforeach
        </select>
        
        <div class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-accent">
            <x-icons.arrow-down />
        </div>
    </div>
    
    @error($name)
        <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
    @enderror
</div>