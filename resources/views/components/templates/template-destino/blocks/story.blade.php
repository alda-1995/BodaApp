@props(['section', 'context'])

@php
    $anios = $section->get('milestones', []);
@endphp

{{--
    Nuestra historia (MilestonesSection).

    Las fotos caen en abanico, cada una con su año encima. El giro de cada
    polaroid lo pone el CSS a partir del índice, no el organizador: así la
    composición se mantiene tanto si suben tres años como si suben siete.
--}}
@if ($anios)
    <section class="td-story" id="historia">
        <div class="td-container">
            <p class="td-story__title">{{ $section->get('title') }}</p>
        </div>

        <ul class="td-story__fan">
            @foreach ($anios as $indice => $anio)
                <li class="td-story__card" style="--td-card-index: {{ $indice }}">
                    <span class="td-story__year">{{ $anio['year'] }}</span>

                    @if (!empty($anio['image']))
                        <img class="td-story__photo" src="{{ $anio['image'] }}" alt="{{ $anio['year'] }}" loading="lazy">
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
