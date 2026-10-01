@php
    use App\Services\NotificationService;

    $tabs = [
        NotificationService::FILTER_ALL => 'Todos',
        NotificationService::FILTER_PENDING => 'Pendientes de confirmar',
        NotificationService::FILTER_NOT_NOTIFIED => 'No invitados aún',
        NotificationService::FILTER_DELIVERIES => 'Estado de envíos',
    ];

    // Colores por estado en la pestaña de seguimiento.
    $statusTones = [
        'En espera' => 'bg-[#FEF9C3] text-[#854D0E]',
        'Reintentando' => 'bg-[#FFEDD5] text-[#9A3412]',
        'Error' => 'bg-[#F2E0E0] text-[#8C2626]',
        'Omitido' => 'bg-gray-100 text-gray-700',
    ];

    // Nombres para la vista previa y el selector "todos", sin volver a consultar.
    $guestNames = $invitations->mapWithKeys(fn ($invitation) => [$invitation->guest->id => $invitation->guest->name]);

    // Medios que no pueden alcanzar a cada invitado: "Sin correo", "Sin teléfono".
    $missingBy = $invitations->mapWithKeys(fn ($invitation) => [
        $invitation->guest->id => collect($channels)
            ->reject(fn ($channel) => $channel->canReach($invitation->guest))
            ->map(fn ($channel) => $channel->unreachableReason())
            ->values()
            ->all(),
    ]);
@endphp

