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

{{--
    La etiqueta va como en los demás controles: el texto y el asterisco dentro
    de UN solo span. Si van sueltos, el 'justify-between' los manda a cada
    extremo y el asterisco aparece despegado, al otro lado del campo.

    El 'gap-1.5' es la separación entre la etiqueta y el campo que usan los
    demás; sin él este quedaba más apretado que el resto del formulario.
--}}
<label {{ $attributes->merge(['class' => "flex flex-col gap-1.5 w-full $palette"]) }}>
    @if(filled($label))
        <span class="font-inter text-size-small-heading text-black flex items-center justify-between">
            <span>
                {{ $label }}
                @if($required) <span class="text-red-500">*</span> @endif
            </span>
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