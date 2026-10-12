@props(['section', 'context'])

@php
    $fotos = $section->get('images', []);
    $texto = $section->get('intro');

    // Los adornos del moodboard son del diseño, no algo que el organizador suba.
    $adornos = ['adorno-1', 'adorno-2', 'adorno-3', 'adorno-4', 'adorno-5'];
@endphp

{{--
    Galería (GallerySection).

    Un moodboard: las fotos caen sobre una textura de papel, ligeramente giradas
    y como pegadas a mano. Entre ellas se asoman los adornos del diseño —el
    sello, la grapa, el alfiler, la flor seca—, que no se preguntan en ningún
    paso porque son de la plantilla.

    Las fotos van en columnas y no en posiciones fijas: el diseño enseña cinco,
    pero aquí pueden subirse las que sean y el tablero se acomoda solo.

    El párrafo cierra sobre una nota de papel rasgado. No lleva título: en el
    diseño cuelga de las fotos, no es una sección aparte.

    La nota viaja en tres piezas —el sello y el borde de arriba, el papel liso
    del centro y el borde de abajo— para que crezca con el texto sin deformar
    ni el sello ni los rasgados. El CSS las monta; aquí sólo se nombran.
--}}
<section class="tc-gallery" id="galeria"
    style="--tc-papel: url('{{ asset('images/assets-clasica/textura-galeria.jpg') }}');
           --tc-nota-arriba: url('{{ asset('images/assets-clasica/nota-arriba.png') }}');
           --tc-nota-medio: url('{{ asset('images/assets-clasica/nota-medio.png') }}');
           --tc-nota-abajo: url('{{ asset('images/assets-clasica/nota-abajo.png') }}')">

    <div class="tc-gallery__tablero">
        @if ($fotos)
            <div class="tc-gallery__collage" data-visor>
                @foreach ($fotos as $i => $foto)
                    <a class="tc-gallery__enlace" href="{{ $foto }}" target="_blank" rel="noopener">
                        <img class="tc-gallery__foto" src="{{ $foto }}" alt="Foto {{ $i + 1 }}" loading="lazy">
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Decorativos: no se leen ni se pueden tocar. --}}
        <div class="tc-gallery__adornos" aria-hidden="true">
            @foreach ($adornos as $n => $adorno)
                <img class="tc-gallery__adorno tc-gallery__adorno--{{ $n + 1 }}"
                    src="{{ asset('images/assets-clasica/' . $adorno . '.png') }}" alt="" loading="lazy">
            @endforeach
        </div>

        @if ($texto)
            <div class="tc-gallery__nota">
                <p class="tc-gallery__texto">{{ $texto }}</p>
            </div>
        @endif
    </div>
</section>
