<?php

namespace App\Services\Superadmin;

use Carbon\CarbonImmutable;

/**
 * El rango de fechas que mira el panel del superadmin.
 *
 * Sólo alcanza a lo que OCURRIÓ en ese tiempo —las ventas y la actividad—. El
 * estado de las invitaciones y los pendientes son una foto de hoy: preguntar
 * "cuántas están por vencer en marzo pasado" no significa nada.
 */
final class DashboardPeriod
{
    public const MES = 'mes';
    public const TRIMESTRE = 'trimestre';
    public const ANIO = 'anio';
    public const PERSONALIZADO = 'personalizado';

    private function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {
    }

    /** Las opciones del selector, en el orden en que se muestran. */
    public static function options(): array
    {
        return [
            self::MES => 'Este mes',
            self::TRIMESTRE => 'Este trimestre',
            self::ANIO => 'Este año',
            self::PERSONALIZADO => 'Personalizado',
        ];
    }

    /**
     * Arma el periodo a partir de lo que llega en la petición.
     *
     * Cualquier valor raro cae en el mes en curso: el panel siempre tiene algo
     * que enseñar y nunca revienta por un parámetro escrito a mano en la URL.
     */
    public static function fromRequest(?string $key, ?string $from = null, ?string $to = null): self
    {
        $hoy = CarbonImmutable::now();

        if ($key === self::PERSONALIZADO) {
            $desde = self::parse($from) ?? $hoy->startOfMonth();
            $hasta = self::parse($to) ?? $hoy;

            // Fechas al revés: se enderezan en vez de devolver un rango vacío.
            if ($desde->gt($hasta)) {
                [$desde, $hasta] = [$hasta, $desde];
            }

            return new self(
                self::PERSONALIZADO,
                $desde->locale('es')->isoFormat('D MMM YYYY') . ' – ' . $hasta->locale('es')->isoFormat('D MMM YYYY'),
                $desde->startOfDay(),
                $hasta->endOfDay(),
            );
        }

        return match ($key) {
            self::TRIMESTRE => new self($key, 'Este trimestre', $hoy->startOfQuarter(), $hoy->endOfQuarter()),
            self::ANIO => new self($key, 'Este año', $hoy->startOfYear(), $hoy->endOfYear()),
            default => new self(self::MES, 'Este mes', $hoy->startOfMonth(), $hoy->endOfMonth()),
        };
    }

    /** El mismo rango, corrido hacia atrás, para comparar contra el anterior. */
    public function previous(): self
    {
        $dias = $this->from->diffInDays($this->to) + 1;

        return new self(
            $this->key,
            'Periodo anterior',
            $this->from->subDays($dias),
            $this->from->subSecond(),
        );
    }

    public function isCustom(): bool
    {
        return $this->key === self::PERSONALIZADO;
    }

    /** Para volver a pintar los campos del formulario. */
    public function inputValue(string $which): string
    {
        return ($which === 'from' ? $this->from : $this->to)->format('Y-m-d');
    }

    private static function parse(?string $value): ?CarbonImmutable
    {
        if (!$value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
