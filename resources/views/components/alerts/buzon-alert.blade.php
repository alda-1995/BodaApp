@props([
    'title' => 'Revisa tu bandeja de entrada.',
    'message' => 'Te enviamos un enlace para restablecer tu contraseña.'
])

<div 
    x-data="{ open: true }" 
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
    x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    id="notification-toast"
    class="fixed top-[5%] md:top-[unset] md:bottom-[5%] right-[5%] md:right-[unset] md:left-[5%] z-50 flex w-full max-w-md items-start gap-4 rounded-md border border-base-gray bg-white py-2 px-3 shadow-lg"
>
    <div class="relative flex-shrink-0">
        <img src="{{ asset('images/auth/email-success-icon.png') }}" class="max-w-full" alt="icon message">
    </div>

    <div class="flex-1 pt-0.5">
        <h3 class="text-black font-inter text-parrafo">
            {{ $title }}
        </h3>
        <p class="mt-2 text-black font-inter text-parrafo">
            {{ $slot->isEmpty() ? $message : $slot }}
        </p>
    </div>

    <button 
        @click="open = false"
        type="button" 
        class="group -mr-1 -mt-1 inline-flex rounded-md bg-white p-1.5 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
        <span class="sr-only">Cerrar</span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5 transition-transform group-hover:scale-105">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    </button>
</div>