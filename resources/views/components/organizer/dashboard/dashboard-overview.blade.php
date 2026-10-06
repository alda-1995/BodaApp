@props([
    'userName' => auth()->user()->name ?? 'Usuario',
    // Las cifras las calcula DashboardMetrics. Sin valores por omisión a
    // propósito: un panel que inventa números es peor que uno que falla.
    'metrics',
    'progressPercentage' => 0,
    'progressUrl' => '#',
    'invitationUrl' => null,
    'guestsUrl' => null,
    'notificationsUrl' => null,
    'rsvpsUrl' => null,
])
<section class="pt-32 pb-20 md:py-10">
    <div class="container">
        <div class="space-y-8">
            <!-- Header -->
            <div class="flex flex-wrap items-start justify-between gap-4 max-w-5xl">
                <div class="space-y-2">
                    <h2 class="font-inter text-size-title">Dashboard</h2>
                    <p class="text-parrafo font-inter text-[#595959]">Hola, {{ $userName }}</p>
                </div>

                @if ($invitationUrl)
                    <x-controls.button href="{{ $invitationUrl }}" variant="secondary" target="_blank" rel="noopener">
                        Ver mi invitación
                    </x-controls.button>
                @endif
            </div>

            {{--
                Sin dirección no hay nada que ver ni que mandar, y es lo
                primero que hay que resolver: va de ancho completo, bajo el
                saludo, antes que las cifras.
            --}}
            @unless ($invitationUrl)
                <div class="max-w-5xl">
                    <x-organizer.missing-address />
                </div>
            @endunless

            <!-- Grilla de Tarjetas -->
            <div class="max-w-xl">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-organizer.dashboard.stats-card title="Cuenta regresiva a la boda"
                        :value="$metrics['countdown']" iconBg="bg-[#EBEBEB]">
                        <x-slot:icon>
                        </x-slot:icon>
                    </x-organizer.dashboard.stats-card>

                    <x-organizer.dashboard.stats-card iconBg="bg-[#EBEBEB]"
                        title="Invitaciones enviadas / pendientes"
                        :value="$metrics['sent'] . ' / ' . $metrics['pending']"
                        buttonText="Enviar invitaciones" :buttonAction="$notificationsUrl" />

                    {{-- Cuántos dijeron que sí, de cuántos hay invitados. --}}
                    <x-organizer.dashboard.stats-card iconBg="bg-[#EBEBEB]" title="Confirmaciones de asistencia"
                        :value="$metrics['confirmed'] . ' / ' . $metrics['guests']"
                        buttonText="Ver confirmaciones" :buttonAction="$rsvpsUrl" />

                    <x-organizer.dashboard.stats-card iconBg="bg-[#EBEBEB]"
                        title="Mensajes restantes (WhatsApp/correo)"
                        :value="$metrics['remaining_messages']" />
    
                    @if($progressPercentage >= 100)
                        <x-organizer.dashboard.progress-card :percentage="$progressPercentage" :link-url="$progressUrl"
                            message="Tu evento está listo" link-text="Revisar configuración" />
                    @else
                        <x-organizer.dashboard.progress-card :percentage="$progressPercentage" :link-url="$progressUrl" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>