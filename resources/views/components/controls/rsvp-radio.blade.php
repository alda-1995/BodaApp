@props([
    'name' => 'rsvp',
    'label' => '',
    'selected' => null
])

<div {{ $attributes->merge(['class' => 'radio-group']) }}>
    <label class="label">{{ $label }}</label>
    <div class="flex items-center space-x-4">
        <label class="radio-control">
            <input
                type="radio"
                name="{{ $name }}"
                value="yes"
                @checked($selected === 'yes')
                class="peer hidden"
            >
            <span class="checkbox peer-checked:bg-main peer-checked:border-main transition duration-200"></span>
            <span class="text-control">Sí</span>
        </label>

        <label class="radio-control">
            <input
                type="radio"
                name="{{ $name }}"
                value="no"
                @checked($selected === 'no')
                class="peer hidden"
            >
            <span class="checkbox peer-checked:bg-main peer-checked:border-main transition duration-200"></span>
            <span class="text-control">No</span>
        </label>
    </div>
</div>