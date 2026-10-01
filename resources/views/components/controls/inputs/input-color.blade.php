@props([
    'label' => '',
    'name' => '',
    'id' => null,
    'value' => '#000000',
    'required' => false,
    'disabled' => false,
    'variant' => 'primary',
    'dotName' => '',
])

@php
    $id = $id ?? $name;
    $errorKey = $dotName ?: $name;
    $hasError = $errors->has($errorKey);

    $inputBase = 'mb-4 border rounded-md px-3 py-1 h-[50px] text-size-small-heading font-inter transition-colors duration-200 focus:outline-none disabled:bg-gray-100 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'border-base-gray text-black focus:border-main focus:ring-1 focus:ring-main',
        'secondary' => 'border-transparent bg-secondary text-black focus:border-gray-400 focus:bg-white',
    ];

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

    <div 
        x-data="{ color: '{{ old($errorKey, $value) ?: '#000000' }}' }" 
        class="flex items-center gap-2"
    >
        <input 
            type="color" 
            id="{{ $id }}" 
            name="{{ $name }}" 
            x-model="color"
            @disabled($disabled)
            @required($required)
            class="h-[50px] w-[50px] mb-4 cursor-pointer rounded-md border border-base-gray bg-transparent p-1 disabled:cursor-not-allowed" 
        />

        <input 
            type="text" 
            x-model="color" 
            placeholder="#000000"
            maxlength="7"
            @disabled($disabled)
            class="flex-1 uppercase {{ $inputBase }} {{ $inputStyles }}"
        />
    </div>

    @error($errorKey)
        <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
    @enderror
</div>