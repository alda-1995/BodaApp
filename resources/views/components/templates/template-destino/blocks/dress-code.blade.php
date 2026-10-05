@props(['section', 'context'])

@php
    $paleta = $section->get('palette', []);
@endphp

{{--
    Código de vestimenta (DressCodeSection).

    Izquierda: el rótulo y la foto de referencia. Derecha: el tipo en grande,
    las dos indicaciones en tarjetas y, abajo, las muestras de color con su
    nombre y la nota reservada.
--}}
<section class="td-dress" id="vestimenta">
    <div class="td-container td-dress__columns">
        <div class="td-dress__aside">
            <p class="td-dress__kicker">Código de vestimenta</p>

            @if ($section->get('reference_image'))
                <img class="td-dress__photo" src="{{ $section->get('reference_image') }}"
                    alt="Referencia de vestimenta" loading="lazy">
            @endif
        </div>

        <div class="td-dress__body">
            @if ($section->filled('type'))
                <h2 class="td-dress__type">{{ $section->get('type') }}</h2>
            @endif

            <div class="td-dress__notes">
                @if ($section->filled('men_attire'))
                    <div class="td-dress__note">
                        <p class="td-dress__note-title">Caballeros</p>
                        <p class="td-dress__note-text">{{ $section->get('men_attire') }}</p>
                    </div>
                @endif

                @if ($section->filled('women_attire'))
                    <div class="td-dress__note">
                        <p class="td-dress__note-title">Damas</p>
                        <p class="td-dress__note-text">{{ $section->get('women_attire') }}</p>
                    </div>
                @endif
            </div>

            @if ($section->filled('color_or_theme'))
                <p class="td-dress__colors">Colores sugeridos: {{ $section->get('color_or_theme') }}</p>
            @endif

            @if ($paleta)
                <ul class="td-dress__swatches">
                    @foreach ($paleta as $color)
                        @continue(empty($color['color']))

                        <li class="td-dress__swatch">
                            <span class="td-dress__chip" style="background: {{ $color['color'] }}"></span>

                            @if (!empty($color['name']))
                                <span class="td-dress__chip-name">{{ $color['name'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($section->filled('reserved_note'))
                <p class="td-dress__reserved">{{ $section->get('reserved_note') }}</p>
            @endif
        </div>
    </div>
</section>
