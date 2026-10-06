@php
    /*
    | Los estados que la columna admite (enum de la migración de orders). 'paid'
    | y 'completed' son lo mismo para quien mira: su pago pasó.
    |
    | 'expired' no está aquí a propósito: OrderService lo usa sólo como
    | respuesta al sondeo del checkout, nunca se guarda en la orden.
    */
    $etiquetas = [
        'completed' => ['texto' => 'Pagada', 'clase' => 'bg-[#DCFCE7] text-[#166534]'],
        'paid' => ['texto' => 'Pagada', 'clase' => 'bg-[#DCFCE7] text-[#166534]'],
        'pending' => ['texto' => 'Pendiente', 'clase' => 'bg-[#FEF9C3] text-[#854D0E]'],
        'failed' => ['texto' => 'Rechazada', 'clase' => 'bg-[#FEE2E2] text-[#991B1B]'],
        'refunded' => ['texto' => 'Reembolsada', 'clase' => 'bg-[#F2F2F2] text-[#595959]'],
    ];

    $estado = fn (string $status) => $etiquetas[$status] ?? ['texto' => ucfirst($status), 'clase' => 'bg-[#F2F2F2] text-[#595959]'];
@endphp

<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-xl">
                <div class="mb-8">
                    <a href="{{ route('organizer.settings.index') }}"
                        class="text-size-small-heading text-[#2563EB] hover:underline">← Volver a Configuración</a>

                    <h2 class="text-size-title text-black mt-4 mb-2">Mis pagos</h2>
                    <p class="text-parrafo text-[#737373]">Lo que has comprado y en qué quedó cada pago.</p>
                </div>

                @if ($orders->isEmpty())
                    <div class="rounded-xl border border-[#F2F2F2] bg-white p-8 text-center">
                        <p class="text-parrafo text-[#737373]">Todavía no tienes pagos registrados.</p>
                    </div>
                @else
                    <ul class="space-y-3">
                        @foreach ($orders as $order)
                            @php($marca = $estado($order->status))

                            <li class="rounded-lg border border-[#F2F2F2] bg-white px-4 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="truncate text-size-small-heading text-black">
                                            {{ $order->template?->name ?? 'Plantilla dada de baja' }}
                                        </p>
                                        <p class="text-xs text-[#8C8C8C] mt-0.5">
                                            {{ $order->created_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
                                        </p>
                                    </div>

                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs {{ $marca['clase'] }}">
                                        {{ $marca['texto'] }}
                                    </span>
                                </div>

                                <div class="mt-3 flex items-end justify-between gap-4">
                                    <p class="text-size-small-heading text-black">
                                        ${{ number_format($order->amount, 2) }}
                                        <span class="text-xs text-[#8C8C8C]">{{ strtoupper($order->currency) }}</span>
                                    </p>

                                    <a href="{{ route('organizer.orders.show', $order->id) }}"
                                        class="text-size-small-heading text-[#2563EB] hover:underline">Ver detalle</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>
