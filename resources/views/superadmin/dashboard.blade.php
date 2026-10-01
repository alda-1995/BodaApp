@php
    $dinero = fn (float $monto) => '$' . number_format($monto, 2) . ' MXN';
@endphp

<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">

            <div class="max-w-xl mb-6">
                <h2 class="text-size-title text-black mb-2">Panel</h2>
                <p class="text-parrafo text-[#737373]">
                    Cómo va el negocio y qué está esperando a que alguien lo resuelva.
                </p>
            </div>

            {{--
                El periodo sólo mueve las ventas y la actividad. El estado de
                las invitaciones y los pendientes son de hoy, y se avisa ahí
                mismo para que nadie los lea como si fueran del rango elegido.
            --}}
            <form method="GET" action="{{ route('superadmin.dashboard') }}"
                class="mb-8 flex flex-wrap items-end gap-3"
                x-data="{ periodo: '{{ $period->key }}' }">

                <label class="flex flex-col gap-1">
                    <span class="text-xs text-[#808080]">Periodo</span>
                    <select name="periodo" x-model="periodo"
                        class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm">
                        @foreach (\App\Services\Superadmin\DashboardPeriod::options() as $value => $label)
                            <option value="{{ $value }}" @selected($period->key === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <template x-if="periodo === 'personalizado'">
                    <div class="flex items-end gap-3">
                        <label class="flex flex-col gap-1">
                            <span class="text-xs text-[#808080]">Desde</span>
                            <input type="date" name="desde" value="{{ $period->inputValue('from') }}"
                                class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm">
                        </label>
                        <label class="flex flex-col gap-1">
                            <span class="text-xs text-[#808080]">Hasta</span>
                            <input type="date" name="hasta" value="{{ $period->inputValue('to') }}"
                                class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm">
                        </label>
                    </div>
                </template>

                <button type="submit"
                    class="h-10 rounded-lg bg-black px-5 text-sm text-white transition-colors hover:bg-[#333]">
                    Aplicar
                </button>
            </form>

            {{-- ---------------------------------------------------- Ventas --}}

            <div class="mb-3 flex items-baseline justify-between">
                <h3 class="text-lg text-black">Ventas</h3>
                <span class="text-xs text-[#808080]">{{ $period->label }}</span>
            </div>

            <div class="mb-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-superadmin.metric-card label="Ingresos" :value="$dinero($sales['ingresos'])">
                    @if ($sales['variacion'] !== null)
                        <span @class([
                            'text-xs',
                            'text-green-700' => $sales['variacion'] >= 0,
                            'text-red-700' => $sales['variacion'] < 0,
                        ])>
                            {{ $sales['variacion'] >= 0 ? '▲' : '▼' }}
                            {{ number_format(abs($sales['variacion']), 1) }}% vs. periodo anterior
                        </span>
                    @else
                        <span class="text-xs text-[#808080]">Sin ventas antes con qué comparar</span>
                    @endif
                </x-superadmin.metric-card>

                <x-superadmin.metric-card label="Órdenes pagadas" :value="$sales['pagadas']">
                    <span class="text-xs text-[#808080]">
                        {{ $sales['pendientes'] }} pendientes · {{ $sales['fallidas'] }} fallidas
                    </span>
                </x-superadmin.metric-card>

                <x-superadmin.metric-card label="Ticket promedio" :value="$dinero($sales['ticket'])" />

                <x-superadmin.metric-card label="Acumulado histórico" :value="$dinero($sales['acumulado'])">
                    <span class="text-xs text-[#808080]">Todas las ventas, sin filtro</span>
                </x-superadmin.metric-card>
            </div>

            @if ($sales['top_plantillas']->isNotEmpty())
                <div class="mb-10 rounded-xl border border-gray-200 bg-white p-5">
                    <h4 class="mb-4 text-sm text-black">Plantillas más vendidas</h4>

                    <ul class="grid gap-3">
                        @foreach ($sales['top_plantillas'] as $plantilla)
                            <li class="flex items-baseline justify-between gap-4 border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                                <span class="text-sm text-black">{{ $plantilla['nombre'] }}</span>
                                <span class="text-xs text-[#808080]">
                                    {{ $plantilla['ventas'] }} {{ $plantilla['ventas'] === 1 ? 'venta' : 'ventas' }}
                                    · {{ $dinero($plantilla['ingresos']) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- --------------------------------------------- Invitaciones --}}

            <div class="mb-3 flex items-baseline justify-between">
                <h3 class="text-lg text-black">Invitaciones</h3>
                <span class="text-xs text-[#808080]">Hoy, sin importar el periodo</span>
            </div>

            <div class="mb-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-superadmin.metric-card label="Activas" :value="$invitations['activas']">
                    <span class="text-xs text-[#808080]">de {{ $invitations['total'] }} en total</span>
                </x-superadmin.metric-card>

                <x-superadmin.metric-card label="Por vencer" :value="$invitations['por_vencer']">
                    <span class="text-xs text-[#808080]">
                        en los próximos {{ \App\Models\Event::EXPIRING_SOON_DAYS }} días
                    </span>
                </x-superadmin.metric-card>

                <x-superadmin.metric-card label="Vencidas" :value="$invitations['vencidas']">
                    <span class="text-xs text-[#808080]">
                        {{ $invitations['suspendidas'] }} suspendidas · {{ $invitations['sin_fecha'] }} sin fecha
                    </span>
                </x-superadmin.metric-card>

                <x-superadmin.metric-card label="Avance promedio" :value="$invitations['avance'] . '%'">
                    <span class="text-xs text-[#808080]">
                        {{ $invitations['sin_configurar'] }} sin empezar
                    </span>
                </x-superadmin.metric-card>
            </div>

            {{-- ------------------------------------------------ Pendientes --}}

            <div class="mb-3 flex items-baseline justify-between">
                <h3 class="text-lg text-black">Pide acción</h3>
                <span class="text-xs text-[#808080]">Hoy, sin importar el periodo</span>
            </div>

            @if ($pending === [])
                <div class="mb-10 rounded-xl border border-gray-200 bg-white p-5 text-sm text-[#737373]">
                    Nada pendiente: ninguna invitación por vencer, ninguna plantilla sin precio.
                </div>
            @else
                <div class="mb-10 grid gap-4 lg:grid-cols-2">
                    @foreach ($pending as $grupo)
                        <div class="rounded-xl border border-gray-200 bg-white p-5">
                            <div class="mb-4 flex items-baseline justify-between gap-4">
                                <h4 class="text-sm text-black">{{ $grupo['titulo'] }}</h4>
                                <a href="{{ $grupo['ver_todo'] }}"
                                    class="text-xs text-[#808080] transition-colors hover:text-black">Ver todo</a>
                            </div>

                            <ul class="grid gap-3">
                                @foreach ($grupo['filas'] as $fila)
                                    <li class="border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                                        <a href="{{ $fila['url'] }}"
                                            class="flex items-baseline justify-between gap-4 transition-colors hover:text-black">
                                            <span class="text-sm text-black">{{ $fila['texto'] }}</span>
                                            <span class="text-xs text-[#808080]">{{ $fila['nota'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- -------------------------------------------------- Actividad --}}

            <div class="mb-3 flex items-baseline justify-between">
                <h3 class="text-lg text-black">Actividad reciente</h3>
                <span class="text-xs text-[#808080]">{{ $period->label }}</span>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                @if ($activity->isEmpty())
                    <p class="text-sm text-[#737373]">No pasó nada en este periodo.</p>
                @else
                    <ul class="grid gap-3">
                        @foreach ($activity as $item)
                            <li class="flex items-baseline justify-between gap-4 border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                                <span class="text-sm text-black">
                                    <span class="text-[#737373]">{{ $item['titulo'] }}</span>
                                    {{ $item['detalle'] }}
                                    @if ($item['monto'] !== null)
                                        <span class="text-[#737373]">· {{ $dinero($item['monto']) }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-xs text-[#808080]">
                                    {{ $item['fecha']->locale('es')->isoFormat('D MMM, HH:mm') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>
    </section>
</x-layouts.dashboard-layout>
