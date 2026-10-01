<x-layouts.guest-layout>
    <div class="flex flex-col md:flex-row">
        <div class="hidden md:block md:w-1/2">
            <div class="h-full w-full bg-cover bg-center"
                style="background-image: url('{{ asset('images/auth/boda-auth.jpg') }}');"></div>
        </div>
        <div class="md:w-1/2">
            <div class="container">
                <div class="pt-32 pb-20 md:py-16 max-w-lg md:min-h-screen md:flex md:flex-col md:justify-center font-inter">
                    @switch($state)
                        @case('register')
                            <p class="text-[#8C8C8C] text-size-small-heading mb-2">Invitación de coadministrador</p>
                            <h2 class="text-size-main text-black mb-2">Crea tu cuenta</h2>
                            <p class="text-parrafo text-[#737373] mb-6">
                                Te invitaron a ayudar a administrar una boda. Tu cuenta usará el correo
                                <strong class="text-black">{{ $invitation->email }}</strong>.
                            </p>

                            <form method="POST" action="{{ request()->fullUrl() }}" novalidate>
                                @csrf
                                <div class="mb-4">
                                    <x-controls.input type="text" name="name" placeholder="Tu nombre" autocomplete="name" required autofocus />
                                </div>
                                <div class="mb-4">
                                    <x-controls.password type="password" name="password" placeholder="Contraseña" required />
                                </div>
                                <div class="mb-6">
                                    <x-controls.password name="password_confirmation" placeholder="Confirma tu contraseña" required />
                                </div>
                                <x-controls.button type="submit">Aceptar invitación</x-controls.button>
                            </form>
                            @break

                        @case('wrong_account')
                            <h2 class="text-size-main text-black mb-2">Esta invitación es para otra cuenta</h2>
                            <p class="text-parrafo text-[#737373] mb-6">
                                La invitación se envió a <strong class="text-black">{{ $invitation->email }}</strong>.
                                Cierra sesión y entra con ese correo para aceptarla.
                            </p>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-controls.button type="submit">Cerrar sesión</x-controls.button>
                            </form>
                            @break

                        @case('unavailable')
                            <h2 class="text-size-main text-black mb-2">Esta invitación ya no está disponible</h2>
                            <p class="text-parrafo text-[#737373]">
                                Quien te invitó la canceló. Si crees que es un error, pídele que te invite de nuevo.
                            </p>
                            @break

                        @default
                            <h2 class="text-size-main text-black mb-2">La invitación venció</h2>
                            <p class="text-parrafo text-[#737373]">
                                Las invitaciones duran {{ \App\Services\CoadminService::INVITATION_DAYS }} días.
                                Pide a quien te invitó que te envíe una nueva.
                            </p>
                    @endswitch
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest-layout>
