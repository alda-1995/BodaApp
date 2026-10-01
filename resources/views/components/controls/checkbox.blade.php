@props([
    'id' => 'checkbox',
    'name' => '',
    'label' => '',
    'checked' => false
])

<label for="{{ $id }}" class="inline-flex items-center">
    <input
        id="{{ $id }}"
        type="checkbox"
        name="{{ $name }}"
        @checked($checked)
        {{ $attributes->merge(['class' => 'h-5 w-5 rounded border-[#dde1e3] border-2 bg-transparent focus:ring-0']) }}
    >
    <span class="ms-2 text-size-small-heading text-complement500">
        {{ $label }}
    </span>
</label>