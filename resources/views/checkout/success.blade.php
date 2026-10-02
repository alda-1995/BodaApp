<x-layouts.guest-layout>
    <div class="min-h-screen pt-32 pb-20 md:py-16 bg-[#FAFAFA] md:flex md:items-center"
        x-data="checkoutPoller('{{ $sessionId }}')" x-init="initPoller()">
        <div class="container">
            <div class="rounded-xl bg-white p-6 md:p-8 lg:p-10 flex flex-col max-w-lg mx-auto">

                {{-- COMPLETADO --}}
                <template x-if="status === 'completed'">
                    <div class="flex flex-col">
                        <h2 class="text-size-subtitle text-center font-inter mb-4">¡Tu compra fue exitosa!</h2>

                        {{-- Quien ya tenía cuenta no crea contraseña: sólo entra. --}}
                        <p class="text-parrafo text-[#737373]" x-show="needsPassword">Estás a solo 2 pasos de comenzar:
                            define tu contraseña para acceder y completa la información de tu evento.</p>
                        <p class="text-parrafo text-[#737373]" x-show="!needsPassword" x-cloak>Tu invitación ya está en
                            tu cuenta. Entra con la contraseña que ya usas y completa la información de tu evento.</p>

                        <x-controls.button class="mt-6 md:mt-8" ::href="redirectUrl">
                            <span x-text="needsPassword ? 'Crear mi contraseña' : 'Entrar a mi cuenta'">Crear mi contraseña</span>
                        </x-controls.button>
                    </div>
                </template>

                {{-- PROCESANDO --}}
                <template x-if="status === 'pending'">
                    <div class="flex flex-col">
                        <h2 class="text-size-subtitle text-center font-inter mb-4">Procesando tu pago...</h2>
                        <p class="text-parrafo text-[#737373]">Estamos preparando tu plantilla y configurando tu cuenta.
                            Por favor no cierres esta página.</p>
                    </div>
                </template>

                {{-- RECHAZADO / FALLIDO --}}
                <template x-if="status === 'failed'">
                    <div class="flex flex-col text-center">
                        <h2 class="text-size-subtitle font-inter mb-4 text-red-600">No se pudo procesar el pago</h2>
                        <p class="text-parrafo text-[#737373]"
                            x-text="errorMessage || 'Tu tarjeta o método de pago fue rechazado. No te preocupes, no se ha realizado ningún cargo.'">
                        </p>
                        <x-controls.button class="mt-6 md:mt-8" href="{{ route('home') }}">
                            Regresar al inicio
                        </x-controls.button>
                    </div>
                </template>

                {{-- SESIÓN EXPIRADA --}}
                <template x-if="status === 'expired'">
                    <div class="flex flex-col text-center">
                        <h2 class="text-size-subtitle font-inter mb-4">La sesión de pago expiró</h2>
                        <p class="text-parrafo text-[#737373]">El tiempo para completar la compra ha finalizado. Puedes
                            seleccionar nuevamente tu invitación para continuar.</p>

                        <x-controls.button class="mt-6 md:mt-8" href="{{ route('home') }}">
                            Volver a las plantillas
                        </x-controls.button>
                    </div>
                </template>

                {{-- TIMEOUT (POLLING prolongado) --}}
                <template x-if="status === 'timeout'">
                    <div class="flex flex-col text-center">
                        <h2 class="text-size-subtitle font-inter mb-4">Tu pago se está procesando</h2>
                        <p class="text-parrafo text-[#737373]">Está tomando un poco más de lo normal. En cuanto se
                            confirme te enviaremos el acceso a tu correo electrónico.</p>
                        <x-controls.button class="mt-6 md:mt-8" href="{{ route('home') }}">
                            Regresar al inicio
                        </x-controls.button>
                    </div>
                </template>

            </div>
        </div>
    </div>
    <script>
        function checkoutPoller(sessionId) {
            return {
                status: 'pending',
                redirectUrl: '',
                needsPassword: true,
                errorMessage: '',
                attempts: 0,
                maxAttempts: 12,
                pollInterval: null,

                initPoller() {
                    this.checkOrderStatus();
                    this.pollInterval = setInterval(() => {
                        this.attempts++;
                        if (this.attempts >= this.maxAttempts) {
                            this.stopPolling();
                            if (this.status === 'pending') {
                                this.status = 'timeout';
                            }
                            return;
                        }
                        this.checkOrderStatus();
                    }, 2500);
                },

                stopPolling() {
                    if (this.pollInterval) {
                        clearInterval(this.pollInterval);
                        this.pollInterval = null;
                    }
                },

                async checkOrderStatus() {
                    try {
                        const response = await fetch(`/checkout/status/${sessionId}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) return;

                        const data = await response.json();

                        if (data.status === 'completed') {
                            this.stopPolling();
                            this.status = 'completed';
                            this.redirectUrl = data.redirect_url ?? '/';
                            this.needsPassword = data.needs_password ?? false;
                        } else if (data.status === 'failed' || data.status === 'expired') {
                            this.stopPolling();
                            this.status = data.status;
                            this.errorMessage = data.message ?? '';
                        }
                    } catch (error) {
                        console.error("Error consultando el estado del pedido:", error);
                    }
                }
            }
        }
    </script>
</x-layouts.guest-layout>