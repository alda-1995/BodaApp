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
    $palette = [
        'primary' => 'input-primary',
        'secondary' => 'input-secondary',
    ][$variant] ?? '';

    $fieldId = $id ?: Str::slug($name);
    $errorKey = $dotName ?: $name;
@endphp

<label {{ $attributes->merge(['class' => "flex flex-col $palette"]) }}>
    @if(filled($label))
        <span class="label">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</span>
    @endif

    <input 
        type="number" 
        id="{{ $fieldId }}" 
        name="{{ $name }}" 
        value="{{ old($errorKey, $value) }}"
        placeholder="{{ $placeholder }}"
        @if(!is_null($min)) min="{{ $min }}" @endif
        @if(!is_null($max)) max="{{ $max }}" @endif
        @if(!is_null($step)) step="{{ $step }}" @endif
        @if ($autofocus) autofocus @endif 
        @if ($disabled) disabled @endif
        @if ($required) required @endif
        class="form-input"
    />

    @error($errorKey)
        <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
    @enderror
</label>