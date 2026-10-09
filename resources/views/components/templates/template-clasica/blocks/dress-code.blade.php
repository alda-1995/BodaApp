@props(['section', 'context'])

@php
    $foto = $section->get('reference_image');
    $tipo = $section->get('type');
    $tema = $section->get('color_or_theme');

    // El diseño las escribe seguidas, una bajo otra, dentro de la misma etiqueta.
    $indicaciones = array_filter([$section->get('men_attire'), $section->get('women_attire')]);
@endphp

{{--
    Código de vestimenta (DressCodeSection).

    Una etiqueta colgante centrada sobre el panel con textura, y todo el texto
    escrito DENTRO de ella: el rótulo, el tipo de vestimenta en grande y las
    indicaciones en cursiva.
--}}
<section class="tc-dress" id="vestimenta"
    style="--tc-textura: url('{{ asset('images/assets-clasica/banda-1.jpg') }}')">

    <div class="tc-dress__etiqueta">
        @if ($foto)
            <img class="tc-dress__foto" src="{{ $foto }}" alt="" loading="lazy">
        @endif

        <div class="tc-dress__texto">
            <p class="tc-dress__rotulo">Código de vestimenta</p>

            @if ($tipo)
                <h2 class="tc-dress__tipo">{{ $tipo }}</h2>
            @endif

            @foreach ($indicaciones as $indicacion)
                <p class="tc-dress__detalle">{{ $indicacion }}</p>
            @endforeach

            @if ($tema)
                <p class="tc-dress__tema">{{ $tema }}</p>
            @endif
        </div>
    </div>
</section>
