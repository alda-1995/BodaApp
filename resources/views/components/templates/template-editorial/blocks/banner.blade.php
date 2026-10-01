@props(['section', 'context'])

@php
    // "SÁBADO · 21 DE MARZO · 2026", como en el diseño. El pie la escribe igual.
    $dateLabel = $context->formattedDateLong();
@endphp

<section class="te-banner" id="portada">
    <div class="te-container te-banner__inner">
        {{-- La fecha corre en vertical por los costados; en móvil no cabe y se muestra abajo. --}}
        @if ($dateLabel)
            <p class="te-banner__rail te-banner__rail--left">{{ $dateLabel }}</p>
            <p class="te-banner__rail te-banner__rail--right">{{ $dateLabel }}</p>
        @endif

        <h1 class="te-banner__names">
            {{ $section->get('wife_name') }} y {{ $section->get('husband_name') }}
        </h1>

        {{--
            La foto la sube el organizador; el monograma lo pone el superadmin.
            Ambos salen del manifiesto de esta portada (banner.manifest.php), que
            también trae el valor por defecto cuando nadie ha subido nada.
        --}}
        <div class="te-banner__photo">
            @if ($section->get('cover_photo'))
                <img class="te-banner__image" src="{{ $section->get('cover_photo') }}"
                    alt="{{ $section->get('wife_name') }} y {{ $section->get('husband_name') }}">
            @endif

            @if ($section->get('monogram'))
                <img class="te-banner__monogram" src="{{ $section->get('monogram') }}" alt="">
            @endif
        </div>

        @if ($dateLabel)
            <p class="te-banner__date">{{ $dateLabel }}</p>
        @endif
    </div>
</section>
