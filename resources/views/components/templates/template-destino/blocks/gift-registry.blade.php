@props(['section', 'context'])

@php
    $opciones = $section->get('options', []);

    /*
    | Este diseño pinta todo en tarjetas iguales, así que no hace falta nombrar
    | tipos: lo que decide la forma es si la opción trae una liga.
    |
    |   con liga  → es una tienda: nombre grande, detalle y "Ir a … →"
    |   sin liga  → es una cuenta: el tipo manda, y el primer dato va arriba
    |               a la derecha (el banco)
    |
    | El tipo de cada campo lo declaró el superadmin en "Tipos de mesa de
    | regalo", así que una liga se reconoce aunque esté a medio escribir.
    */
    $esLiga = fn (array $detalle) => ($detalle['type'] ?? 'text') === 'url';

    // Una clave larga se copia; un texto corto sólo se lee.
    $esClave = fn (array $detalle) => (bool) preg_match(
        '/\d{6,}/',
        (string) preg_replace('/[\s-]+/', '', $detalle['value'])
    );

    $tarjetas = [];

    foreach ($opciones as $opcion) {
        $liga = collect($opcion['details'])->first($esLiga);
        $otros = collect($opcion['details'])->reject($esLiga)->values();

        $tarjetas[] = $liga
            ? [
                'titulo' => $otros->first()['value'] ?? $opcion['type'],
                'esquina' => null,
                'lineas' => $otros->skip(1)->all(),
                'liga' => $liga,
            ]
            : [
                'titulo' => $opcion['type'],
                // El banco, la tienda, la plataforma: el primer dato del tipo.
                'esquina' => $otros->first()['value'] ?? null,
                'lineas' => $otros->skip(1)->all(),
                'liga' => null,
            ];
    }
@endphp

{{--
    Mesa de regalos (GiftRegistrySection).

    Tarjetas blancas sobre el verde agua, escalonadas: la primera arriba, la
    siguiente un poco más abajo, y así. El escalón lo pone el CSS con el índice.
--}}
@if ($tarjetas)
    <section class="td-gifts" id="mesa-de-regalos">
        <div class="td-container td-center">
            <h2 class="td-gifts__title">{{ $section->get('title') }}</h2>

            @if ($section->filled('message'))
                <p class="td-gifts__message">{{ $section->get('message') }}</p>
            @endif
        </div>

        <ul class="td-gifts__cards td-container">
            @foreach ($tarjetas as $tarjeta)
                <li class="td-gifts__card" style="--td-card-index: {{ $loop->index }}">
                    <p class="td-gifts__card-head">
                        <span class="td-gifts__card-title">{{ $tarjeta['titulo'] }}</span>

                        @if ($tarjeta['esquina'])
                            <span class="td-gifts__card-tag">{{ $tarjeta['esquina'] }}</span>
                        @endif
                    </p>

                    @foreach ($tarjeta['lineas'] as $linea)
                        <p class="td-gifts__card-line">
                            @if ($esClave($linea))
                                <x-templates.template-destino.copy :value="$linea['value']" :label="$linea['label']" />
                            @else
                                {{ $linea['value'] }}
                            @endif
                        </p>
                    @endforeach

                    @if ($tarjeta['liga'])
                        <a class="td-gifts__card-link" href="{{ $tarjeta['liga']['value'] }}"
                            target="_blank" rel="noopener">
                            Ir a {{ $tarjeta['titulo'] }} <span aria-hidden="true">→</span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
