<x-layouts.guest-layout>
    <div class="flex flex-col md:flex-row">
        <div class="hidden md:block md:w-1/2">
            <div class="h-full w-full bg-cover bg-center" style="background-image: url('{{ asset('images/auth/boda-register.jpg') }}');"></div>
        </div>
        <div class="md:w-1/2">
            <div class="container">
                <div class="pt-32 pb-20 md:py-16 max-w-lg md:min-h-screen md:flex md:flex-col md:justify-center">
                    <form method="POST" action="{{ route('register') }}" novalidate>
                        <h2 class="font-inter text-size-main text-black mb-4">Crear una cuenta</h2>
                        @csrf

                        <x-controls.input type="text" name="name" placeholder="Nombre completo"
                            value="{{ old('name') }}" required autofocus />

                        <x-controls.input type="email" name="email" placeholder="Correo electrónico"
                            value="{{ old('email') }}" required />

                        <x-controls.password type="password" name="password" placeholder="Contraseña" required />

                        <x-controls.password type="password" name="password_confirmation" placeholder="Confirmar contraseña" required />

                        <div class="mt-8 flex items-center justify-between">
                            <a href="{{ route('login') }}" class="btn btn-transparent-blue">¿Ya tienes cuenta? Inicia sesión</a>
                        </div>

                        <div class="mt-4">
                            <x-controls.button type="submit">Registrarse</x-controls.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest-layout>