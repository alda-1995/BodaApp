{{--
    Plantilla "Boda Clásica".

    Recibe $context (pareja, fecha, invitado, paleta), $sections (ya resueltas
    por TemplateRenderer) y $assets (su CSS y JS).

    Esta lista no enumera nada: las secciones vienen en el orden que declara
    App\Strategies\ClassicWeddingStrategy, que es el mismo orden del wizard.
    Para reordenar el diseño se mueve una línea de allá.
--}}
<x-layouts.template-layout :context="$context" :assets="$assets" :fonts="$fonts" :body-class="$bodyClass">
    @foreach ($sections as $section)
        <x-dynamic-component :component="$section->component" :section="$section" :context="$context" />
    @endforeach

    {{-- El pie repite los nombres y la fecha, así que recibe la sección general. --}}
    <x-templates.template-clasica.footer :context="$context" :section="$sections['general'] ?? null" />
</x-layouts.template-layout>