<x-layouts.dashboard-layout>
    <section class="pt-32 pb-20 md:py-16 font-inter">
        <div class="container">
            <div class="max-w-xl mb-6">
                <h2 class="text-size-title text-black mb-2">Notificaciones</h2>
                {{-- Los medios se nombran desde la configuración: si se quita uno, el texto no miente. --}}
                <p class="text-parrafo text-[#737373]">
                    Envía invitaciones y recordatorios
                    @if ($channels)
                        por {{ collect($channels)->map(fn ($channel) => $channel->label())->implode(' o ') }}.
                    @else
                        a tus invitados.
                    @endif
                </p>
            </div>

            @if ($channels)
                <p class="mb-6 inline-block rounded-md bg-[#FCFDCA] px-4 py-3 text-parrafo text-[#54501A]">
                    Te quedan {{ $remaining }} mensajes disponibles este mes
                    ({{ collect($channels)->map(fn ($channel) => $channel->label())->implode(' + ') }}).
                </p>
            @else
                <p class="mb-6 inline-block rounded-md bg-[#F2E0E0] px-4 py-3 text-parrafo text-[#8C5926]">
                    No hay medios de envío configurados. Revisa las credenciales de correo y WhatsApp.
                </p>
            @endif

            {{-- Filtros --}}
            <div class="mb-6 flex flex-wrap gap-2">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('organizer.notifications.index', $key === NotificationService::FILTER_ALL ? [] : ['filtro' => $key]) }}"
                        @class([
                            'rounded-full px-4 py-2 text-parrafo transition-colors',
                            'bg-[#29241C] text-white' => $filter === $key,
                            'bg-white border border-[#EBEBEB] text-[#737373] hover:text-black' => $filter !== $key,
                        ])>
                        {{ $label }}
                        @if ($key === NotificationService::FILTER_DELIVERIES && $attentionCount > 0)
                            <span @class([
                                'ml-1 inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-xs',
                                'bg-white text-[#29241C]' => $filter === $key,
                                'bg-[#F2E0E0] text-[#8C2626]' => $filter !== $key,
                            ])>{{ $attentionCount }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            @if ($filter === NotificationService::FILTER_DELIVERIES)
                {{-- Seguimiento: lo que está en espera, falló o se omitió. --}}
                @php
                    $deliveryColumns = [
                        [
                            'label' => 'Invitado',
                            'width' => '200px',
                            'render' => fn ($delivery) => e($delivery->guest?->name ?? '—'),
                        ],
                        [
                            'label' => 'Medio',
                            'width' => '120px',
                            'render' => fn ($delivery) => e($channelLabels[$delivery->notification->channel] ?? ucfirst($delivery->notification->channel)),
                        ],
                        [
                            'label' => 'Estado',
                            'width' => '140px',
                            'isHtml' => true,
                            'render' => fn ($delivery) => '<span class="inline-flex items-center rounded-full px-3 py-1 text-xs '
                                . ($statusTones[$delivery->displayStatus()] ?? 'bg-gray-100 text-gray-700') . '">'
                                . e($delivery->displayStatus()) . '</span>',
                        ],
                        [
                            // Qué pasó, en español. El error técnico del proveedor
                            // se guarda para soporte pero no se muestra aquí.
                            'label' => '¿Qué pasó?',
                            'width' => '380px',
                            'isHtml' => true,
                            'render' => fn ($delivery) => '<span class="block whitespace-normal">'
                                . e($delivery->issueMessage($channelLabels[$delivery->notification->channel] ?? 'este medio'))
                                . '</span>',
                        ],
                        [
                            'label' => 'Última actualización',
                            'width' => '170px',
                            'render' => fn ($delivery) => e($delivery->updated_at->format('j M Y, H:i')),
                        ],
                    ];
                @endphp

                <p class="mb-4 text-parrafo text-[#737373]">
                    Los envíos en espera salen en unos momentos. Los que fallan se reintentan solos antes de marcarse como error.
                </p>

                <x-tables.table-crud :columns="$deliveryColumns" :items="$deliveries" :show-actions="false" />
            @elseif ($invitations->isEmpty())
                <div class="rounded-xl border border-[#F2F2F2] bg-white p-8 text-center">
                    <p class="text-parrafo text-[#737373] mb-4">
                        {{ $filter === NotificationService::FILTER_ALL
                            ? 'Aún no tienes invitados en tu lista.'
                            : 'No hay invitados en este filtro.' }}
                    </p>
                    <x-controls.button href="{{ route('organizer.guests.index') }}">Ir a invitados</x-controls.button>
                </div>
            @else
                <form method="POST" action="{{ route('organizer.notifications.send') }}" novalidate
                    x-data="{
                        selected: [],
                        message: @js(old('message', $defaultMessage)),
                        names: @js($guestNames),
                        get preview() {
                            const first = this.selected.length ? this.names[this.selected[0]] : 'Nombre del invitado';
                            return this.message.replace(new RegExp('\\{\\{\\s*nombre\\s*\\}\\}', 'g'), first);
                        },
                        toggleAll(checked) {
                            this.selected = checked ? Object.keys(this.names) : [];
                        },
                    }">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6 items-start">
                        {{-- Lista de invitados --}}
                        <div class="rounded-xl border border-[#F2F2F2] bg-white divide-y divide-[#F2F2F2]">
                            <label class="flex items-center gap-3 px-6 py-4 cursor-pointer">
                                <input type="checkbox" class="size-4 accent-[#29241C]"
                                    @change="toggleAll($event.target.checked)"
                                    :checked="selected.length === Object.keys(names).length && selected.length > 0" />
                                <span class="text-size-small-heading text-[#808080]">
                                    Seleccionar todos · <span x-text="selected.length"></span> seleccionados
                                </span>
                            </label>

                            @foreach ($invitations as $invitation)
                                @php $guest = $invitation->guest; @endphp
                                <label class="flex items-center gap-3 px-6 py-4 cursor-pointer">
                                    <input type="checkbox" name="guest_ids[]" value="{{ $guest->id }}"
                                        x-model="selected" class="size-4 accent-[#29241C]" />

                                    <span class="flex-1 min-w-0 text-parrafo text-[#1A1A1A] truncate">
                                        {{ $guest->name }}
                                        @foreach ($missingBy[$guest->id] as $reason)
                                            <span class="text-size-small-heading text-red-500">({{ $reason }})</span>
                                        @endforeach
                                    </span>

                                    @if ($guest->sent_notifications_count > 0)
                                        <span class="shrink-0 text-[#29241C]" title="Ya recibió un mensaje">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    @endif
                                </label>
                            @endforeach
                        </div>

                        {{-- Mensaje y vista previa --}}
                        <div class="rounded-xl border border-[#F2F2F2] bg-white p-6 space-y-4">
                            <h3 class="text-size-small-heading text-black">Vista previa del mensaje</h3>

                            <div class="space-y-1.5">
                                <label for="message" class="block text-size-small-heading text-[#808080]">
                                    Mensaje (puedes usar @{{nombre}})
                                </label>
                                <textarea id="message" name="message" rows="4" x-model="message"
                                    class="w-full resize-y rounded-md border border-base-gray bg-white px-3 py-2 font-inter text-size-small-heading text-black outline-none focus:border-black"></textarea>
                                @error('message')
                                    <span class="error text-red-500 text-size-small-heading block">{{ $message }}</span>
                                @enderror
                            </div>

                            <p class="rounded-md bg-[#E9F5EC] px-4 py-3 text-size-small-heading text-[#1A1A1A] whitespace-pre-line"
                                x-text="preview"></p>

                            {{-- Un botón por medio disponible: la lista sale de config/notifications.php --}}
                            <div class="flex flex-wrap gap-3 pt-2">
                                @foreach ($channels as $key => $channel)
                                    <x-controls.button type="submit" name="channel" value="{{ $key }}"
                                        :variant="$key === 'whatsapp' ? 'main' : 'secondary'"
                                        x-bind:disabled="selected.length === 0"
                                        class="disabled:opacity-50 disabled:cursor-not-allowed">
                                        {{ $channel->label() }}
                                    </x-controls.button>
                                @endforeach
                            </div>

                            @error('guest_ids')
                                <span class="error text-red-500 text-size-small-heading block">{{ $message }}</span>
                            @enderror
                            @error('channel')
                                <span class="error text-red-500 text-size-small-heading block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </section>
</x-layouts.dashboard-layout>
