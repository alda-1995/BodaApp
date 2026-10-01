<?php

namespace App\Services\Superadmin;

use App\Models\Event;
use App\Models\Order;
use App\Models\Rsvp;
use App\Models\Template;
use App\Models\User;
use App\Services\EventWizardService;
use Illuminate\Support\Collection;

/**
 * Los números del panel del superadmin.
 *
 * Vive aquí y no en el controlador porque son consultas con reglas —qué cuenta
 * como venta, qué es "por vencer"— y esas reglas se prueban solas.
 *
 * Dos de los bloques miran el periodo elegido (ventas y actividad) y dos son
 * una foto de hoy (el estado de las invitaciones y los pendientes).
 */
class DashboardService
{
    /** Cuántas filas se enseñan en cada lista del panel. */
    private const FILAS = 5;

    /** Días que miramos hacia adelante en los pendientes. */
    private const VENCE_PRONTO_DIAS = 7;

    public function __construct(private readonly EventWizardService $wizard)
    {
    }

    /**
     * Ventas del periodo, con su comparación contra el rango anterior.
     */
    public function sales(DashboardPeriod $period): array
    {
        $anterior = $period->previous();

        $ingresos = $this->income($period);
        $ingresosAnteriores = $this->income($anterior);

        $pagadas = $this->countOrders('completed', $period);

        return [
            'ingresos' => $ingresos,
            'ingresos_anteriores' => $ingresosAnteriores,
            'variacion' => $this->variation($ingresos, $ingresosAnteriores),
            'pagadas' => $pagadas,
            'pendientes' => $this->countOrders('pending', $period),
            'fallidas' => $this->countOrders('failed', $period),
            // Sin ventas no hay promedio que sacar, y dividir entre cero revienta.
            'ticket' => $pagadas > 0 ? $ingresos / $pagadas : 0.0,
            'acumulado' => (float) Order::where('status', 'completed')->sum('amount'),
            'top_plantillas' => $this->topTemplates($period),
        ];
    }

    /**
     * Estado de las invitaciones, hoy.
     *
     * Se cuentan en PHP a propósito: la regla de qué estado tiene una boda vive
     * en Event::statusKey(), y repetirla en SQL sería tener dos versiones que
     * se separan con el tiempo. Se traen sólo las cuatro columnas que decide.
     */
    public function invitations(): array
    {
        $eventos = Event::query()
            ->select(['id', 'is_active', 'event_date', 'expires_at'])
            ->get();

        $porEstado = $eventos
            ->groupBy(fn (Event $event) => $event->statusKey())
            ->map->count();

        return [
            'total' => $eventos->count(),
            'activas' => $porEstado[Event::STATUS_ACTIVE] ?? 0,
            'por_vencer' => $porEstado[Event::STATUS_EXPIRING] ?? 0,
            'vencidas' => $porEstado[Event::STATUS_EXPIRED] ?? 0,
            'sin_fecha' => $porEstado[Event::STATUS_PENDING_DATE] ?? 0,
            'suspendidas' => $porEstado[Event::STATUS_SUSPENDED] ?? 0,
            'sin_configurar' => $this->unconfigured()->count(),
            'avance' => $this->averageProgress(),
        ];
    }

    /**
     * Lo que pide acción: cosas concretas que alguien tiene que resolver.
     *
     * Cada una trae su liga a la pantalla donde se arregla; una lista sin eso
     * sólo sirve para preocuparse.
     */
    public function pending(): array
    {
        return array_values(array_filter([
            $this->expiringSoon(),
            $this->templatesWithoutPrice(),
            $this->unconfiguredPurchases(),
            $this->pastWeddingsStillActive(),
        ]));
    }

    /**
     * Qué pasó en el periodo: compras, altas y confirmaciones, en una sola
     * línea de tiempo.
     */
    public function activity(DashboardPeriod $period): Collection
    {
        $compras = Order::with(['user', 'template'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$period->from, $period->to])
            ->latest()
            ->limit(self::FILAS * 2)
            ->get()
            ->map(fn (Order $order) => [
                'tipo' => 'compra',
                'fecha' => $order->created_at,
                'titulo' => $order->user?->name ?? 'Alguien',
                'detalle' => 'compró ' . ($order->template?->name ?? 'una plantilla'),
                'monto' => (float) $order->amount,
            ]);

        $altas = User::whereBetween('created_at', [$period->from, $period->to])
            ->latest()
            ->limit(self::FILAS * 2)
            ->get()
            ->map(fn (User $user) => [
                'tipo' => 'alta',
                'fecha' => $user->created_at,
                'titulo' => $user->name,
                'detalle' => 'se registró',
                'monto' => null,
            ]);

        $confirmaciones = Rsvp::with('eventGuest.guest')
            ->whereBetween('created_at', [$period->from, $period->to])
            ->latest()
            ->limit(self::FILAS * 2)
            ->get()
            ->map(fn (Rsvp $rsvp) => [
                'tipo' => 'confirmacion',
                'fecha' => $rsvp->created_at,
                'titulo' => $rsvp->eventGuest?->guest?->name ?? 'Un invitado',
                'detalle' => $rsvp->attendance === 'declined' ? 'no podrá asistir' : 'confirmó su asistencia',
                'monto' => null,
            ]);

        return $compras
            ->concat($altas)
            ->concat($confirmaciones)
            ->sortByDesc('fecha')
            ->take(self::FILAS * 2)
            ->values();
    }

