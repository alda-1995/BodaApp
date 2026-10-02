@props([
    'label' => '',
    'name' => '',
    'id' => null,
    'placeholder' => '',
    'value' => null,
    'rows' => 4,
    'required' => false,
    'autofocus' => false,
    'disabled' => false,
    'variant' => 'primary',
    'dotName' => '',
])

@php
    $id = $id ?? $name;
    $errorKey = $dotName ?: $name;
    $hasError = $errors->has($errorKey);
    $content = old($errorKey, $value);

    $inputBase = 'mb-4 border rounded-md px-4 py-3 text-size-small-heading font-inter transition-colors duration-200 focus:outline-none disabled:bg-gray-100 disabled:cursor-not-allowed resize-none';

    $variants = [
        'primary' => 'border-base-gray text-black focus:border-main focus:ring-1 focus:ring-main',
        'secondary' => 'border-transparent bg-secondary text-black focus:border-gray-400 focus:bg-white',
    ];

    // 3. Estilo reactivo según error de validación
    $inputStyles = $hasError 
        ? 'border-red-500 bg-white text-red-900 focus:border-red-500 focus:ring-1 focus:ring-red-500' 
        : ($variants[$variant] ?? $variants['primary']);
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5 w-full']) }}>
    @if(filled($label))
        <label for="{{ $id }}" class="font-inter text-size-small-heading text-black flex items-center justify-between">
            <span>
                {{ $label }}
                @if($required) <span class="text-red-500">*</span> @endif
            </span>
        </label>
    @endif

    <textarea 
        id="{{ $id }}"
        name="{{ $name }}" 
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @required($required)
        @autofocus($autofocus)
        @disabled($disabled)
        class="{{ $inputBase }} {{ $inputStyles }}"
    >{{ $content }}</textarea>

    @error($errorKey)
        <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
    @enderror
</div>