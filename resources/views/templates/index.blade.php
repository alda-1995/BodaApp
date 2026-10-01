@php
    $columns = [
        [
            'label' => 'Plantilla',
            'width' => 'w-[150px]',
            'render' => fn($template) => e($template['name']),
        ],
        [
            'label' => 'Ruta plantilla',
            'width' => 'w-[150px]',
            'render' => fn($template) => e($template['view_path']),
        ],
        [
            'label' => 'Precio',
            'width' => 'w-[150px]',
            'render' => fn($template) => e($template['price']),
        ],
        [
            'label' => 'Vigencia',
            'width' => 'w-[150px]',
            'render' => fn($template) => e(($template['duration_days'] ?: config('events.default_duration_days'))
                . ' días tras la boda'),
        ],
        [
            // Abre la plantilla con datos de ejemplo, tal como la verá un invitado.
            'label' => 'Vista previa',
            'width' => 'w-[130px]',
            'isHtml' => true,
            'render' => fn($template) => '<a href="' . route('templates.preview', $template['slug'])
                . '" target="_blank" rel="noopener" class="text-[#3952F6] text-parrafo">Ver plantilla</a>',
        ],
        [
            'label' => 'Estado',
            'type' => 'status',
            'width' => 'w-[120px]',
            'render' => fn($template) => $template->is_active ?? $template['is_active'],
        ],
    ];
    $editRoute = 'templates.edit';
    $deleteRoute = 'templates.destroy';
@endphp
<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16">
        <div class="container">
            <div class="max-w-lg">
                <h2 class="text-size-title font-inter text-black mb-4">Templates y precios</h2>
                <p class="text-parrafo text-[#737373]">Administra el catálogo de templates disponibles para la venta,
                    sus precios y su estado.</p>
            </div>
            <div class="flex justify-end">
                <x-controls.button href="{{ route('templates.create') }}">+ Agregar template</x-controls.button>
            </div>
            <div class="mt-6 md:mt-8">
                <x-tables.table-crud :columns="$columns" :editRoute="$editRoute" :deleteRoute="$deleteRoute"
                    :items="$templates" />
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>