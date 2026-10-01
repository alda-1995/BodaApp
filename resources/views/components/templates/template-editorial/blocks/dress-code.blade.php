@props(['section', 'context'])

@php
    $tipo = $section->get('type');
    $caballeros = $section->get('men_attire');
    $damas = $section->get('women_attire');
    $reservado = $section->get('reserved_note');
    $colores = $section->get('color_or_theme');
    $foto = $section->get('reference_image');
    $paleta = $section->get('palette', []);
@endphp

{{--
    Código de vestimenta (DressCodeSection).

    Dos mitades: la foto ocupa la izquierda de borde a borde y las indicaciones
    la derecha. La foto va sola —el letrero de la pareja viene dentro de ella,
    lo compone el equipo de diseño—, así que aquí no se le encima nada.
--}}
<section class="te-dress" id="vestimenta">
    <div class="te-dress__columns">
        @if ($foto)
            <div class="te-dress__photo">
                <img src="{{ $foto }}" alt="Referencia de vestimenta" loading="lazy">
            </div>
        @endif

        <div class="te-dress__body">
            <p class="te-dress__kicker">Código de vestimenta</p>

            @if ($tipo)
                <h2 class="te-dress__type">{{ $tipo }}</h2>
            @endif

            <div class="te-dress__notes">
                @if ($caballeros)
                    <p>Caballeros: {{ $caballeros }}</p>
                @endif

                @if ($damas)
                    <p>Damas: {{ $damas }}</p>
                @endif
            </div>

            @if ($reservado)
                <p class="te-dress__warning">{{ $reservado }}</p>
            @endif

            @if ($colores)
                <p class="te-dress__palette-text">Colores sugeridos: {{ $colores }}</p>
            @endif

            @if ($paleta)
                <ul class="te-dress__swatches">
                    @foreach ($paleta as $color)
                        @continue(empty($color['color']))

                        <li class="te-dress__swatch">
                            <span class="te-dress__swatch-dot" style="background: {{ $color['color'] }}"></span>

                            @if (!empty($color['name']))
                                <span class="te-dress__swatch-name">{{ $color['name'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>
