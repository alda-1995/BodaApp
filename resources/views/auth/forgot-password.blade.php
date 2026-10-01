<x-layouts.guest-layout>
    @if (session('status'))
        <x-alerts.buzon-alert title="Revisa tu bandeja de entrada."
            message="Te enviamos un enlace para restablecer tu contraseña." />
    @endif
    <div class="min-h-screen pt-32 pb-20 md:py-16 flex flex-col md:justify-center bg-white">
        <div class="container">
            <div class="max-w-lg mx-auto">
                <h2 class="text-size-title font-inter text-center mb-4 text-black">Recuperar contraseña</h2>
                <form action="{{ route('password.email') }}" method="POST" novalidate>
                    @csrf
                    <x-controls.input type="email" name="email" icon="user" placeholder="Correo electrónico"
                        value="{{ old('email') }}" required autofocus />

                    <div class="flex flex-col items-start gap-4 mt-8">
                        <x-controls.button type="submit">
                            Enviar enlace
                        </x-controls.button>

                        <a href="{{ route('login') }}" class="btn btn-transparent-blue">
                            Volver al inicio de sesión
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.guest-layout>