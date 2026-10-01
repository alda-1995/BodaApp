@props([
    'icon' => '', // Ej: 'calendar', 'outlook', 'download'
    'label' => '', // Texto que se muestra
    'type' => 'button',
    'title' => '',
    'description' => '',
    'location' => '',
    'startDate' => '',
    'typeCalendar' => ''
])

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'btn-calendar inline-flex items-center cursor-pointer gap-x-4 px-2 py-2 rounded-md transition-all duration-300 hover:shadow-lg']) }}
    data-title="{{ $title }}"
    data-description="{{ $description }}"
    data-location="{{ $location }}"
    data-start-date="{{ $startDate }}"
    data-type-calendar="{{ $typeCalendar }}"
>
    <x-dynamic-component :component="'icons.' . $icon" />
    <span class="text-size-small-heading font-secondary text-main">{{ $label }}</span>
</button>