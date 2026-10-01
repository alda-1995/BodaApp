<x-layouts.guest-layout>
    <div class="flex flex-col md:flex-row">
        <div class="hidden md:block md:w-1/2">
            <div class="h-full w-full bg-cover bg-center"
                style="background-image: url('{{ asset('images/auth/boda-auth.jpg') }}');"></div>
        </div>
        <div class="md:w-1/2">
            <div class="container">
                <div class="pt-32 pb-20 md:py-16 max-w-lg md:min-h-screen md:flex md:flex-col md:justify-center">
                    <form method="POST" action="{{ route('onboarding.password.store') }}" novalidate>
                        <p class="text-[#8C8C8C] text-size-small-heading mb-2">Paso 1 de 2 · Crea tu contraseña</p>
                        <h2 class="font-inter text-size-main text-black mb-4">Crea tu contraseña</h2>
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <input type="hidden" name="token" value="{{ $token ?? '' }}">

                        <x-controls.password type="password" name="password" placeholder="Contraseña"
                            value="{{ old('password') }}" required autofocus />
                        <x-controls.password name="password_confirmation" icon="lock"
                            placeholder="Confirmar nueva contraseña" required />
                        <div class="mt-4">
                            <x-controls.button type="submit">Guardar y continuar..</x-controls.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-layouts.guest-layout>