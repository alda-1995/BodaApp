@php
    $empty = '—';

    $columns = [
        [
            'label' => 'Pareja',
            'width' => 'w-[220px]',
            'render' => fn($event) => e($event->user?->name ?? $empty),
        ],
        [
            'label' => 'Invitación',
            'width' => 'w-[200px]',
            'render' => fn($event) => e($event->custom_url ?: $empty),
        ],
        [
            'label' => 'Plantilla',
            'width' => 'w-[180px]',
            'render' => fn($event) => e($event->template?->name ?? $empty),
        ],
        [
            'label' => 'Personalizadas',
            'width' => 'w-[140px]',
            'render' => function ($event) {
                // Cuántas imágenes tiene reemplazadas hoy esta boda.
                $count = collect($event->template_assets ?? [])->flatten()->filter()->count();

                return $count > 0 ? $count . ' imágenes' : 'Ninguna';
            },
        ],
        [
            'label' => 'Acciones',
            'width' => 'w-[130px]',
            'isHtml' => true,
            'render' => fn($event) => '<a href="' . route('superadmin.events.assets.edit', $event->id) . '" '
                . 'class="text-[#808080] hover:text-black transition-colors">Personalizar</a>',
        ],
    ];
@endphp

<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-xl mb-6">
                <h2 class="text-size-title text-black mb-2">Personalización</h2>
                <p class="text-parrafo text-[#737373]">
                    Reemplaza imágenes de la plantilla —un monograma, un mapa, una secuencia de
                    animación— para una boda en concreto, sin afectar a las demás.
                </p>
            </div>

            <form method="GET" action="{{ route('superadmin.events.assets.index') }}"
                class="mb-6 flex flex-wrap items-start gap-4">
                <div class="w-full sm:w-72">
                    <x-controls.inputs.input name="search" value="{{ $search }}"
                        placeholder="Buscar pareja, correo o URL..." />
                </div>

                @if ($search)
                    <x-controls.button variant="cancel" href="{{ route('superadmin.events.assets.index') }}">
                        Limpiar búsqueda
                    </x-controls.button>
                @endif
            </form>

            <x-tables.table-crud :columns="$columns" :items="$events" :show-actions="false" />
        </div>
    </section>
</x-layouts.dashboard-layout>
