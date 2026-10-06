<!DOCTYPE html>
<html lang="es" class="scroll-smooth">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $title ?? config('app.name', 'Tamira') . ' · Invitaciones digitales de boda' }}</title>
    <meta name="description" content="{{ $description ?? 'Invitaciones digitales de boda con confirmación de asistencia, lista de invitados y todo lo de tu día en una sola liga.' }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" />

    {{--
        Sólo las dos familias que usa la página. Esta vista no carga el carrusel
        ni el campo de teléfono del panel: no los necesita, y es lo primero que
        ve alguien que todavía no es cliente.
    --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montaga&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    {{--
        Sin esto, lo que Alpine esconde se ve un instante antes de que arranque:
        el menú desplegado y las seis respuestas abiertas. El CSS del panel no
        lo declara, así que va aquí.
    --}}
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="overflow-x-hidden bg-white font-inter text-black antialiased">
    {{ $slot }}

    {{-- El plugin va antes que Alpine: el acordeón de preguntas usa x-collapse. --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>

</html>
