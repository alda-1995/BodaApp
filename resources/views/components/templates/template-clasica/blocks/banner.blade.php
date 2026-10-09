@props(['section', 'context'])

@php
    $linea = $section->get('invitation_line');
    $portada = $section->get('cover_photo');
    $banda = $section->get('band_photo');
@endphp

{{--
    Portada (GeneralSection).

    Tres capas, como en el diseño:

      1. un panel de fondo con la textura de la plantilla, más angosto que la
         página, que corre por detrás de todo;
      2. la tarjeta de la portada centrada encima, con la invitación, los
         nombres y la fecha escritos DENTRO de ella;
      3. la foto ancha, que sí va de borde a borde y se monta sobre el panel.

    La fecha se escribe larga y en versales, con el mismo formato que usa el
    pie: sale de TemplateContext y no de aquí, para que las dos no puedan verse
    distintas.
--}}
<section class="tc-banner" id="portada"
    style="--tc-textura: url('{{ asset('images/assets-clasica/banda-1.jpg') }}')">
    <div class="tc-banner__panel">
        <div class="tc-banner__tarjeta">
            @if ($portada)
                <img class="tc-banner__photo" src="{{ $portada }}" alt="" loading="eager">
            @endif

            <div class="tc-banner__texto">
                @if ($linea)
                    <p class="tc-banner__linea">{{ $linea }}</p>
                @endif

                <h1 class="tc-banner__nombres">{{ $context->coupleNames(' & ') }}</h1>

                @if ($context->formattedDateLong())
                    <p class="tc-banner__fecha">{{ $context->formattedDateLong() }}</p>
                @endif
            </div>
        </div>

        @if ($banda)
            <img class="tc-banner__banda" src="{{ $banda }}" alt="" loading="lazy">
        @endif
    </div>
</section>
