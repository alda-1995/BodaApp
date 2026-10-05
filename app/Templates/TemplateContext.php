<?php

namespace App\Templates;

use Carbon\Carbon;

/**
 * Datos que cualquier bloque puede necesitar: la pareja, la fecha, el invitado
 * que abrió la liga y la paleta elegida.
 */
final class TemplateContext
{
    public function __construct(
        public readonly string $wifeName,
        public readonly string $husbandName,
        public readonly ?Carbon $eventDate,
        public readonly ?string $parents,
        /** ['primary' => '#…', 'secondary' => '#…', 'accent' => '#…'] */
        public readonly array $theme,
        /** Invitado de la liga personal: name, max_passes, uuid, has_confirmed. */
        public readonly ?object $guest = null,
        public readonly bool $isDemo = false,
        /** A dónde manda el formulario de confirmación; null en la vista previa. */
        public readonly ?string $rsvpUrl = null,
    ) {
    }

    /**
     * Si quien abrió la invitación llegó por su liga personal.
     *
     * Sin ella no hay a quién apuntar la respuesta, así que sólo puede
     * confirmar si el organizador dejó abierta la liga general.
     */
    public function hasPersonalInvitation(): bool
    {
        return filled($this->guest?->uuid);
    }

    public function coupleNames(string $separator = ' & '): string
    {
        return trim(implode($separator, array_filter([$this->wifeName, $this->husbandName])));
    }

    /** La fecha en español: "12 de octubre de 2027". */
    public function formattedDate(): ?string
    {
        return $this->eventDate?->locale('es')->isoFormat('D \d\e MMMM \d\e YYYY');
    }

    /**
     * La fecha con el día de la semana, en versales: "SÁBADO · 21 DE MARZO · 2026".
     *
     * La portada y el pie de "Boda Editorial" la escriben igual, así que vive
     * aquí y no en cada bloque: son la misma fecha y no pueden verse distintas.
     */
    public function formattedDateLong(): ?string
    {
        $fecha = $this->eventDate?->locale('es')->isoFormat('dddd · D [DE] MMMM · YYYY');

        return $fecha === null ? null : mb_strtoupper($fecha);
    }

    /**
     * La fecha en números: "24 — 10 — 2026".
     *
     * La usa "Boda Destino", que escribe la fecha como un boleto. El separador
     * se pide porque su portada la pone con raya larga y su pie con guion.
     */
    public function formattedDateNumeric(string $separator = ' — '): ?string
    {
        return $this->eventDate?->isoFormat("DD[{$separator}]MM[{$separator}]YYYY");
    }

    /** La hora corta ("3:30 pm"); en español saldría "3:30 p. m.". */
    public function formattedTime(): ?string
    {
        return $this->eventDate?->locale('en')->isoFormat('h:mm a');
    }

    /**
     * Paleta como variables CSS. Las hojas de estilo de las plantillas se
     * escriben contra estos nombres, así el mismo diseño sirve para cualquier
     * pareja sin recompilar nada.
     */
    public function cssVariables(): string
    {
        $variables = array_filter([
            '--color-brand' => $this->theme['primary'] ?? null,
            '--color-brand-soft' => $this->theme['secondary'] ?? null,
            '--color-brand-contrast' => $this->theme['accent'] ?? null,
        ]);

        return collect($variables)
            ->map(fn ($value, $name) => "{$name}: {$value};")
            ->implode(' ');
    }
}
