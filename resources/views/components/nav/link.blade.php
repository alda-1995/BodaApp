@props([
    'route'  => null,
    'params' => [],
    'href'   => null,
    'icon'   => null,
    // Otras rutas que también marcan el enlace como activo (ej. 'events.wizard.*').
    'activeRoutes' => [],
])

@php
    // Aseguramos que params sea un arreglo si llega nulo o un solo elemento
    $params = is_array($params) ? $params : [$params];

    // Construir la URL final con sus parámetros correspondientes
    $url = $href ?? ($route ? route($route, $params) : '#');

    // Determinar si la ruta está activa (comprobando el nombre de la ruta)
    $active = $route
        ? request()->routeIs($route, ...(array) $activeRoutes)
        : request()->url() === $href;

    $activeStyles = $active ? 'active' : '';
@endphp

<a href="{{ $url }}"
   {{ $attributes->merge(['class' => "item-menu-nav $activeStyles"]) }}>
    @if ($icon)
        <x-dynamic-component :component="'icons.' . $icon" />
    @endif
    <span class="text-current">
        {{ $slot }}
    </span>
</a>