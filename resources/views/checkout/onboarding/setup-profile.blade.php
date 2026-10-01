<x-layouts.guest-layout>
    <div class="flex flex-col md:flex-row">
        <div class="hidden md:block md:w-1/2">
            <div class="h-full w-full bg-cover bg-center"
                style="background-image: url('{{ asset('images/auth/boda-auth.jpg') }}');"></div>
        </div>
        <div class="md:w-1/2">
            <div class="container">
                <div class="pt-32 pb-20 md:py-16 max-w-lg md:min-h-screen md:flex md:flex-col md:justify-center">
                    <form method="POST" action="{{ route('onboarding.profile.store') }}" novalidate
                        x-data="{
                            url: '',
                            loading: false,
                            async refresh() {
                                const partner1 = this.$root.querySelector('[name=partner_1_name]').value.trim();
                                const partner2 = this.$root.querySelector('[name=partner_2_name]').value.trim();

                                if (!partner1 || !partner2) {
                                    this.url = '';
                                    return;
                                }

                                this.loading = true;
                                try {
                                    const params = new URLSearchParams({
                                        partner_1_name: partner1,
                                        partner_2_name: partner2,
                                        email: @js($user->email),
                                    });
                                    const response = await fetch(@js(route('onboarding.url.preview')) + '?' + params, {
                                        headers: { Accept: 'application/json' },
                                    });
                                    if (response.ok) {
                                        this.url = (await response.json()).url || '';
                                    }
                                } catch (error) {
                                    // Sin conexión: se deja la última URL mostrada.
                                } finally {
                                    this.loading = false;
                                }
                            },
                        }"
                        x-init="refresh()"
                        @input.debounce.400ms="refresh()">
                        @csrf
                        <input type="hidden" name="email" value="{{ $user->email }}">
                        <p class="text-[#8C8C8C] text-size-small-heading mb-2">Paso 2 de 2 · Tus datos y el evento</p>
                        <h2 class="font-inter text-size-main text-black mb-4">¡Queremos conocerte!</h2>
                        
                        <div class="mb-4">
                            <x-controls.input 
                                type="text" 
                                name="name" 
                                placeholder="Tu nombre completo"
                                value="{{ old('name', $user->name ?? '') }}" 
                                required 
                                autofocus 
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <x-controls.input 
                                type="text" 
                                name="partner_1_name" 
                                placeholder="Nombre Pareja 1"
                                value="{{ old('partner_1_name') }}" 
                                required 
                            />
                            <x-controls.input 
                                type="text" 
                                name="partner_2_name" 
                                placeholder="Nombre Pareja 2"
                                value="{{ old('partner_2_name') }}"
                                required
                            />
                        </div>

                        {{-- Sólo lectura y sin name: muestra la URL pero no se envía ni se edita. --}}
                        <label class="flex flex-col input-primary mb-4">
                            <p class="label">URL de tu invitación</p>
                            <input
                                type="text"
                                id="invitation_url_preview"
                                readonly
                                tabindex="-1"
                                aria-describedby="invitation_url_help"
                                :value="url"
                                :class="loading && 'opacity-60'"
                                placeholder="{{ \App\Models\Event::invitationUrlFor('pareja-1-y-pareja-2') }}"
                                class="form-input bg-[#F5F5F5] text-[#737373] cursor-default select-all"
                            />
                        </label>
                        <p id="invitation_url_help" class="-mt-2 mb-4 text-size-small-heading text-[#8C8C8C]">
                            Se genera con los nombres de la pareja y se puede editar más adelante.
                        </p>

                        <div class="mt-6">
                            <x-controls.button type="submit">Finalizar y comenzar</x-controls.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest-layout>