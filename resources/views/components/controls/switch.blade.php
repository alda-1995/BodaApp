@props([
    'name',
    'label' => 'Toggle',
    'initialState' => false,
])

@php
    $checked = filter_var(old($name, $initialState), FILTER_VALIDATE_BOOLEAN);
@endphp

<div x-data="{
        isOn: @json($checked),

        toggle() {
            this.isOn = !this.isOn;
            this.$dispatch('toggled-{{ $name }}', { state: this.isOn, name: '{{ $name }}' });
        }
    }"
    {{ $attributes->merge(['class' => 'flex items-center space-x-2']) }}
>
    <label for="{{ $name }}" class="font-inter text-size-small-heading text-black cursor-pointer">
        {{ $label }}
    </label>

    <button type="button"
        id="{{ $name }}"
        @click="toggle()"
        :aria-checked="isOn"
        :class="isOn ? 'bg-base-blue' : 'bg-gray-200'"
        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
        role="switch"
    >
        <span class="sr-only">{{ $label }}</span>
        <span aria-hidden="true"
            :class="isOn ? 'translate-x-5' : 'translate-x-0'"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
        ></span>
    </button>

    <input type="hidden" name="{{ $name }}" :value="isOn ? 1 : 0">
</div>

@error($name)
    <span class="error text-red-500 text-size-small-heading font-inter mb-4 block">{{ $message }}</span>
@enderror