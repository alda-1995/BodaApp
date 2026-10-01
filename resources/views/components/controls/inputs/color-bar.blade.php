@props([
    'label' => '',
    'name' => '',
    'id' => null,
    'value' => '#000000',
    'required' => false,
    'disabled' => false,
    'dot' => true,
    'dotName' => '',
])

@php
    $id = $id ?? $name;
    $errorKey = $dotName ?: $name;
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5 w-full']) }}>
    @if(filled($label))
        <label for="{{ $id }}" class="font-inter text-size-small-heading text-black flex items-center gap-1.5">
            @if($dot)
                <span 
                    class="w-2.5 h-2.5 rounded-full inline-block border border-black/10 shrink-0 transition-colors duration-150"
                    :style="`background-color: ${color}`"
                ></span>
            @endif
            <span>
                {{ $label }}
                @if($required) <span class="text-red-500">*</span> @endif
            </span>
        </label>
    @endif

    <div class="relative flex items-center">
        <!-- Input de texto principal que activa el colorpicker -->
        <input 
            type="text" 
            id="{{ $id }}" 
            name="{{ $name }}" 
            x-model="color" 
            maxlength="7"
            @disabled($disabled)
            @required($required)
            @click="$refs.picker.click()"
            class="mb-4 border rounded-md px-4 py-1 h-[50px] text-size-small-heading font-inter w-full border-base-gray text-black"
        />

        <!-- Input de color nativo oculto pero clickeable mediante $refs -->
        <input 
            x-ref="picker"
            type="color" 
            x-model="color"
            @disabled($disabled)
            class="absolute right-2 opacity-0 w-6 h-6 cursor-pointer pointer-events-none" 
        />
    </div>

    @error($errorKey)
        <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
    @enderror
</div>