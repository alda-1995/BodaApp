{{--
    Plantilla "Boda Barco".

    Recibe $context (pareja, fecha, invitado, paleta), $sections (ya resueltas
    por TemplateRenderer) y $assets (su CSS y JS). Cada bloque sale de
    components/templates/template-travel/blocks/{tipo}.blade.php; si la plantilla
    no tuviera alguno, se usa el compartido.
--}}
<x-layouts.template-layout :context="$context" :assets="$assets" :fonts="$fonts" body-class="tv">
    @foreach (['general', 'history', 'itinerary', 'gift_registry', 'dress_code', 'rsvp'] as $key)
        @isset($sections[$key])
            <x-dynamic-component :component="$sections[$key]->component" :section="$sections[$key]" :context="$context" />
        @endisset
    @endforeach

    <div class="tv-section--second">
        @foreach (['gallery', 'faqs'] as $key)
            @isset($sections[$key])
                <x-dynamic-component :component="$sections[$key]->component" :section="$sections[$key]" :context="$context" />
            @endisset
        @endforeach

        <x-templates.template-travel.footer />
    </div>
</x-layouts.template-layout>
