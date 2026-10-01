@props(['section', 'context'])

@php
    $title = $section->get('title');
    $intro = $section->get('intro');
    $illustration = $section->get('illustration');
    $milestones = $section->get('milestones', []);
@endphp

{{--
    Nuestra historia (MilestonesSection).

    Título, la banda ilustrada de la plantilla y, debajo, la línea de años con
    la foto de cada uno.

    En escritorio la foto aparece al pasar por su año: la esconde el CSS y
    story.js sólo marca cuál está activo. En móvil no hay "pasar por encima",
    así que ahí las fotos se ven siempre, una debajo de su año.

    Las fotos están todas en el HTML: sin JS la sección se lee igual.
--}}
<section class="te-story" id="historia">
    <div class="te-container">
        @if ($title)
            <h2 class="te-story__title">{{ $title }}</h2>
        @endif
    </div>

    {{-- La ilustración es de la plantilla, no de la boda (story.manifest.php). --}}
    @if ($illustration)
        <div class="te-story__band">
            <img src="{{ $illustration }}" alt="" loading="lazy">
        </div>
    @endif

    <div class="te-container">
        @if ($intro)
            <p class="te-story__intro">{{ $intro }}</p>
        @endif

        @if ($milestones)
            <ol class="te-story__years" data-te-story-years>
                @foreach ($milestones as $milestone)
                    <li class="te-story__year">
                        {{--
                            La foto se ancla a ESTE envoltorio, no a la celda:
                            así queda centrada en el año se llame 1998 o 2026,
                            sin depender de cuánto mida el texto ni la columna.
                        --}}
                        <span class="te-story__year-anchor">
                            <span class="te-story__year-number">{{ $milestone['year'] }}</span>

                            @if ($milestone['image'])
                                <figure class="te-story__year-photo">
                                    <img src="{{ $milestone['image'] }}" alt="{{ $milestone['year'] }}" loading="lazy">
                                </figure>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</section>
