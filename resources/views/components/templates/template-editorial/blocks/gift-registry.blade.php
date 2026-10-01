@props(['section', 'context'])

@php
    $title = $section->get('title');
    $message = $section->get('message');
    $options = $section->get('options', []);

    /*
    | QUÉ VA DE CADA LADO lo fija esta plantilla, no el dato.
    |
    | Su diseño tiene dos formas: la cuenta bancaria va en la tarjeta de la
    | derecha y todo lo demás —tiendas, mesas, listas— en la lista de la
    | izquierda. Los tipos los nombra el superadmin en "Tipos de mesa de
    | regalo", así que aquí se nombran los que esta plantilla trata como cuenta.
    */
    $tiposEnTarjeta = ['cuenta_bancaria'];

    /*
    | COPIAR O ENLAZAR también lo decide esta plantilla, con su diseño en la
    | mano. Ya no hay bandera en el catálogo del superadmin:
    |
    |   una liga        → se enlaza, con su "Ver"
    |   una clave larga → se copia (CLABE, cuenta, número de mesa)
    |   lo demás        → texto
    |
    | El tipo del campo es el que declaró el superadmin, así que una liga se
    | reconoce aunque todavía esté vacía o a medio escribir.
    */
    $esLiga = fn (array $detalle) => ($detalle['type'] ?? 'text') === 'url';

    $esClave = fn (array $detalle) => (bool) preg_match(
        '/\d{6,}/',
        (string) preg_replace('/[\s-]+/', '', $detalle['value'])
    );

    $tiendas = [];
    $cuentas = [];

    foreach ($options as $option) {
        if (in_array($option['type_value'] ?? '', $tiposEnTarjeta, true)) {
            $cuentas[] = $option;

            continue;
        }

        $liga = collect($option['details'])->first($esLiga);
        $otros = collect($option['details'])->reject($esLiga)->values();

        $tiendas[] = [
            // El nombre grande es el primer dato que no es liga.
            'nombre' => $otros->first()['value'] ?? $option['type'],
            'detalle' => $otros->skip(1)->pluck('value')->implode(' · '),
            'liga' => $liga,
        ];
    }
@endphp

{{--
    Mesa de regalos (GiftRegistrySection).

    A la izquierda las tiendas, cada una con su ilustración; a la derecha las
    cuentas, en una tarjeta. Las ilustraciones son de la plantilla y las pone el
    superadmin (gift-registry.manifest.php); se alternan para que dos tiendas
    seguidas no se vean iguales.
--}}
@if ($options)
    <section class="te-gifts" id="mesa-de-regalos">
        <div class="te-container">
            @if ($title)
                <h2 class="te-gifts__title">{{ mb_strtoupper($title) }}</h2>
            @endif

            @if ($message)
                <p class="te-gifts__message">{{ $message }}</p>
            @endif

            {{--
                Las dos columnas sólo tienen sentido si hay de las dos cosas.
                Con puras tiendas o puras cuentas —que es lo normal si el
                superadmin configuró un solo tipo— se centra la que haya.
            --}}
            <div @class(['te-gifts__columns', 'te-gifts__columns--single' => !$tiendas || !$cuentas])>
                @if ($tiendas)
                    <ul class="te-gifts__stores">
                        @foreach ($tiendas as $tienda)
                            @php
                                // Las ilustraciones se turnan: la primera, la segunda, la primera...
                                $ilustracion = $loop->index % 2 === 0
                                    ? $section->get('store_icon_a')
                                    : $section->get('store_icon_b');
                            @endphp

                            <li class="te-gifts__store">
                                @if ($ilustracion)
                                    <img class="te-gifts__store-icon" src="{{ $ilustracion }}" alt="" loading="lazy">
                                @endif

                                <div class="te-gifts__store-body">
                                    <p class="te-gifts__store-head">
                                        <span class="te-gifts__store-name">{{ $tienda['nombre'] }}</span>

                                        @if ($tienda['liga'])
                                            <a class="te-gifts__store-link" href="{{ $tienda['liga']['value'] }}"
                                                target="_blank" rel="noopener">
                                                Ver <span aria-hidden="true">→</span>
                                            </a>
                                        @endif
                                    </p>

                                    @if ($tienda['detalle'])
                                        <p class="te-gifts__store-detail">{{ $tienda['detalle'] }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($cuentas)
                    <div class="te-gifts__accounts">
                        @foreach ($cuentas as $cuenta)
                            <article class="te-gifts__card">
                                @if ($cuenta['type'])
                                    <h3 class="te-gifts__card-title">{{ $cuenta['type'] }}</h3>
                                @endif

                                <ul class="te-gifts__card-data">
                                    @foreach ($cuenta['details'] as $detalle)
                                        <li class="te-gifts__card-row">
                                            @if ($esLiga($detalle))
                                                <a class="te-gifts__card-link" href="{{ $detalle['value'] }}"
                                                    target="_blank" rel="noopener">{{ $detalle['value'] }}</a>
                                            @elseif ($esClave($detalle))
                                                <x-templates.template-editorial.copy :value="$detalle['value']"
                                                    :label="$detalle['label']" />
                                            @else
                                                <span class="te-gifts__card-value">{{ $detalle['value'] }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
