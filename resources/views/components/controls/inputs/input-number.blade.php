@props([
    'label' => '',
    'name' => '',
    'id' => '',
    'value' => '',
    'required' => false,
    'disabled' => false,
    'autofocus' => false,
    'placeholder' => '',
    'min' => null,
    'max' => null,
    'step' => 'any',
    'variant' => 'primary',
    'dotName' => '',
])

@php
    $fieldId = $id ?: Str::slug($name);
    $errorKey = $dotName ?: $name;

    $variants = [
        'primary' => 'input-primary',
        'secondary' => 'input-secondary',
    ];

    $palette = $variants[$variant] ?? '';
@endphp

<label {{ $attributes->merge(['class' => "flex flex-col $palette"]) }}>
    @if(filled($label))
        <span class="font-inter text-size-small-heading text-black flex items-center justify-between">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </span>
    @endif

    <input 
        type="number" 
        id="{{ $fieldId }}" 
        name="{{ $name }}" 
        value="{{ old($errorKey, $value) }}"
        placeholder="{{ $placeholder }}"
        step="{{ $step }}"
        @if(!is_null($min)) min="{{ $min }}" @endif
        @if(!is_null($max)) max="{{ $max }}" @endif
        @required($required)
        @disabled($disabled)
        @autofocus($autofocus)
        @class(['form-input', 'border-red-500' => $errors->has($errorKey)])
    />

    @error($errorKey)
        <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
    @enderror
</label>