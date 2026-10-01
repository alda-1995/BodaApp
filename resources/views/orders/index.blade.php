@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8">
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Mis Compras</h1>
            <p class="mt-2 text-sm text-gray-500">Historial de plantillas adquiridas y estado de tus eventos.</p>
        </div>

        @if($orders->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center shadow-sm">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <h3 class="mt-4 text-lg font-bold text-gray-900">Aún no tienes órdenes</h3>
                <p class="mt-1 text-sm text-gray-500">Cuando adquieras un diseño de invitación, aparecerá en esta sección.</p>
                <div class="mt-6">
                    <a href="{{ url('/') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                        Explorar Catálogo
                    </a>
                </div>
            </div>
        @else
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
                <ul role="list" class="divide-y divide-gray-100">
                    @foreach($orders as $order)
                        <li class="p-6 hover:bg-gray-50/50 transition-colors">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                
                                <!-- Info Principal -->
                                <div class="flex items-start space-x-4">
                                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900">
                                            {{ $order->template->name ?? 'Plantilla' }}
                                        </h4>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            Adquirido el {{ $order->created_at->format('d/m/Y H:i') }}
                                        </p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="text-sm font-black text-gray-900">${{ number_format($order->amount, 2) }}</span>
                                            <span class="text-xs uppercase font-medium text-gray-400">{{ $order->currency }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estatus y Acción -->
                                <div class="flex flex-row sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-2">
                                    @if($order->status === 'paid' || $order->status === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                            Paga
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            Pendiente
                                        </span>
                                    @endif

                                    <a href="{{ route('orders.show', $order->id) }}" class="mt-1 text-sm font-semibold text-indigo-600 hover:text-indigo-500 inline-flex items-center gap-1">
                                        Detalles de orden
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </a>
                                </div>

                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Paginación -->
            <div class="mt-6">
                {{ $orders->links() }}
            </div>
        @endif

    </div>
</div>
@endsection