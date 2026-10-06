@props(['compact' => false])

{{--
    La invitación todavía no tiene dirección.

    Sin ella no existe para nadie: la página pública sólo se encuentra por su
    custom_url, así que no hay liga que mandar ni "Ver mi invitación" que
    ofrecer. No se genera sola a propósito —la dirección es de la pareja y la
    van a repartir por WhatsApp— así que aquí se invita a elegirla.

    Se usa en el wizard y en el dashboard. En $compact va en línea, donde
    estaría el botón; si no, como aviso de ancho completo.
--}}
@php
    $titulo = 'Tu invitación todavía no tiene dirección';
    $texto = 'Elígela en Configuración para poder verla y compartirla con tus invitados.';
@endphp

@if ($compact)
    <div class="flex flex-col items-start gap-1">
        <x-controls.button href="{{ route('organizer.settings.index') }}" variant="secondary">
            Elegir la dirección de mi invitación
        </x-controls.button>
        <p class="font-inter text-size-small-heading text-[#8C8C8C] max-w-[280px]">
            Sin ella no se puede ver ni compartir.
        </p>
    </div>
@else
    {{-- El amarillo de la marca: es lo que hay que resolver, no un dato más. --}}
    <div class="w-full rounded-xl bg-base-yellow p-4 md:p-5">
        <p class="font-inter text-size-heading">{{ $titulo }}</p>
        <p class="font-inter text-parrafo text-[#595959] mt-1">{{ $texto }}</p>

        <a href="{{ route('organizer.settings.index') }}"
            class="font-inter text-parrafo text-[#3952F6] underline underline-offset-4 mt-3 inline-block">
            Ir a Configuración
        </a>
    </div>
@endif
