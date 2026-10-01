@props([
    'label' => '',
    'name' => '',
    'id' => '',
    'value' => '#000000',
    'required' => false,
    'disabled' => false,
    'variant' => 'primary',
    'dotName' => '',
])

@php
    $palette = [
        'primary' => 'color-primary',
    ][$variant] ?? '';
    $fieldId = $id ?: Str::slug($name);
    
    $errorKey = $dotName ?: $name;
@endphp

<label {{ $attributes->merge(['class' => "flex flex-col $palette"]) }}>
    @if(filled($label))
        <span class="label">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</span>
    @endif

    <div x-data="{ color: '{{ old($errorKey, $value) ?: '#000000' }}' }" class="inner-flex">
        <input 
            type="color" 
            id="{{ $fieldId }}" 
            name="{{ $name }}" 
            x-model="color"
            @if ($disabled) disabled @endif
            @if ($required) required @endif
            class="color-picker-input cursor-pointer" 
        />

        {{-- Input textual sincronizado en tiempo real --}}
        <input 
            type="text" 
            x-model="color" 
            placeholder="#000000"
            maxlength="7"
            @if ($disabled) disabled @endif
            class="form-input uppercase"
        />
    </div>

    @error($errorKey)
        <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
    @enderror
</label>