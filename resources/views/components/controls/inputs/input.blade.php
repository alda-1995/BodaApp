@props([
    'label' => '',
    'name' => '',
    'id' => null,
    'value' => '',
    'type' => 'text',
    'required' => false,
    'autofocus' => false,
    'disabled' => false,
    'placeholder' => '',
    'variant' => 'primary',
    'autocomplete' => 'on',
    'dotName' => '',
])

@php
    $id = $id ?? $name;
    // Dentro de un repeater el name viaja como 'faqs[0][question]', pero old() y
    // @error se consultan con 'faqs.0.question'.
    $errorKey = $dotName ?: $name;
    $hasError = filled($errorKey) ? $errors->has($errorKey) : false;
@endphp

<div class="flex flex-col gap-1.5 w-full">
    @if(filled($label))
        <label @if($id) for="{{ $id }}" @endif class="font-inter text-size-small-heading text-black flex items-center justify-between">
            <span>
                {{ $label }}
                @if($required) <span class="text-red-500">*</span> @endif
            </span>
        </label>
    @endif

    <x-controls.inputs.native
        :type="$type"
        :id="$id"
        :name="$name"
        :value="filled($errorKey) ? old($errorKey, $value) : $value"
        :placeholder="$placeholder"
        :autocomplete="$autocomplete"
        :required="$required"
        :autofocus="$autofocus"
        :disabled="$disabled"
        :variant="$variant"
        :has-error="$hasError"
        {{ $attributes }}
    />

    @if(filled($errorKey))
        @error($errorKey)
            <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
        @enderror
    @endif
</div>