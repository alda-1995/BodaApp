@php
    use App\Services\Invitation\RsvpReportService;

    $empty = '—';

    $tabs = [
        RsvpReportService::TAB_CONFIRMED => 'Confirmados',
        RsvpReportService::TAB_NOT_CONFIRMED => 'No confirmados',
    ];

    $tiles = [
        ['label' => 'Confirmaciones recibidas', 'value' => $stats['responses']],
        ['label' => 'Total de personas confirmadas', 'value' => $stats['people']],
        ['label' => 'Pendientes de responder', 'value' => $stats['pending']],
    ];

    /*
     * Una columna por cada pregunta de esta boda, en el orden en que el
     * organizador las puso. Antes se enseñaba una sola y salía de la primera
     * respuesta guardada, así que la columna cambiaba según quién contestara
     * antes.
     */
    $preguntas = collect($questions)->map(fn (string $pregunta) => [
        'label' => Str::limit($pregunta, 40),
        'width' => 'w-[220px]',
        'render' => fn($invitation) => e($invitation->rsvp?->answers[$pregunta] ?? $empty),
    ])->all();

    $columns = array_values(array_filter([
        [
            'label' => 'Invitado',
            'width' => 'w-[220px]',
            'render' => fn($invitation) => e($invitation->guest?->name ?? $empty),
        ],
        [
            'label' => 'Asistentes',
            'width' => 'w-[110px]',
            'render' => fn($invitation) => e((string) ($invitation->rsvp?->confirmed_passes ?? $empty)),
        ],
        [
            'label' => 'Asistencia',
            'width' => 'w-[140px]',
            'render' => fn($invitation) => match ($invitation->rsvp?->attendance) {
                'confirmed' => 'Asistirá',
                'declined' => 'No asistirá',
                default => 'Sin responder',
            },
        ],
        /*
         * Las restricciones ya no se preguntan con un campo aparte: ahora son
         * una pregunta más que escribe el organizador. La columna se queda sólo
         * si alguien la contestó cuando sí existía, para no esconder lo que ya
         * está guardado; en una boda nueva no aparece vacía.
         */
        $invitations->contains(fn($invitation) => filled($invitation->rsvp?->dietary_restrictions))
            ? [
                'label' => 'Restricciones',
                'width' => 'w-[200px]',
                'render' => fn($invitation) => e($invitation->rsvp?->dietary_restrictions ?: $empty),
            ]
            : null,
        ...$preguntas,
        [
            'label' => 'Contacto',
            'width' => 'w-[180px]',
            'render' => fn($invitation) => e($invitation->guest?->phone ?: ($invitation->guest?->email ?: $empty)),
        ],
    ]));
@endphp

<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-xl mb-6">
                <h2 class="text-size-title text-black mb-2">Confirmación de Asistencia</h2>
                <p class="text-parrafo text-[#737373]">
                    Revisa quién ha confirmado su asistencia y descarga el reporte completo.
                </p>
            </div>

            {{-- Resumen --}}
            <div class="mb-8 flex flex-wrap gap-4">
                @foreach ($tiles as $tile)
                    <div class="min-w-[180px] flex-1 rounded-lg border border-[#EBEBEB] bg-white px-4 py-3">
                        <p class="text-size-small-heading text-[#808080]">{{ $tile['label'] }}</p>
                        <p class="text-size-subtitle text-black">{{ $tile['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mb-6">
                <x-tabs.tab-links :tabs="$tabs" />
            </div>

            {{-- Búsqueda + descarga --}}
            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <form method="GET" action="{{ route('organizer.rsvps.index') }}"
                    class="flex flex-wrap items-start gap-4">
                    <input type="hidden" name="status" value="{{ $tab }}">
                    <div class="w-full sm:w-72">
                        <x-controls.inputs.input name="search" value="{{ $search }}" placeholder="Buscar invitado..." />
                    </div>
                    @if ($search)
                        <x-controls.button variant="cancel"
                            href="{{ route('organizer.rsvps.index', ['status' => $tab]) }}">
                            Limpiar búsqueda
                        </x-controls.button>
                    @endif
                </form>

                <x-controls.button variant="secondary" href="{{ route('organizer.rsvps.export') }}">
                    Exportar confirmados (Excel)
                </x-controls.button>
            </div>

            <x-tables.table-crud :columns="$columns" :items="$invitations" :show-actions="false" />
        </div>
    </section>
</x-layouts.dashboard-layout>
