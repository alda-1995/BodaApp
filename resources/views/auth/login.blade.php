<x-layouts.guest-layout>
    <div class="flex flex-col md:flex-row">
        <div class="hidden md:block md:w-1/2">
            <div class="h-full w-full bg-cover bg-center"
                style="background-image: url('{{ asset('images/auth/boda-auth.jpg') }}');"></div>
        </div>
        <div class="md:w-1/2">
            <div class="container">
                <div class="pt-32 pb-20 md:py-16 max-w-lg md:min-h-screen md:flex md:flex-col md:justify-center">
                    <form method="POST" action="{{ route('login') }}" novalidate>
                        <h2 class="font-inter text-size-main text-black mb-4">Iniciar sesión</h2>
                        @csrf
                        <x-controls.input type="email" name="email" placeholder="Correo electrónico"
                            value="{{ old('email', request('email')) }}" required autofocus />
                        <x-controls.password type="password" name="password" placeholder="Contraseña"
                            value="{{ old('password') }}" required autofocus />
                        <div class="block mt-8">
                            <x-controls.checkbox id="remember_me" name="remember" label="{{ __('Recordarme') }}" />
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('password.request') }}" class="btn btn-transparent-blue">¿Olvidaste tu
                                contraseña?</a>
                        </div>
                        <div class="mt-4">
                            <x-controls.button type="submit">Iniciar sesión</x-controls.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-layouts.guest-layout>