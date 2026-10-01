@extends('layouts.app') {{-- O el layout principal que uses --}}

@section('content')
    <div class="bg-gray-50 min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12">
                <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight sm:text-5xl">
                    Nuestras Plantillas de Invitaciones
                </h1>
                <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500">
                    Elige el diseño perfecto para tu gran evento. Todos nuestros modelos son interactivos y optimizados para
                    móviles.
                </p>
            </div>

            @if($templates->isEmpty())
                <div class="text-center bg-white rounded-lg border border-gray-200 p-12 shadow-sm">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 002-2H4a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay diseños disponibles</h3>
                    <p class="mt-1 text-sm text-gray-500">Estamos cocinando nuevos estilos. ¡Vuelve pronto!</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-y-10 gap-x-6 sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-8">
                    @foreach($templates as $template)
                        <div
                            class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col justify-between transition-all duration-300 hover:shadow-xl hover:-translate-y-1">

                            <div class="aspect-w-16 aspect-h-9 bg-gray-200 group-hover:opacity-95 transition-opacity duration-300">
                                {{-- Aquí puedes linkear dinámicamente imágenes si las agregas después, por ahora un placeholder
                                estético --}}
                                <div
                                    class="w-full h-48 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center p-6 text-center text-white">
                                    <div>
                                        <span
                                            class="text-xs uppercase tracking-widest bg-white/20 px-2.5 py-1 rounded-full font-medium">Diseño
                                            Premium</span>
                                        <p class="mt-2 text-lg font-bold">{{ $template->name }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6 flex-1 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-bold text-gray-900 truncate">
                                            {{ $template->name }}
                                        </h3>
                                        <span class="text-xl font-black text-indigo-600">
                                            ${{ number_format($template->price, 2) }} <span
                                                class="text-xs text-gray-400 font-normal">MXN</span>
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500 line-clamp-2">
                                        Totalmente personalizable, con confirmación de asistencia RSVP directa y mapa interactivo.
                                    </p>
                                </div>

                                <div class="mt-6 flex flex-col gap-2">
                                    {{-- Demo de la plantilla con datos de ejemplo. --}}
                                    <a href="{{ route('templates.preview', $template->slug) }}" target="_blank" rel="noopener"
                                        class="w-full flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                        Ver demo
                                    </a>
                                    <a href="{{ route('checkout.checkout-preview', $template->slug) }}"
                                        class="w-full flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                        Ver Demo e Info
                                    </a>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
@endsection