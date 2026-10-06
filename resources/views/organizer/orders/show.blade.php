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

    $marca = $etiquetas[$order->status] ?? ['texto' => ucfirst($order->status), 'clase' => 'bg-[#F2F2F2] text-[#595959]'];

    $fila = 'flex items-start justify-between gap-4 py-3 border-b border-[#F2F2F2] last:border-0';
    $etiqueta = 'text-size-small-heading text-[#737373] shrink-0';
    $dato = 'text-size-small-heading text-black text-right break-words min-w-0';
@endphp

<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-xl">
                <div class="mb-8">
                    <a href="{{ route('organizer.orders.index') }}"
                        class="text-size-small-heading text-[#2563EB] hover:underline">← Volver a mis pagos</a>

                    <div class="mt-4 flex items-start justify-between gap-4">
                        <h2 class="text-size-title text-black">Pago #{{ $order->id }}</h2>

                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs {{ $marca['clase'] }}">
                            {{ $marca['texto'] }}
                        </span>
                    </div>
                </div>

                <div class="rounded-xl border border-[#F2F2F2] bg-white px-4 py-2">
                    <div class="{{ $fila }}">
                        <span class="{{ $etiqueta }}">Plantilla</span>
                        <span class="{{ $dato }}">{{ $order->template?->name ?? 'Plantilla dada de baja' }}</span>
                    </div>

                    <div class="{{ $fila }}">
                        <span class="{{ $etiqueta }}">Fecha</span>
                        <span class="{{ $dato }}">
                            {{ $order->created_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY, HH:mm') }}
                        </span>
                    </div>

                    <div class="{{ $fila }}">
                        <span class="{{ $etiqueta }}">Importe</span>
                        <span class="{{ $dato }}">
                            ${{ number_format($order->amount, 2) }} {{ strtoupper($order->currency) }}
                        </span>
                    </div>

                    @if ($order->event)
                        <div class="{{ $fila }}">
                            <span class="{{ $etiqueta }}">Invitación</span>
                            <span class="{{ $dato }}">
                                @if ($order->event->custom_url)
                                    <a href="{{ $order->event->invitationUrl() }}" target="_blank" rel="noopener"
                                        class="text-[#2563EB] hover:underline">{{ $order->event->invitationUrl() }}</a>
                                @else
                                    Todavía sin dirección
                                @endif
                            </span>
                        </div>
                    @endif

                    {{--
                        El identificador de Stripe es lo primero que pide soporte
                        cuando hay que rastrear un cobro, así que se enseña tal
                        cual en vez de esconderlo.
                    --}}
                    <div class="{{ $fila }}">
                        <span class="{{ $etiqueta }}">Referencia</span>
                        <span class="{{ $dato }} font-mono text-xs">{{ $order->stripe_session_id }}</span>
                    </div>
                </div>

                <p class="text-size-small-heading text-[#8C8C8C] mt-4">
                    ¿Algo no cuadra con este pago? Escríbenos con la referencia de arriba.
                </p>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>
