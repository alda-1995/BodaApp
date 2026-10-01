@props([
    /** Nombre del error bag (“default” para el principal) */
    'bag'   => 'default',

    /** Texto que encabeza la alerta */
    'title' => 'Oops! Hubo algunos errores:',
])

@php
    // Selecciona el ErrorBag adecuado
    $errorBag = $bag === 'default' ? $errors : $errors->{$bag};
@endphp

@if ($errorBag->any())
    <div {{ $attributes->merge([
            'class' => 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'
        ]) }}>
        {{-- <strong>{{ $title }}</strong> --}}

        <ul class="mt-2 list-disc list-inside">
            @foreach ($errorBag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif