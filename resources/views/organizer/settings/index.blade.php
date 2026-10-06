@php
    // Tras un error se muestran los datos enviados; si no, los guardados.
    $value = fn (string $key) => old($key, $values[$key] ?? null);

    $paletteColors = $palettes->mapWithKeys(fn ($palette) => [
        $palette->id => ['primary' => $palette->primary_color, 'secondary' => $palette->secondary_color],
    ]);

    $sectionTitle = 'text-size-small-heading font-medium text-black mb-4';
    $hint = 'text-size-small-heading text-[#8C8C8C]';
    $error = 'error text-red-500 text-size-small-heading block';
    // Casilla redonda del diseño: el input real queda oculto y el círculo lo refleja.
    $circle = 'flex size-5 shrink-0 items-center justify-center rounded-full border border-[#BFBFBF] bg-white text-white transition-colors peer-checked:border-[#29241C] peer-checked:bg-[#29241C] peer-focus-visible:ring-2 peer-focus-visible:ring-[#29241C]/30';
@endphp

<x-layouts.dashboard-layout>
    {{-- Una sola modal para todos: el botón de cada fila le pone su destino y su texto. --}}
    <x-modals.modal-confirm-delete
        id="removeCoadminModal"
        cancelId="cancelRemoveCoadmin"
        title="¿Quitar acceso?"
        confirmText="Sí, quitar acceso" />

    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-xl">
                <div class="mb-8">
                    <h2 class="text-size-title text-black mb-2">Configuración</h2>
                    <p class="text-parrafo text-[#737373]">Ajustes generales de tu evento y de tu cuenta.</p>
                </div>

                @unless ($event)
                    <div class="rounded-xl border border-[#F2F2F2] bg-white p-8 text-center">
                        <p class="text-parrafo text-[#737373]">Aún no tienes un evento para configurar.</p>
                    </div>
                @else
                    <form id="settings-form" method="POST" action="{{ route('organizer.settings.update') }}" novalidate
                        x-data="{
                            custom: @js((bool) $value('custom_colors')),
                            paletteId: @js((string) $value('palette_id')),
                            palettes: @js($paletteColors),
                            primary: @js($value('primary_color')),
                            secondary: @js($value('secondary_color')),
                            slug: @js($value('custom_url')),
                            urlChanged: false,
                            urlAvailable: true,
                            urlSuggestion: null,
                            // Mientras no toque la dirección, sigue a los nombres.
                            slugTouched: @js((bool) old('custom_url')),
                            choose(id) {
                                this.paletteId = String(id);
                                this.primary = this.palettes[id].primary;
                                this.secondary = this.palettes[id].secondary;
                            },
                            useSuggestion() {
                                this.slug = this.urlSuggestion;
                                this.refreshUrl();
                            },
                            async refreshUrl() {
                                const params = new URLSearchParams({
                                    partner_1_name: this.$refs.partner1.value.trim(),
                                    partner_2_name: this.$refs.partner2.value.trim(),
                                    custom_url: this.slugTouched ? this.slug : '',
                                });
                                try {
                                    const response = await fetch(@js(route('organizer.settings.url_preview')) + '?' + params, {
                                        headers: { Accept: 'application/json' },
                                    });
                                    if (response.ok) {
                                        const data = await response.json();
                                        if (!this.slugTouched && data.slug) this.slug = data.slug;
                                        this.urlAvailable = data.available !== false;
                                        this.urlSuggestion = data.suggestion || null;
                                        this.urlChanged = Boolean(data.changed);
                                    }
                                } catch (error) {
                                    // Sin conexión: se deja lo último que se mostró.
                                }
                            },
                        }">
                        @csrf
                        @method('PUT')

                        {{-- Información general --}}
                        <section class="mb-10">
                            <h3 class="{{ $sectionTitle }}">Información general</h3>

                            <x-controls.inputs.input label="Nombre de usuario" name="name" :value="$values['name']"
                                autocomplete="name" />

                            <p class="font-inter text-size-small-heading text-black mb-1.5">
                                Nombres para la dirección de la invitación
                            </p>
                            <p class="{{ $hint }} mb-2">
                                Nombres cortos, sin apellidos. Los nombres completos que verán tus invitados se
                                capturan en Información del evento.
                            </p>
                            <div @input.debounce.400ms="refreshUrl()">
                                <x-controls.inputs.input name="partner_1_name" :value="$values['partner_1_name']"
                                    placeholder="Nombre" aria-label="Nombre de la primera persona" x-ref="partner1" />
                                <x-controls.inputs.input name="partner_2_name" :value="$values['partner_2_name']"
                                    placeholder="Nombre" aria-label="Nombre de la segunda persona" x-ref="partner2" />
                            </div>

                            <label for="custom_url" class="block font-inter text-size-small-heading text-black mb-1.5">
                                Dirección de tu invitación
                            </label>
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-stretch">
                                <span class="flex items-center rounded-md border border-base-gray bg-[#F5F5F5] px-3 text-size-small-heading text-[#737373] sm:rounded-r-none sm:border-r-0">
                                    {{ rtrim(\App\Models\Event::invitationUrlFor(''), '/') }}/
                                </span>
                                <input id="custom_url" name="custom_url" type="text" maxlength="80"
                                    value="{{ $value('custom_url') }}"
                                    x-model="slug" @input.debounce.400ms="slugTouched = true; refreshUrl()"
                                    class="h-[50px] w-full rounded-md border px-4 font-inter text-size-small-heading text-black sm:rounded-l-none {{ $errors->has('custom_url') ? 'border-red-500' : 'border-base-gray' }}" />
                            </div>

                            @if ($event->custom_url && $event->isAvailable())
                                <p class="mt-1">
                                    <a href="{{ $event->invitationUrl() }}" target="_blank" rel="noopener"
                                        class="text-size-small-heading text-[#2563EB] hover:underline">
                                        Abrir mi invitación
                                    </a>
                                </p>
                            @endif

                            @error('custom_url')
                                <span class="{{ $error }} mt-1">{{ $message }}</span>
                            @else
                                <p class="{{ $hint }} mt-1" x-show="urlAvailable && !urlChanged">
                                    Se propone con los nombres de arriba; puedes escribir la que prefieras.
                                </p>
                                <p class="text-size-small-heading text-[#854D0E] mt-1" x-show="urlAvailable && urlChanged" x-cloak>
                                    La dirección cambiará al guardar. Los enlaces que ya enviaste seguirán funcionando.
                                </p>
                                <p class="text-size-small-heading text-[#8C2626] mt-1" x-show="!urlAvailable" x-cloak>
                                    Esa dirección ya está ocupada.
                                    <template x-if="urlSuggestion">
                                        <button type="button" class="underline" @click="useSuggestion()">
                                            Usar <span x-text="urlSuggestion"></span>
                                        </button>
                                    </template>
                                </p>
                            @enderror
                        </section>

                        {{--
                            Pagos: va aquí arriba, junto a los datos de la
                            cuenta, porque es información de la cuenta y no de
                            la boda. No tiene nada que guardar —es un enlace—,
                            pero se presenta como una sección más para que se
                            lea como parte de Configuración y no como un añadido.
                        --}}
                        <section class="mb-10">
                            <h3 class="{{ $sectionTitle }}">Pagos</h3>

                            <p class="{{ $hint }} mb-3">Lo que has comprado y en qué quedó cada pago.</p>

                            <a href="{{ route('organizer.orders.index') }}"
                                class="text-size-small-heading text-[#2563EB] hover:underline">Ver mis pagos</a>
                        </section>

                        {{-- Paleta de colores --}}
                        <section class="mb-10">
                            <h3 class="{{ $sectionTitle }}">Paleta de colores</h3>

                            @if ($palettes->isNotEmpty())
                                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                    @foreach ($palettes as $palette)
                                        <label class="cursor-pointer" :class="custom && 'opacity-50 cursor-not-allowed'">
                                            <input type="radio" name="palette_id" value="{{ $palette->id }}" class="peer sr-only"
                                                x-model="paletteId" :disabled="custom" @change="choose({{ $palette->id }})" />
                                            <span class="flex h-full flex-col gap-3 rounded-lg border border-[#EBEBEB] bg-white p-4 transition-colors peer-checked:border-[#29241C] peer-checked:border-2 peer-focus-visible:ring-2 peer-focus-visible:ring-[#29241C]/30"
                                                title="{{ $palette->name }}">
                                                <span class="flex gap-1.5" aria-hidden="true">
                                                    <span class="size-5 rounded-full border border-black/10" style="background-color: {{ $palette->primary_color }}"></span>
                                                    <span class="size-5 rounded-full border border-black/10" style="background-color: {{ $palette->secondary_color }}"></span>
                                                </span>
                                                <span class="sr-only">{{ $palette->name }}</span>
                                                <span class="text-size-small-heading"
                                                    :class="paletteId === '{{ $palette->id }}' && !custom ? 'text-black' : 'text-[#8C8C8C]'"
                                                    x-text="paletteId === '{{ $palette->id }}' && !custom ? 'Seleccionado' : 'Elegir'">
                                                    {{ (string) $value('palette_id') === (string) $palette->id ? 'Seleccionado' : 'Elegir' }}
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                            @error('palette_id')
                                <span class="{{ $error }} mb-3">{{ $message }}</span>
                            @enderror

                            <label class="mb-5 flex cursor-pointer items-center gap-3">
                                <input type="checkbox" name="custom_colors" value="1" class="peer sr-only" x-model="custom" />
                                <span class="{{ $circle }}" aria-hidden="true">
                                    <svg class="size-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                </span>
                                <span class="text-size-small-heading text-black">
                                    Personalizar mis propios colores en vez de usar una paleta predeterminada
                                </span>
                            </label>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @foreach (['primary' => 'Color primario', 'secondary' => 'Color secundario'] as $key => $label)
                                    <div>
                                        <label for="{{ $key }}_color" class="block font-inter text-size-small-heading text-black mb-1.5">{{ $label }}</label>
                                        <div class="relative">
                                            <input id="{{ $key }}_color" type="text" name="{{ $key }}_color" maxlength="7"
                                                x-model="{{ $key }}" :disabled="!custom"
                                                class="h-[50px] w-full rounded-md border border-base-gray bg-white pl-4 pr-12 font-inter text-size-small-heading uppercase text-black disabled:bg-[#F5F5F5] disabled:text-[#737373]" />
                                            <input type="color" x-model="{{ $key }}" :disabled="!custom" aria-label="{{ $label }}"
                                                class="absolute right-2 top-1/2 size-8 -translate-y-1/2 cursor-pointer rounded border-0 bg-transparent p-0 disabled:cursor-not-allowed" />
                                        </div>
                                        @error($key . '_color')
                                            <span class="{{ $error }} mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        {{-- Invitados y confirmación --}}
                        <section class="mb-10">
                            <h3 class="{{ $sectionTitle }}">Invitados y confirmación</h3>

                            <label class="mb-1 flex cursor-pointer items-center gap-3">
                                <input type="checkbox" name="allow_children" value="1" class="peer sr-only"
                                    @checked($value('allow_children')) />
                                <span class="{{ $circle }}" aria-hidden="true">
                                    <svg class="size-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                </span>
                                <span class="text-size-small-heading text-black">Tu boda admite niños</span>
                            </label>
                            <p class="{{ $hint }} mb-6">
                                Si desactivas esto, la pregunta sobre niños no aparecerá en el formulario de confirmación.
                            </p>

                            <x-controls.inputs.input type="number" min="0" max="20"
                                label="Máximo de acompañantes por invitado (link abierto)"
                                name="open_link_max_passes" :value="$values['open_link_max_passes']" />

                            {{-- Se ajustaba en el paso de la galería; vale para toda la boda. --}}
                            <label class="mb-1 flex cursor-pointer items-center gap-3">
                                <input type="checkbox" name="allow_guest_uploads" value="1" class="peer sr-only"
                                    @checked($value('allow_guest_uploads')) />
                                <span class="{{ $circle }}" aria-hidden="true">
                                    <svg class="size-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                </span>
                                <span class="text-size-small-heading text-black">Permitir que los invitados suban fotos</span>
                            </label>
                            <p class="{{ $hint }}">
                                Todavía no hace nada: la pantalla para que los invitados compartan sus fotos
                                está por construirse.
                            </p>
                        </section>

                        {{-- Coadministradores --}}
                        <section class="mb-10">
                            <h3 class="{{ $sectionTitle }}">Coadministradores</h3>

                            <ul class="mb-4 space-y-2">
                                <li class="flex items-center justify-between gap-4 rounded-lg border border-[#F2F2F2] bg-white px-4 py-3">
                                    <span class="min-w-0">
                                        <span class="block truncate text-size-small-heading text-black">{{ $user->name ?: $user->email }}</span>
                                        <span class="block truncate text-xs text-[#8C8C8C]">{{ $user->email }}</span>
                                    </span>
                                    <span class="shrink-0 text-size-small-heading text-[#8C8C8C]">Tú</span>
                                </li>

                                @foreach ($coadmins as $coadmin)
                                    <li class="flex items-center justify-between gap-4 rounded-lg border border-[#F2F2F2] bg-white px-4 py-3">
                                        <span class="min-w-0">
                                            @if ($coadmin->isAccepted())
                                                <span class="block truncate text-size-small-heading text-black">{{ $coadmin->user->name ?: $coadmin->email }}</span>
                                                <span class="block truncate text-xs text-[#8C8C8C]">{{ $coadmin->email }}</span>
                                            @else
                                                <span class="block truncate text-size-small-heading text-black">{{ $coadmin->email }}</span>
                                                <span class="block truncate text-xs text-[#854D0E]">Invitación pendiente</span>
                                            @endif
                                        </span>
                                        <button type="button"
                                            class="shrink-0 text-size-small-heading text-[#2563EB] hover:underline"
                                            onclick="confirmRemoveCoadmin(
                                                @js(route('organizer.settings.coadmins.destroy', $coadmin->id)),
                                                @js($coadmin->isAccepted()
                                                    ? $coadmin->email . ' dejará de tener acceso a la información de tu evento. Puedes volver a invitarla cuando quieras.'
                                                    : 'Se cancelará la invitación de ' . $coadmin->email . ' y su liga dejará de funcionar.')
                                            )">
                                            {{ $coadmin->isAccepted() ? 'Quitar acceso' : 'Cancelar invitación' }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>

                            @if ($canInviteMore)
                                {{-- Pertenece al formulario de invitación (abajo): no se envía con "Guardar cambios". --}}
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                                    <div class="flex-1">
                                        <input type="email" name="email" form="coadmin-invite-form" autocomplete="off"
                                            aria-label="Correo del coadministrador" placeholder="correo@ejemplo.com"
                                            value="{{ $errors->coadmin->any() ? old('email') : '' }}"
                                            class="h-[50px] w-full rounded-md border px-4 font-inter text-size-small-heading text-black {{ $errors->coadmin->has('email') ? 'border-red-500' : 'border-base-gray' }}" />
                                        @if ($errors->coadmin->has('email'))
                                            <span class="{{ $error }} mt-1">{{ $errors->coadmin->first('email') }}</span>
                                        @endif
                                    </div>
                                    <x-controls.button type="submit" variant="secondary" form="coadmin-invite-form" class="h-[50px] shrink-0">
                                        Invitar coadministrador
                                    </x-controls.button>
                                </div>
                            @else
                                <p class="{{ $hint }}">Llegaste al máximo de {{ $maxCoadmins }} coadministradores.</p>
                            @endif
                        </section>

                        <x-controls.button type="submit">Guardar cambios</x-controls.button>
                    </form>

                    {{-- Formulario aparte: un <form> no puede ir dentro de otro. --}}
                    <form id="coadmin-invite-form" method="POST" action="{{ route('organizer.settings.coadmins.store') }}" novalidate>
                        @csrf
                    </form>
                @endunless
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            function confirmRemoveCoadmin(url, message) {
                const modal = document.getElementById('removeCoadminModal');
                modal.querySelector('.js-modal-message').textContent = message;
                toggleModalDelete('removeCoadminModal', url);
            }

            document.getElementById('cancelRemoveCoadmin')?.addEventListener('click', (event) => {
                event.preventDefault();
                document.getElementById('removeCoadminModal')?.classList.add('hidden');
            });
        </script>
    @endpush
</x-layouts.dashboard-layout>
