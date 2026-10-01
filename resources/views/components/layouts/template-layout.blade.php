@props([
    'context' => null,
    // CSS y JS propios de la plantilla (los resuelve TemplateRenderer).
    'assets' => [],
    // Hojas de fuentes que declara la plantilla en su estrategia.
    'fonts' => [],
    'bodyClass' => '',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $context?->coupleNames(' y ') ?: config('app.name', 'Tamira') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" />

    {{-- Tipografías: cada plantilla declara las suyas. --}}
    @if (filled($fonts))
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        @foreach ($fonts as $font)
            <link rel="stylesheet" href="{{ $font }}">
        @endforeach
    @endif

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/css/intlTelInput.css">

    {{-- Sólo el CSS de la invitación: estas páginas no usan el del panel. --}}
    @vite(array_merge(['resources/css/templates/shared/template.css'], $assets))

    @if ($context?->cssVariables())
        {{-- Paleta que eligió la pareja en Configuración. --}}
        <style>
            :root { {!! $context->cssVariables() !!} }
        </style>
    @endif
</head>

<body class="inv-body {{ $bodyClass }}">
    <main>
        {{ $slot }}
    </main>

    {{-- Sólo el JS de la plantilla: las invitaciones no usan el del panel. --}}

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/split-type"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/js/intlTelInput.min.js"></script>

    @stack('animations')
</body>

</html>
