@php
    /*
    | Landing de venta.
    |
    | Las plantillas, su precio y su vigencia salen de la base ($templates), no
    | escritos aquí: lo que el superadmin cambie en su panel se ve en esta
    | página sin tocarla. Lo único fijo es lo que la plataforma hace, que es lo
    | mismo para todas.
    */
    $desde = $templates->min('price');
    $vigencia = $templates->max('duration_days');

    $secciones = [
        '#plantillas' => 'Plantillas',
        '#incluye' => 'Qué incluye',
        '#como-funciona' => 'Cómo funciona',
        '#preguntas' => 'Preguntas',
    ];
@endphp

<x-layouts.landing>
    <x-landing.nav :links="$secciones" />

    <main>
        {{-- ============================================================
             Portada: una sola promesa y una sola acción.
             ============================================================ --}}
        <section class="mx-auto max-w-6xl px-6 pb-20 pt-16 text-center sm:pt-24 lg:px-8">
            <p class="text-size-small-heading uppercase tracking-[0.2em] text-gray">Invitaciones digitales de boda</p>

            <h1 class="mx-auto mt-6 max-w-3xl font-main text-size-hero leading-tight">
                Tu boda entera en una sola liga
            </h1>

            <p class="mx-auto mt-6 max-w-xl text-size-heading text-gray">
                Manda una invitación que se abre en el celular, confirma a tus invitados sola y te deja ver quién
                viene sin perseguir a nadie por WhatsApp.
            </p>

            <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="#plantillas" class="btn btn-main w-full rounded-full sm:w-auto">Ver plantillas</a>

                @if ($primera = $templates->first())
                    <a href="{{ route('templates.preview', $primera->slug) }}" target="_blank" rel="noopener"
                        class="btn btn-secondary w-full rounded-full sm:w-auto">Ver una invitación de ejemplo</a>
                @endif
            </div>

            @if ($desde)
                <p class="mt-6 text-size-small-heading text-gray">
                    Desde ${{ number_format($desde, 0) }} MXN · pago único, sin mensualidades
                </p>
            @endif
        </section>

        {{-- ============================================================
             Plantillas: el catálogo real, que es lo que se vende.
             ============================================================ --}}
        <section id="plantillas" class="scroll-mt-24 border-t border-black/5 bg-secondary py-20 sm:py-24">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="max-w-2xl">
                    <h2 class="font-main text-size-main">Elige tu diseño</h2>
                    <p class="mt-4 text-size-heading text-gray">
                        Cada plantilla trae su propio estilo y las mismas herramientas. Ábrelas y recórrelas completas
                        antes de decidir: lo que ves es lo que tus invitados van a recibir.
                    </p>
                </div>

                @if ($templates->isEmpty())
                    <div class="mt-12 rounded-2xl border border-dashed border-black/15 bg-white p-12 text-center">
                        <p class="text-size-heading">Estamos preparando nuevos diseños.</p>
                        <p class="mt-2 text-size-small-heading text-gray">Vuelve pronto o escríbenos y te avisamos.</p>
                    </div>
                @else
                    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($templates as $template)
                            <article class="flex flex-col rounded-2xl border border-black/5 bg-white p-7">
                                <h3 class="font-main text-size-title">{{ $template->name }}</h3>

                                <p class="mt-3 text-size-small-heading text-gray">
                                    Portada, itinerario, mesa de regalos, galería y confirmación de asistencia.
                                </p>

                                <p class="mt-6 text-size-title">
                                    ${{ number_format($template->price, 0) }}
                                    <span class="text-size-small-heading text-gray">MXN</span>
                                </p>

                                @if ($template->duration_days)
                                    <p class="mt-1 text-size-small-heading text-gray">
                                        Tu invitación queda en línea {{ $template->duration_days }} días después de la boda.
                                    </p>
                                @endif

                                <div class="mt-7 flex flex-col gap-2">
                                    <a href="{{ route('checkout.checkout-preview', $template->slug) }}"
                                        class="btn btn-main w-full rounded-full">Elegir esta</a>

                                    <a href="{{ route('templates.preview', $template->slug) }}" target="_blank" rel="noopener"
                                        class="btn btn-secondary w-full rounded-full">Ver demo</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- ============================================================
             Qué incluye: lo que la plataforma hace de verdad.
             ============================================================ --}}
        <section id="incluye" class="scroll-mt-24 py-20 sm:py-24">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="max-w-2xl">
                    <h2 class="font-main text-size-main">Todo va incluido</h2>
                    <p class="mt-4 text-size-heading text-gray">
                        No hay versiones ni extras: elijas la plantilla que elijas, tienes lo mismo.
                    </p>
                </div>

                <dl class="mt-12 grid gap-x-10 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['Confirmación de asistencia', 'Cada invitado tiene su propia liga, con los lugares que le apartaste. Responde desde ahí y tú lo ves al instante.'],
                        ['Lista de invitados', 'Quién confirmó, quién falta y cuántos lugares llevas apartados, siempre al día.'],
                        ['Invitaciones por WhatsApp', 'Manda la liga personal de cada quien desde el panel, sin copiar y pegar uno por uno.'],
                        ['Itinerario y mapas', 'Ceremonia, recepción y cada momento con su hora, su lugar y su liga a Google Maps.'],
                        ['Mesa de regalos', 'Tiendas y cuentas bancarias, con los datos listos para copiar de un toque.'],
                        ['Hoteles y transporte', 'Dónde quedarse, con qué tarifa, y las corridas si pones camión.'],
                        ['Galería de fotos', 'Sus fotos en grande, para que la invitación cuente algo antes de la boda.'],
                        ['Código de vestimenta', 'Lo que se espera de cada quien, con los colores sugeridos a la vista.'],
                        ['Preguntas frecuentes', 'Las dudas de siempre respondidas ahí mismo, para que no te las pregunten a ti.'],
                    ] as [$titulo, $texto])
                        <div>
                            <dt class="text-size-heading">{{ $titulo }}</dt>
                            <dd class="mt-2 text-size-small-heading leading-relaxed text-gray">{{ $texto }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        {{-- ============================================================
             Cómo funciona: los tres pasos reales de la compra.
             ============================================================ --}}
        <section id="como-funciona" class="scroll-mt-24 border-y border-black/5 bg-secondary py-20 sm:py-24">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <h2 class="max-w-2xl font-main text-size-main">Cómo funciona</h2>

                <ol class="mt-12 grid gap-10 sm:grid-cols-3">
                    @foreach ([
                        ['Elige tu plantilla', 'Pagas una vez, con tarjeta. En ese momento se crea tu cuenta.'],
                        ['Llena tu información', 'Un paso a la vez: sus nombres, la fecha, el itinerario, las fotos. Guardas cuando quieras y sigues después.'],
                        ['Comparte tu liga', 'Mandas la invitación y, conforme respondan, ves las confirmaciones en tu panel.'],
                    ] as $indice => [$titulo, $texto])
                        <li>
                            <span class="font-main text-size-title text-gray">0{{ $indice + 1 }}</span>
                            <h3 class="mt-3 text-size-heading">{{ $titulo }}</h3>
                            <p class="mt-2 text-size-small-heading leading-relaxed text-gray">{{ $texto }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- ============================================================
             Preguntas: lo que frena una compra.
             ============================================================ --}}
        <section id="preguntas" class="scroll-mt-24 py-20 sm:py-24">
            <div class="mx-auto max-w-3xl px-6 lg:px-8">
                <h2 class="font-main text-size-main">Preguntas</h2>

                <div class="mt-10 divide-y divide-black/10 border-y border-black/10" x-data="{ abierta: null }">
                    @foreach ([
                        ['¿Es un pago único?', 'Sí. Pagas una vez por tu invitación y no hay mensualidades ni cobros después.'],
                        ['¿Cuánto tiempo está en línea?', $vigencia ? "Hasta {$vigencia} días después de la fecha de tu boda, para que a tus invitados les siga sirviendo mientras la recuerdan." : 'Queda en línea durante toda la organización y un tiempo después de la boda.'],
                        ['¿Puedo cambiar cosas después de comprar?', 'Sí, cuando quieras. Entras a tu panel, cambias lo que necesites y tus invitados ven el cambio en la misma liga, sin tener que reenviarla.'],
                        ['¿Mis invitados necesitan instalar algo?', 'No. Es una página que se abre en el navegador del celular, como cualquier liga que mandas por WhatsApp.'],
                        ['¿Alguien más puede ayudarme a organizar?', 'Sí. Desde Configuración puedes invitar a un coadministrador para que entre al panel contigo.'],
                        ['¿Y si alguien no tiene su invitación personal?', 'Puedes dejar abierta una liga general para que confirme quien no recibió la suya, o cerrarla y que sólo confirme a quien tú invitaste.'],
                    ] as $indice => [$pregunta, $respuesta])
                        <div>
                            <h3>
                                <button type="button" class="flex w-full items-center justify-between gap-6 py-5 text-left"
                                    @click="abierta = abierta === {{ $indice }} ? null : {{ $indice }}"
                                    :aria-expanded="abierta === {{ $indice }}">
                                    <span class="text-size-heading">{{ $pregunta }}</span>

                                    {{-- El mismo signo gira 45° y se vuelve una equis. --}}
                                    <svg class="size-4 shrink-0 transition-transform duration-200"
                                        :class="{ 'rotate-45': abierta === {{ $indice }} }" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                                        <path d="M12 5v14M5 12h14" />
                                    </svg>
                                </button>
                            </h3>

                            <p x-show="abierta === {{ $indice }}" x-collapse x-cloak
                                class="pb-5 pr-10 text-size-small-heading leading-relaxed text-gray">{{ $respuesta }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ============================================================
             Cierre: la misma acción del principio, para quien llegó hasta aquí.
             ============================================================ --}}
        <section class="border-t border-black/5 bg-black py-20 text-center text-white sm:py-24">
            <div class="mx-auto max-w-2xl px-6 lg:px-8">
                <h2 class="font-main text-size-main">¿Empezamos?</h2>
                <p class="mt-4 text-size-heading text-white/70">
                    Elige tu plantilla hoy y empieza a llenarla esta misma tarde.
                </p>

                <a href="#plantillas" class="btn btn-main mt-10 rounded-full">Ver plantillas</a>
            </div>
        </section>
    </main>

    <footer class="border-t border-black/5 py-10">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 sm:flex-row lg:px-8">
            <span class="font-main text-size-heading">{{ config('app.name', 'Tamira') }}</span>

            <div class="flex items-center gap-6 text-size-small-heading text-gray">
                @auth
                    <a href="{{ route('panel') }}" class="transition-colors hover:text-black">Mi panel</a>
                @else
                    <a href="{{ route('login') }}" class="transition-colors hover:text-black">Iniciar sesión</a>
                @endauth

                <span>© {{ now()->year }}</span>
            </div>
        </div>
    </footer>
</x-layouts.landing>
