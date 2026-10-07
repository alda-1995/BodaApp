@props([
    // Invitaciones suyas que ya no sirven: vencidas o apagadas. Vacía si nunca compró.
    'past' => null,
    'hasSharedEvents' => false,
])

@php
    $past = collect($past);
@endphp

{{--
    Panel de quien no tiene una invitación en pie.

    Se ve igual haya comprado antes o no: lo que puede hacer es lo mismo, elegir
    una plantilla. Las que tuvo quedan abajo como historial, para que no parezca
    que se perdieron.
--}}
<div class="max-w-xl rounded-xl border border-[#EBEBEB] bg-white p-6 md:p-8 font-inter">
    <h3 class="mb-2 text-size-heading text-black">Aún no tienes una invitación digital activa</h3>
    <p class="mb-6 text-parrafo text-[#737373]">
        Elige una plantilla y crea la invitación de tu boda.
    </p>

    <div class="flex flex-wrap items-center gap-4">
        <x-controls.button href="{{ route('home') }}">Ver plantillas</x-controls.button>

        @if ($hasSharedEvents)
            <a href="{{ route('shared-events.index') }}" class="text-parrafo text-[#2563EB] hover:underline">
                Ver invitaciones compartidas contigo
            </a>
        @endif
    </div>
</div>

@if ($past->isNotEmpty())
    <div class="max-w-xl mt-6 rounded-xl border border-[#EBEBEB] bg-white p-6 md:p-8 font-inter">
        <h3 class="mb-1 text-size-heading text-black">Tus invitaciones anteriores</h3>
        <p class="mb-6 text-parrafo text-[#737373]">
            Ya no se pueden abrir ni editar, pero aquí queda constancia de lo que contrataste.
        </p>

        <ul class="space-y-4">
            @foreach ($past as $event)
                <li class="flex flex-wrap items-start justify-between gap-2 border-b border-[#F2F2F2] pb-4 last:border-0 last:pb-0">
                    <div>
                        <p class="text-size-small-heading text-black">
                            {{ $event->template?->name ?? 'Invitación digital' }}
                        </p>
                        <p class="text-size-small-heading text-[#8C8C8C]">
                            @if ($event->event_date)
                                Boda del {{ $event->event_date->format('d/m/Y') }}
                            @else
                                Sin fecha de boda capturada
                            @endif
                        </p>
                    </div>

                    {{-- Apagada a mano y vencida por fecha no son lo mismo: se dicen distinto. --}}
                    <span class="shrink-0 rounded-full bg-[#F2E0E0] px-3 py-1 text-size-small-heading text-[#8C2626]">
                        @if (!$event->is_active)
                            Desactivada
                        @elseif ($event->expires_at)
                            Venció el {{ $event->expires_at->format('d/m/Y') }}
                        @else
                            No disponible
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>

        <a href="{{ route('organizer.orders.index') }}"
            class="mt-6 inline-block text-parrafo text-[#2563EB] hover:underline">Ver mis pagos</a>
    </div>
@endif
