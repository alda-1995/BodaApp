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
])

@php
    $id = $id ?? $name;
    $hasError = filled($name) ? $errors->has($name) : false;
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
        :value="filled($name) ? old($name, $value) : $value"
        :placeholder="$placeholder"
        :autocomplete="$autocomplete"
        :required="$required"
        :autofocus="$autofocus"
        :disabled="$disabled"
        :variant="$variant"
        :has-error="$hasError"
        {{ $attributes }}
    />

    @if(filled($name))
        @error($name)
            <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
        @enderror
    @endif
</div>