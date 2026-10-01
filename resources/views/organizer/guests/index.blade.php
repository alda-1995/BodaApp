@php
    $empty = '—';

    // Botón que copia el enlace personal del invitado. table-crud aplica
    // html_entity_decode a las celdas HTML, por eso la URL va en comillas simples.
    $copyLink = function (?string $url) use ($empty) {
        if (!$url) {
            return $empty;
        }

        return '<button type="button" x-data="{ copied: false }" '
            . '@click="navigator.clipboard.writeText(\'' . e($url) . '\').then(() => { copied = true; setTimeout(() => copied = false, 1500) })" '
            . 'x-text="copied ? \'¡Copiado!\' : \'Copiar URL\'" '
            . 'class="text-[#808080] hover:text-black transition-colors cursor-pointer">Copiar URL</button>';
    };

    $columns = [
        [
            'label' => 'Nombre',
            'width' => 'w-[200px]',
            'render' => fn($guest) => e($guest->name),
        ],
        [
            'label' => 'Correo',
            'width' => 'w-[220px]',
            'render' => fn($guest) => $guest->email ? e($guest->email) : $empty,
        ],
        [
            'label' => 'Teléfono',
            'width' => 'w-[160px]',
            'render' => fn($guest) => $guest->phone ? e($guest->phone) : $empty,
        ],
        [
            'label' => 'Tope acompañantes',
            'width' => 'w-[150px]',
            'render' => fn($guest) => e((string) ($guest->invitationFor($event)?->max_passes ?? $empty)),
        ],
        [
            'label' => 'Enlace',
            'width' => 'w-[130px]',
            'isHtml' => true,
            'render' => fn($guest) => $copyLink($guest->invitationFor($event)?->invitationUrl()),
        ],
        [
            'label' => 'Acciones',
            'width' => 'w-[130px]',
            'isHtml' => true,
            'render' => fn($guest) => '<a href="' . route('organizer.guests.show', $guest->id) . '" '
                . 'class="text-[#808080] hover:text-black transition-colors">Ver detalle</a>',
        ],
    ];
@endphp

<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-xl mb-6">
                <h2 class="text-size-title text-black mb-2">Invitados</h2>
                <p class="text-parrafo text-[#737373]">
                    Administra tu lista de invitados, sus acompañantes permitidos y sus enlaces de invitación.
                </p>
            </div>

            @if (session('import_errors'))
                <div class="mb-6 max-w-2xl rounded-md bg-[#F2E0E0] px-4 py-3 text-parrafo text-[#8C5926]">
                    <p class="mb-1">Estas filas no se importaron:</p>
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach (session('import_errors') as $importError)
                            <li>{{ $importError }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Búsqueda + acciones --}}
            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <form method="GET" action="{{ route('organizer.guests.index') }}" class="flex flex-wrap items-start gap-4">
                    <div class="w-full sm:w-72">
                        <x-controls.inputs.input name="search" value="{{ $search }}" placeholder="Buscar invitado..." />
                    </div>
                    @if ($search)
                        <x-controls.button variant="cancel" href="{{ route('organizer.guests.index') }}">
                            Limpiar búsqueda
                        </x-controls.button>
                    @endif
                </form>

                <div class="flex flex-wrap gap-4">
                    <x-controls.button variant="secondary" href="{{ route('organizer.guests.import') }}">
                        Importar invitados
                    </x-controls.button>
                    <x-controls.button href="{{ route('organizer.guests.create') }}">
                        + Añadir invitado
                    </x-controls.button>
                </div>
            </div>

            <x-tables.table-crud :columns="$columns" :items="$guests" :show-actions="false" />
        </div>
    </section>
</x-layouts.dashboard-layout>
