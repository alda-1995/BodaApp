@props([
    // Invitación propia que ya no está vigente; null si nunca ha comprado.
    'event' => null,
    'hasSharedEvents' => false,
])

<div class="max-w-xl rounded-xl border border-[#EBEBEB] bg-white p-6 md:p-8 font-inter">
    @if ($event)
        <p class="mb-3 inline-block rounded-full bg-[#F2E0E0] px-3 py-1 text-size-small-heading text-[#8C2626]">
            Invitación vencida
        </p>
        <h3 class="mb-2 text-size-heading text-black">Tu invitación digital ya no está activa</h3>
        <p class="mb-6 text-parrafo text-[#737373]">
            @if ($event->expires_at)
                Estuvo disponible hasta el {{ $event->expires_at->format('d/m/Y') }}.
            @endif
            Compra una nueva invitación digital para tu siguiente celebración.
        </p>
    @else
        <h3 class="mb-2 text-size-heading text-black">Aún no tienes una invitación digital</h3>
        <p class="mb-6 text-parrafo text-[#737373]">
            Elige una plantilla y crea la invitación de tu boda.
        </p>
    @endif

    <div class="flex flex-wrap items-center gap-4">
        <x-controls.button href="{{ route('home') }}">
            {{ $event ? 'Comprar otra invitación' : 'Ver plantillas' }}
        </x-controls.button>

        @if ($hasSharedEvents)
            <a href="{{ route('shared-events.index') }}" class="text-parrafo text-[#2563EB] hover:underline">
                Ver invitaciones compartidas contigo
            </a>
        @endif
    </div>
</div>
