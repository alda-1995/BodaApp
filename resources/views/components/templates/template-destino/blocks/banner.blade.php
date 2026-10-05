@props(['section', 'context'])

@php
    $nombres = trim($section->get('wife_name') . '&' . $section->get('husband_name'), '&');
    // "24 — 10 — 2026": la fecha como un boleto, no como una frase.
    $fecha = $context->formattedDateNumeric();
@endphp

{{--
    Portada (GeneralSection).

    La foto ocupa la pantalla completa y todo lo demás va encima, centrado: el
    monograma, los nombres pegados por el "&" y la fecha. Abajo del todo, la
    línea que invita a bajar.
--}}
<section class="td-banner" id="portada">
    @if ($section->get('cover_photo'))
        <img class="td-banner__photo" src="{{ $section->get('cover_photo') }}" alt="{{ $nombres }}">
    @endif

    <div class="td-banner__overlay"></div>

    <div class="td-banner__inner">
        @if ($section->get('monogram'))
            <img class="td-banner__monogram" src="{{ $section->get('monogram') }}" alt="">
        @endif

        <h1 class="td-banner__names">
            {{ $section->get('wife_name') }}<span class="td-banner__amp">&amp;</span>{{ $section->get('husband_name') }}
        </h1>

        @if ($fecha)
            <p class="td-banner__date">{{ $fecha }}</p>
        @endif
    </div>

    @if ($section->filled('scroll_hint'))
        <p class="td-banner__hint">{{ $section->get('scroll_hint') }}</p>
    @endif
</section>
