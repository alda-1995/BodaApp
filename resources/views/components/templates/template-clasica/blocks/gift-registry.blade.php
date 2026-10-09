@props(['section', 'context'])

@php
    $titulo = $section->get('title');
    $mensaje = $section->get('message');
    $opciones = $section->get('options', []);
@endphp

{{--
    Mesa de regalos (GiftRegistrySection).

    El título en cursiva sobre el crema y, debajo, las opciones en tarjetas
    enmarcadas de dos en dos: el nombre y una línea de detalle, centrados dentro
    del marco.

    Cada opción trae su tipo y sus datos ya resueltos por la sección. Si entre
    sus datos hay una liga, la tarjeta entera lleva a la tienda; si no —una
    transferencia, por ejemplo— es una tarjeta que sólo se lee.
--}}
<section class="tc-gifts" id="mesa-de-regalos"
    style="--tc-marco-tarjeta: url('{{ asset('images/assets-clasica/tarjeta-regalo.png') }}')">

    @if ($titulo)
        <h2 class="tc-gifts__titulo">{{ $titulo }}</h2>
    @endif

    @if ($mensaje)
        <p class="tc-gifts__mensaje">{{ $mensaje }}</p>
    @endif

    @if ($opciones)
        <ul class="tc-gifts__lista">
            @foreach ($opciones as $opcion)
                @php
                    /*
                    | Cada dato llega como ['label' => …, 'value' => …]. Si
                    | alguno es una liga, la tarjeta entera lleva a la tienda y
                    | ese dato ya no se escribe: el nombre hace de enlace.
                    */
                    $datos = [];
                    $liga = null;

                    foreach ($opcion['details'] ?? [] as $dato) {
                        $valor = is_array($dato) ? ($dato['value'] ?? '') : $dato;

                        if (blank($valor)) {
                            continue;
                        }

                        if (!$liga && filter_var($valor, FILTER_VALIDATE_URL)) {
                            $liga = $valor;

                            continue;
                        }

                        $etiqueta = is_array($dato) ? ($dato['label'] ?? '') : '';
                        $datos[] = filled($etiqueta) ? "{$etiqueta}: {$valor}" : $valor;
                    }
                @endphp

                <li class="tc-gifts__opcion">
                    <{{ $liga ? 'a' : 'div' }} class="tc-gifts__tarjeta"
                        @if ($liga) href="{{ $liga }}" target="_blank" rel="noopener" @endif>
                        <p class="tc-gifts__tienda">{{ $opcion['type'] ?? '' }}</p>

                        @foreach ($datos as $dato)
                            <p class="tc-gifts__dato">{{ $dato }}</p>
                        @endforeach
                    </{{ $liga ? 'a' : 'div' }}>
                </li>
            @endforeach
        </ul>
    @endif
</section>