    /* =====================================================================
     | Piezas de los bloques
     * ===================================================================*/

    private function income(DashboardPeriod $period): float
    {
        return (float) Order::where('status', 'completed')
            ->whereBetween('created_at', [$period->from, $period->to])
            ->sum('amount');
    }

    private function countOrders(string $status, DashboardPeriod $period): int
    {
        return Order::where('status', $status)
            ->whereBetween('created_at', [$period->from, $period->to])
            ->count();
    }

    /** Cuánto cambió respecto del periodo anterior, en porcentaje. */
    private function variation(float $ahora, float $antes): ?float
    {
        // Sin base con qué comparar, un porcentaje no dice nada.
        if ($antes <= 0.0) {
            return null;
        }

        return (($ahora - $antes) / $antes) * 100;
    }

    private function topTemplates(DashboardPeriod $period): Collection
    {
        return Order::query()
            ->selectRaw('template_id, COUNT(*) as ventas, SUM(amount) as ingresos')
            ->with('template')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$period->from, $period->to])
            ->whereNotNull('template_id')
            ->groupBy('template_id')
            ->orderByDesc('ventas')
            ->limit(self::FILAS)
            ->get()
            ->map(fn ($fila) => [
                'nombre' => $fila->template?->name ?? 'Plantilla eliminada',
                'ventas' => (int) $fila->ventas,
                'ingresos' => (float) $fila->ingresos,
            ]);
    }

    /**
     * Invitaciones compradas de las que todavía no se capturó nada.
     *
     * Se filtra en SQL por el JSON vacío. Event::hasFeatures() es un poco más
     * estricto —también descarta un features lleno de valores vacíos—, pero
     * traer esa columna de todas las bodas para contarlas no compensa.
     */
    private function unconfigured()
    {
        return Event::query()
            ->where(fn ($q) => $q->whereNull('features')->orWhereJsonLength('features', 0));
    }

    /**
     * Avance promedio del wizard entre las invitaciones vigentes.
     *
     * Es la parte cara del panel: calcula el progreso boda por boda. Si algún
     * día esto pesa, es lo primero que hay que guardar en caché.
     */
    private function averageProgress(): int
    {
        $eventos = Event::available()->with('template')->get();

        if ($eventos->isEmpty()) {
            return 0;
        }

        $suma = $eventos->sum(fn (Event $event) => $this->wizard->getProgress($event)['percentage'] ?? 0);

        return (int) round($suma / $eventos->count());
    }

    private function expiringSoon(): ?array
    {
        $eventos = Event::with('user')
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(self::VENCE_PRONTO_DIAS)])
            ->orderBy('expires_at')
            ->limit(self::FILAS)
            ->get();

        if ($eventos->isEmpty()) {
            return null;
        }

        return [
            'titulo' => 'Invitaciones por vencer esta semana',
            'ver_todo' => route('admin.users.index', ['status' => Event::STATUS_EXPIRING]),
            'filas' => $eventos->map(fn (Event $event) => [
                'texto' => $event->user?->name ?? $event->custom_url ?? 'Sin dueño',
                'nota' => 'vence el ' . $event->expires_at->locale('es')->isoFormat('D [de] MMMM'),
                'url' => route('admin.users.index', ['search' => $event->user?->email]),
            ]),
        ];
    }

    private function templatesWithoutPrice(): ?array
    {
        $plantillas = Template::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('stripe_price_id')->orWhere('stripe_price_id', ''))
            ->limit(self::FILAS)
            ->get();

        if ($plantillas->isEmpty()) {
            return null;
        }

        return [
            'titulo' => 'Plantillas activas que no se pueden cobrar',
            'ver_todo' => route('templates.index'),
            'filas' => $plantillas->map(fn (Template $template) => [
                'texto' => $template->name,
                'nota' => 'sin precio de Stripe',
                'url' => route('templates.edit', $template->id),
            ]),
        ];
    }

    private function unconfiguredPurchases(): ?array
    {
        $eventos = $this->unconfigured()
            ->with('user')
            ->whereNotNull('order_id')
            ->latest()
            ->limit(self::FILAS)
            ->get();

        if ($eventos->isEmpty()) {
            return null;
        }

        return [
            'titulo' => 'Compradas y sin configurar',
            'ver_todo' => route('admin.users.index'),
            'filas' => $eventos->map(fn (Event $event) => [
                'texto' => $event->user?->name ?? 'Sin dueño',
                'nota' => 'comprada el ' . $event->created_at->locale('es')->isoFormat('D [de] MMMM'),
                'url' => route('admin.users.index', ['search' => $event->user?->email]),
            ]),
        ];
    }

    private function pastWeddingsStillActive(): ?array
    {
        $eventos = Event::with('user')
            ->where('is_active', true)
            ->whereNotNull('event_date')
            ->where('event_date', '<', now())
            ->orderBy('event_date')
            ->limit(self::FILAS)
            ->get();

        if ($eventos->isEmpty()) {
            return null;
        }

        return [
            'titulo' => 'Bodas que ya pasaron y siguen activas',
            'ver_todo' => route('admin.users.index'),
            'filas' => $eventos->map(fn (Event $event) => [
                'texto' => $event->user?->name ?? 'Sin dueño',
                'nota' => 'se casaron el ' . $event->event_date->locale('es')->isoFormat('D [de] MMMM'),
                'url' => route('admin.users.index', ['search' => $event->user?->email]),
            ]),
        ];
    }
}
