@props([
    'type' => 'info',
    'message' => null,
    'messages' => null,
])

@php
    $styles = [
        'success' => 'bg-green-100 border-green-400 text-green-700 border-green-700',
        'error' => 'bg-red-100 border-red-400 text-red-700 border-red-700',
        // Ni error ni éxito: se guardó, pero hay algo que el organizador debe revisar.
        'warning' => 'bg-amber-100 border-amber-400 text-amber-800 border-amber-700',
        'info' => 'bg-blue-100 border-blue-400 text-blue-700 border-blue-700',
    ];
    $iconPaths = [
        'success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', // Check circle
        'error' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', // X circle
        'warning' => 'M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.5 0l-7.1 12.25A2 2 0 005 19z', // Triangle
        'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', // Info circle
    ];
    $baseClass = $styles[$type] ?? $styles['info'];
    $iconPath = $iconPaths[$type] ?? $iconPaths['info'];
@endphp

<div class="toast-notification p-4 border-l-4 shadow-lg rounded-md w-96 {{ $baseClass }}" role="alert">
    @if (is_array($messages))
        <div class="flex justify-end mb-4">
            <button type="button" class="close-toast cursor-pointer ml-auto -mx-1.5 -my-1.5 p-1.5 rounded-full inline-flex focus:outline-none opacity-80 hover:opacity-100">
                <span class="sr-only">Cerrar Notificación</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <ul class="list-disc list-inside space-y-1 font-inter text-size-small-heading">
            @foreach ($messages as $msg)
                <li>{{ $msg }}</li>
            @endforeach
        </ul>
    @else
        <div class="flex items-center">
            <div class="shrink-0 mr-3">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
                </svg>
            </div>
            <p class="text-parrafo font-medium grow">{{ $message }}</p>
            <button type="button" class="close-toast cursor-pointer ml-auto -mx-1.5 -my-1.5 p-1.5 rounded-full inline-flex focus:outline-none opacity-80 hover:opacity-100">
                <span class="sr-only">Cerrar Notificación</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>            
    @endif
</div>