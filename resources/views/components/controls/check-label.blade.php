@props([
    'label' => '',
    'name' => '',
    'id' => null,
    'value' => '1',
    'checked' => false,
    'required' => false,
    'disabled' => false,
    'autofocus' => false,
])

@php
    $id = $id ?? $name;
    $hasError = filled($name) ? $errors->has($name) : false;

    $defaultChecked = filter_var($checked, FILTER_VALIDATE_BOOLEAN);

    if (filled($name) && session()->hasOldInput()) {
        $isChecked = filter_var(old($name), FILTER_VALIDATE_BOOLEAN);
    } else {
        $isChecked = $defaultChecked;
    }
@endphp

<div class="flex flex-col gap-1.5 w-full mb-4">
    <label 
        @if($id) for="{{ $id }}" @endif 
        class="inline-flex items-center gap-3 cursor-pointer select-none group {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
    >
        <div class="relative flex items-center justify-center">
            <input type="hidden" name="{{ $name }}" value="0" />

            <input 
                type="checkbox"
                id="{{ $id }}"
                name="{{ $name }}"
                value="{{ $value }}"
                @checked($isChecked)
                @required($required)
                @disabled($disabled)
                @autofocus($autofocus)
                {{ $attributes->merge(['class' => 'peer sr-only']) }}
            />

            {{-- Círculo personalizado --}}
            <div @class([
                'w-[22px] h-[22px] rounded-full border-2 border-black flex items-center justify-center transition-colors duration-150 bg-white',
                'text-transparent peer-checked:text-black',
                'peer-focus-visible:ring-2 peer-focus-visible:ring-black peer-focus-visible:ring-offset-2',
                '!border-red-500' => $hasError,
            ])>
                {{-- Ícono Check --}}
                <svg 
                    class="w-3.5 h-3.5 stroke-current" 
                    fill="none" 
                    viewBox="0 0 24 24" 
                    stroke="currentColor" 
                    stroke-width="3"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>

        @if(filled($label))
            <span class="font-inter text-size-small-heading text-black">
                {{ $label }}
                @if($required) <span class="text-red-500">*</span> @endif
            </span>
        @endif
    </label>

    @if(filled($name))
        @error($name)
            <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
        @enderror
    @endif
</div>