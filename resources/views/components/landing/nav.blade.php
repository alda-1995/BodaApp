@props(['links' => []])

{{--
    Menú de la landing.

    A la derecha cambia según quién mira: a quien ya tiene cuenta se le ofrece
    su panel y no "iniciar sesión", que es la pantalla por la que acaba de
    pasar. En móvil las secciones se pliegan, pero la acción principal se queda
    siempre a la vista: es el botón que vende.
--}}
<header x-data="{ abierto: false }" class="sticky top-0 z-50 border-b border-black/5 bg-white/85 backdrop-blur">
    <nav class="mx-auto flex h-20 max-w-6xl items-center justify-between gap-6 px-6 lg:px-8">
        <a href="{{ route('home') }}" class="shrink-0" aria-label="{{ config('app.name', 'Tamira') }} · inicio">
            <span class="font-main text-2xl tracking-tight">{{ config('app.name', 'Tamira') }}</span>
        </a>

        {{-- Secciones de la página. En móvil viven en el panel de abajo. --}}
        <ul class="hidden items-center gap-8 md:flex">
            @foreach ($links as $href => $label)
                <li>
                    <a href="{{ $href }}" class="text-size-small-heading text-gray transition-colors hover:text-black">{{ $label }}</a>
                </li>
            @endforeach
        </ul>

        <div class="flex items-center gap-2 sm:gap-3">
            @auth
                <a href="{{ route('panel') }}"
                    class="btn btn-main !h-11 rounded-full text-size-small-heading">Ir a mi panel</a>
            @else
                <a href="{{ route('login') }}"
                    class="hidden text-size-small-heading text-gray transition-colors hover:text-black sm:inline">Iniciar sesión</a>
                <a href="#plantillas"
                    class="btn btn-main !h-11 rounded-full text-size-small-heading">Ver plantillas</a>
            @endauth

            <button type="button" @click="abierto = !abierto"
                class="-mr-2 inline-flex size-10 items-center justify-center rounded-full text-black md:hidden"
                :aria-expanded="abierto" aria-controls="menu-landing" aria-label="Abrir menú">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                    stroke-linecap="round" aria-hidden="true">
                    <path x-show="!abierto" d="M4 7h16M4 12h16M4 17h16" />
                    <path x-show="abierto" x-cloak d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>
    </nav>

    <div id="menu-landing" x-show="abierto" x-cloak @click="abierto = false"
        class="border-t border-black/5 bg-white px-6 pb-6 md:hidden">
        <ul class="flex flex-col">
            @foreach ($links as $href => $label)
                <li>
                    <a href="{{ $href }}" class="block border-b border-black/5 py-4 text-size-heading">{{ $label }}</a>
                </li>
            @endforeach

            @guest
                <li>
                    <a href="{{ route('login') }}" class="block py-4 text-size-heading">Iniciar sesión</a>
                </li>
            @endguest
        </ul>
    </div>
</header>
