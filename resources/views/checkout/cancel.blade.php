@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-sm border border-gray-100 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 text-yellow-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Pago cancelado</h2>
            <p class="mt-2 text-sm text-gray-500">
                El proceso de pago fue interrumpido. No se ha realizado ningún cargo a tu tarjeta. Puedes intentarlo de nuevo cuando estés listo.
            </p>
        </div>
        <div class="mt-6">
            <a href="{{ url('/') }}" class="w-full flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-xl text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                Regresar al catálogo
            </a>
        </div>
    </div>
</div>
@endsection