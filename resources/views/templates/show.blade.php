@extends('layouts.app')

@section('content')
    <div class="bg-white min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Enlace para volver -->
            <div class="mb-6">
                <a href="{{ url('/') }}"
                    class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver al catálogo
                </a>
            </div>

            <div class="lg:grid lg:grid-cols-2 lg:gap-x-8 lg:items-start">

                <!-- Columna Izquierda: Vista previa / Imagen -->
                <div class="flex flex-col">
                    <div
                        class="w-full aspect-w-1 aspect-h-1 rounded-2xl overflow-hidden bg-gradient-to-br from-indigo-500 to-purple-600 p-1 flex items-center justify-center min-h-[350px] lg:min-h-[500px] shadow-lg">
                        <div class="text-center text-white px-6">
                            <svg class="mx-auto h-16 w-16 opacity-75 mb-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span
                                class="text-xs uppercase tracking-widest bg-white/20 px-3 py-1 rounded-full font-semibold">Demo
                                en Vivo</span>
                            <h2 class="mt-4 text-3xl font-extrabold">{{ $template->name }}</h2>
                            <p class="mt-2 text-indigo-100 max-w-sm mx-auto text-sm">Esta es una simulación del diseño
                                digital interactivo optimizado para smartphones.</p>

                            <!-- Botón para abrir la demo real -->
                            <div class="mt-8">
                                {{-- Aquí apuntas a la ruta real que renderiza el view_path del template --}}
                                <a href="{{ url('/demo/' . $template->slug) }}" target="_blank"
                                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-indigo-700 bg-white hover:bg-indigo-50 shadow-sm transition duration-150">
                                    <svg class="mr-2 h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Probar demo en pantalla completa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Información y Compra -->
                <div class="mt-10 px-4 sm:px-0 sm:mt-16 lg:mt-0">
                    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                        {{ $template->name }}
                    </h1>

                    <div class="mt-3">
                        <h2 class="sr-only">Información del precio</h2>
                        <p class="text-3xl font-black text-indigo-600">
                            ${{ number_format($template->price, 2) }} <span
                                class="text-sm text-gray-500 font-normal">MXN</span>
                        </p>
                    </div>

                    <div class="mt-6">
                        <h3 class="sr-only">Descripción</h3>
                        <p class="text-base text-gray-700 leading-relaxed">
                            Eleva la experiencia de tus invitados con nuestro diseño premium <span
                                class="font-semibold">"{{ $template->name }}"</span>. Una invitación completamente digital,
                            ecológica y elegante que se adapta perfectamente a cualquier dispositivo móvil u ordenador.
                        </p>
                    </div>

                    <!-- Características incluidas -->
                    <div class="mt-8 border-t border-gray-200 pt-8">
                        <h3 class="text-sm font-medium text-gray-900">¿Qué incluye esta plantilla?</h3>
                        <div class="mt-4">
                            <ul role="list" class="grid grid-cols-1 gap-y-3 sm:grid-cols-2 sm:gap-x-4">
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-3 h-5 w-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Confirmación de asistencia (RSVP)
                                </li>
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-3 h-5 w-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Ubicación con Google Maps
                                </li>
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-3 h-5 w-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Sugerencia de Mesa de Regalos
                                </li>
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-3 h-5 w-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Contador regresivo del evento
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Formulario/Acción de Compra -->
                    <div class="mt-10">
                        <a href="{{ route('checkout.detail-payment', $template->slug) }}"
                            class="w-full bg-indigo-600 border border-transparent rounded-xl py-4 px-8 flex items-center justify-center text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 shadow-md">
                            <svg class="mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 0a2 2 0 110 4 2 2 0 010-4z" />
                            </svg>
                            Adquirir este diseño ahora
                        </a>
                        <p class="mt-4 text-center text-xs text-gray-400">
                            Pago 100% seguro procesado por Stripe. Acceso instantáneo tras la confirmación.
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection