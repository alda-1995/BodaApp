<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Invitación no disponible</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montaga&family=Onest:wght@100..900&display=swap"
        rel="stylesheet">
    @vite(['resources/css/templates/shared/template.css'])
</head>

<body class="inv-body">
    <main class="inv-unavailable">
        <div class="inv-container inv-narrow">
            <h1 class="inv-title">Esta invitación ya no está disponible</h1>
            <p class="inv-text">
                {{-- Sólo si de verdad venció: una apagada a mano tiene su fecha por delante. --}}
                @if ($event->isExpired())
                    Estuvo activa hasta el {{ $event->expires_at->format('d/m/Y') }}.
                @endif
                Si necesitas información del evento, comunícate con los novios.
            </p>
        </div>
    </main>
</body>

</html>
