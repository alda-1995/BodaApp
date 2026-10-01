@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-6">
            <a href="{{ url('/orders/' . auth()->id()) }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500">
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Volver a mis compras
            </a>
        </div>

        <div class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Orden #{{ $order->id }}</h1>
                    <p class="text-xs text-gray-400 mt-1">ID de Sesión: {{ Str::limit($order->stripe_session_id, 20) }}</p>
                </div>
                <div>
                    @if($order->status === 'paid' || $order->status === 'completed')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200">Paga</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">Pendiente</span>
                    @endif
                </div>
            </div>

            <!-- Detalles del Producto -->
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">Producto Adquirido</h3>
                    <div class="flex justify-between items-center bg-gray-50 p-4 rounded-xl">
                        <span class="font-semibold text-gray-900">{{ $order->template->name ?? 'Plantilla de Invitación' }}</span>
                        <span class="font-black text-gray-900">${{ number_format($order->amount, 2) }} {{ strtoupper($order->currency) }}</span>
                    </div>
                </div>

                <!-- Info de Evento -->
                @if($order->event)
                    <div class="border-t border-gray-100 pt-6">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">Evento Vinculado</h3>
                        <div class="border border-gray-100 rounded-xl p-4 flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-gray-900">{{ $order->event->title }}</h4>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Fecha programada:
                                    {{ $order->event->event_date?->format('d/m/Y') ?? 'pendiente de capturar' }}
                                </p>
                            </div>
                            <a href="{{ url('/events/' . $order->event->id . '/edit') }}" class="text-xs font-bold bg-indigo-50 text-indigo-600 px-3 py-2 rounded-lg hover:bg-indigo-100 transition-colors">
                                Gestionar
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection