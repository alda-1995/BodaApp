@php
    use App\Models\Event;

    // Estado de la invitación de cada cuenta. "Sin plantilla" es no haber comprado.
    $statuses = [
        'no_template' => ['Sin plantilla', 'bg-gray-100 text-gray-700'],
        Event::STATUS_SUSPENDED => ['Suspendido', 'bg-gray-100 text-gray-700'],
        Event::STATUS_EXPIRED => ['Vencido', 'bg-[#F2E0E0] text-[#8C5926]'],
        Event::STATUS_PENDING_DATE => ['Falta fecha de boda', 'bg-[#E0EAF2] text-[#26548C]'],
        Event::STATUS_EXPIRING => ['Por vencer', 'bg-[#FEF9C3] text-[#854D0E]'],
        Event::STATUS_ACTIVE => ['Activo', 'bg-[#E0F2E3] text-[#267333]'],
    ];

    $columns = [
        [
            'label' => 'Nombre / Empresa',
            'width' => 'w-[200px]',
            'render' => fn($user) => e($user->name),
        ],
        [
            'label' => 'Correo',
            'width' => 'w-[220px]',
            'render' => fn($user) => e($user->email),
        ],
        [
            'label' => 'Template',
            'width' => 'w-[160px]',
            'render' => function ($user) {
                $event = $user->latestEvent;
                return e($event?->template?->name ?? '—');
            },
        ],
        [
            'label' => 'F. compra',
            'width' => 'w-[130px]',
            'render' => function ($user) {
                $event = $user->latestEvent;
                $orderDate = $event?->order?->created_at;
                return $orderDate ? $orderDate->format('j M Y') : '—';
            },
        ],
        [
            // Ambas fechas aparecen hasta que la pareja captura la de su boda.
            'label' => 'F. boda',
            'width' => 'w-[130px]',
            'render' => function ($user) {
                $event = $user->latestEvent;
                return $event?->event_date ? $event->event_date->format('j M Y') : 'Pendiente';
            },
        ],
        [
            'label' => 'Vence el',
            'width' => 'w-[130px]',
            'render' => function ($user) {
                $event = $user->latestEvent;
                return $event?->expires_at ? $event->expires_at->format('j M Y') : 'Pendiente';
            },
        ],
        [
            'label' => 'Estado',
            'isHtml' => true,
            'width' => 'w-[130px]',
            'render' => function ($user) use ($statuses) {
                $event = $user->latestEvent;
                [$label, $classes] = $statuses[$event ? $event->statusKey() : 'no_template'];

                return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-normal ' . $classes . '">'
                    . e($label) . '</span>';
            },
        ],
        [
            // Hasta cuándo sirve la invitación, y su interruptor. Se toca a mano
            // cuando hay que intervenir: una boda pospuesta, una cortesía.
            'label' => 'Vigencia',
            'isHtml' => true,
            'width' => 'w-[110px]',
            'render' => function ($user) {
                $event = $user->latestEvent;

                if (!$event) {
                    return '—';
                }

                return '<a href="' . route('superadmin.events.validity.edit', $event->id) . '" '
                    . 'class="text-[#808080] hover:text-black transition-colors">Ajustar</a>';
            },
        ],
        [
            // Las imágenes de la plantilla de esa boda: el monograma, un mapa,
            // una secuencia de animación. Sólo existen si la plantilla las declara.
            'label' => 'Imágenes',
            'isHtml' => true,
            'width' => 'w-[120px]',
            'render' => function ($user) {
                $event = $user->latestEvent;

                if (!$event) {
                    return '—';
                }

                return '<a href="' . route('superadmin.events.assets.edit', $event->id) . '" '
                    . 'class="text-[#808080] hover:text-black transition-colors">Personalizar</a>';
            },
        ],
    ];
@endphp

<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-xl mb-6">
                <h2 class="text-size-title text-black mb-2">Usuarios</h2>
                <p class="text-parrafo text-[#737373]">
                    Cuentas dadas de alta en Tamira, su template, tiempo de vida y estado.
                </p>
            </div>

            {{-- Formulario de Filtros --}}
            <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 flex flex-wrap gap-4">
                <div class="w-full sm:w-72">
                    <x-controls.inputs.input name="search" value="{{ request('search') }}"
                        placeholder="Buscar usuario o correo..." />
                </div>

                <div class="w-full sm:w-52">
                    @php
                        $statusOptions = collect($statuses)->map(fn ($status) => $status[0])->all();
                    @endphp

                    <x-controls.selects.select name="status" placeholder="Todos los estados" :options="$statusOptions"
                        :selected="request('status')" onchange="this.querySelector('select').form.submit()"
                        class="w-full sm:w-52" />
                </div>
                @if(request()->hasAny(['search', 'status']))
                    <x-controls.button variant="cancel" href="{{ route('admin.users.index') }}">Limpiar filtros</x-controls.button>
                @endif
            </form>

            {{-- Tabla CRUD Adaptada --}}
            <div class="mt-6 md:mt-8">
                <x-tables.table-crud :columns="$columns" :items="$users" />
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>