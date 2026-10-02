@props([
    'label' => '',
    'idParent' => null,
    'id' => null,
    'name' => '',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Seleccione una opción',
    'required' => false,
    'disabled' => false,
    'variant' => 'main',
    'dotName' => '',
    'xModel' => null,
])

@php
    $fieldId = $id ?: Str::slug($name);
    $errorKey = $dotName ?: $name;
    $value = old($errorKey, $selected);

    $items = $options;
    if (!array_key_exists('', $items)) {
        $items = ['' => $placeholder] + $items;
    }

    $variants = [
        'main' => 'select-main',
        'secondary' => 'select-secondary',
    ];

    $palette = $variants[$variant] ?? $variants['main'];
@endphp

<div {{ $attributes->merge(['class' => "flex flex-col gap-1.5 $palette"]) }} @if($idParent) id="{{ $idParent }}" @endif>
    @if(filled($label))
        {{-- La misma etiqueta que el resto del formulario, no la clase suelta '.label'. --}}
        <label for="{{ $fieldId }}" class="font-inter text-size-small-heading text-black flex items-center justify-between">
            <span>
                {{ $label }}
                @if($required) <span class="text-red-500">*</span> @endif
            </span>
        </label>
    @endif

    <div class="relative w-full select-container">
        <select
            id="{{ $fieldId }}"
            name="{{ $name }}"
            @required($required)
            @disabled($disabled)
            @if($xModel)
                x-model="{{ $xModel }}"
            @endif
            @class([
                'form-select w-full appearance-none', 
                'border-red-500' => $errors->has($errorKey)
            ])
        >
            @foreach ($items as $optValue => $optLabel)
                <option
                    value="{{ $optValue }}"
                    @selected((string) $value === (string) $optValue)
                    @if($loop->first && $optValue === '') disabled @endif
                >
                    {{ $optLabel }}
                </option>
            @endforeach
        </select>
        
        <div class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-accent">
            <x-icons.arrow-down />
        </div>
    </div>
    
    @error($errorKey)
        <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
    @enderror
</div>