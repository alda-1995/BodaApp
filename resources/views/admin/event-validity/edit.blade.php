@php
    use App\Models\Event;

    $estados = [
        Event::STATUS_SUSPENDED => ['Suspendido', 'bg-gray-100 text-gray-700'],
        Event::STATUS_EXPIRED => ['Vencido', 'bg-[#F2E0E0] text-[#8C5926]'],
        Event::STATUS_PENDING_DATE => ['Falta fecha de boda', 'bg-[#E0EAF2] text-[#26548C]'],
        Event::STATUS_EXPIRING => ['Por vencer', 'bg-[#FEF9C3] text-[#854D0E]'],
        Event::STATUS_ACTIVE => ['Activo', 'bg-[#E0F2E3] text-[#267333]'],
    ];

    [$etiqueta, $clases] = $estados[$event->statusKey()];

    $hint = 'text-size-small-heading text-[#8C8C8C]';
    $fila = 'flex items-start justify-between gap-4 py-3 border-b border-[#F2F2F2] last:border-0';
    $circle = 'flex size-5 shrink-0 items-center justify-center rounded-full border border-[#BFBFBF] bg-white text-white transition-colors peer-checked:border-[#29241C] peer-checked:bg-[#29241C] peer-focus-visible:ring-2 peer-focus-visible:ring-[#29241C]/30';
@endphp

<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-xl">
                <div class="mb-8">
                    <a href="{{ route('admin.users.index') }}"
                        class="text-size-small-heading text-[#2563EB] hover:underline">← Volver a Usuarios</a>

                    <div class="mt-4 flex items-start justify-between gap-4">
                        <h2 class="text-size-title text-black">Vigencia de la invitación</h2>

                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs {{ $clases }}">{{ $etiqueta }}</span>
                    </div>

                    <p class="text-parrafo text-[#737373] mt-2">
                        {{ $event->user?->name ?: $event->user?->email }} ·
                        {{ $event->template?->name ?? 'Sin plantilla' }}
                    </p>
                </div>

                {{-- Lo que hay hoy, para no cambiar a ciegas. --}}
                <div class="rounded-xl border border-[#F2F2F2] bg-white px-4 py-2 mb-8">
                    <div class="{{ $fila }}">
                        <span class="{{ $hint }} shrink-0">Fecha de la boda</span>
                        <span class="text-size-small-heading text-black text-right">
                            {{ $event->event_date?->locale('es')->isoFormat('D [de] MMMM [de] YYYY') ?? 'Todavía sin capturar' }}
                        </span>
                    </div>

                    <div class="{{ $fila }}">
                        <span class="{{ $hint }} shrink-0">Días que da la plantilla</span>
                        <span class="text-size-small-heading text-black text-right">{{ $event->durationDays() }} días</span>
                    </div>

                    <div class="{{ $fila }}">
                        <span class="{{ $hint }} shrink-0">Dirección</span>
                        <span class="text-size-small-heading text-black text-right break-words min-w-0">
                            @if ($event->custom_url)
                                <a href="{{ $event->invitationUrl() }}" target="_blank" rel="noopener"
                                    class="text-[#2563EB] hover:underline">{{ $event->custom_url }}</a>
                            @else
                                Todavía sin dirección
                            @endif
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('superadmin.events.validity.update', $event->id) }}" novalidate>
                    @csrf
                    @method('PUT')

                    <label for="expires_at" class="block font-inter text-size-small-heading text-black mb-1.5">
                        Vence el
                    </label>
                    <input id="expires_at" name="expires_at" type="datetime-local"
                        value="{{ old('expires_at', $event->expires_at?->format('Y-m-d\TH:i')) }}"
                        class="h-[50px] w-full rounded-md border px-4 font-inter text-size-small-heading text-black {{ $errors->has('expires_at') ? 'border-red-500' : 'border-base-gray' }}" />

                    @error('expires_at')
                        <span class="error text-red-500 text-size-small-heading block mt-1">{{ $message }}</span>
                    @else
                        <p class="{{ $hint }} mt-1 mb-6">
                            Normalmente se calcula sola: fecha de la boda más los días de la plantilla.
                            Lo que escribas aquí manda sobre ese cálculo. Déjala vacía y no vencerá.
                        </p>
                    @enderror

                    {{--
                        El interruptor va aparte de la fecha porque son cosas
                        distintas: el comando por horas apaga esto al vencer, así
                        que extender la fecha de una invitación ya apagada no la
                        revive si no se vuelve a encender.
                    --}}
                    <label class="mb-1 flex cursor-pointer items-center gap-3">
                        <input type="checkbox" name="is_active" value="1" class="peer sr-only"
                            @checked(old('is_active', $event->is_active)) />
                        <span class="{{ $circle }}" aria-hidden="true">
                            <svg class="size-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </span>
                        <span class="text-size-small-heading text-black">Invitación encendida</span>
                    </label>
                    <p class="{{ $hint }} mb-6">
                        Apagada, la invitación responde "no disponible" a los invitados y el organizador
                        no puede editarla.
                    </p>

                    <x-controls.inputs.input label="Motivo (opcional)" name="reason" :value="old('reason')"
                        placeholder="Ej. La pareja pospuso la boda a septiembre" />

                    <p class="{{ $hint }} -mt-2 mb-6">Queda en la bitácora, junto a quién lo cambió.</p>

                    <x-controls.button type="submit">Guardar vigencia</x-controls.button>
                </form>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>
