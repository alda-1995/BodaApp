<div x-data="{ openSidebar: false }">
    <!-- BACKDROP (solo en mobile) -->
    <div x-show="openSidebar" @click="openSidebar = false" class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        x-transition.opacity></div>

    <!-- SIDEBAR -->
    <div :class="{ 'translate-x-0': openSidebar, '-translate-x-full': !openSidebar }"
        class="fixed top-0 left-0 z-[200] h-screen w-[240px] border-r border-r-gray-400 bg-white transform transition-transform duration-300 ease-in-out lg:translate-x-0">
        <div class="flex flex-col h-full p-4 justify-between min-h-[700px]">
            <div class="flex flex-col gap-4">
                <a href="{{ route('home') }}">
                    <h1 class="text-black text-size-subtitle font-roboto font-medium">{{ config('app.name', 'Tamira') }}</h1>
                </a>

                <!-- Menú -->
                @auth
                    @foreach(auth()->user()->roles as $role)
                        @if(isset($menuItems[$role->name]))
                            @foreach($menuItems[$role->name] as $item)
                                <x-nav.link
                                    :route="$item['route']" 
                                    :params="$item['params'] ?? []"
                                    :active-routes="$item['active'] ?? []"
                                    :icon="$item['icon']"
                                >
                                    {{ $item['label'] }}
                                </x-nav.link>
                            @endforeach
                        @endif
                    @endforeach
                @endauth

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-nav.link onclick="event.preventDefault(); this.closest('form').submit();" route="logout" icon="logout">
                        Cerrar sesión
                    </x-nav.link>
                </form>
            </div>
        </div>
    </div>

    <!-- NAVBAR -->
    <nav class="fixed top-0 left-0 z-50 bg-gray-300 w-full lg:hidden">
        <div class="container">
            <div class="flex justify-between items-center h-[80px]">
                <a href="/" class="text-[#111418] text-base font-medium">
                    {{ config('app.name', 'Tamira') }}
                </a>

                <button type="button" class="h-11 w-11 flex justify-center items-center lg:hidden"
                    @click="openSidebar = true">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>
</div>