<?php

namespace App\Services;

use App\DTOs\Event\EventDTO;
use App\Models\EventUrlRedirect;
use App\Models\User;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Event;
use Illuminate\Pagination\LengthAwarePaginator;

class EventService
{
    public function getPaginatedByUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Event::where('user_id', $userId)
            ->with(['template', 'order'])
            ->latest()
            ->paginate($perPage);
    }

    public function findByUserAndId(int $userId, int $eventId): ?Event
    {
        return Event::where('user_id', $userId)
            ->with('guests')
            ->where('id', $eventId)
            ->where('is_active', true)
            ->first();
    }

    public function findByUserId(int $userId): ?Event
    {
        return Event::where('user_id', $userId)->first();
    }

    public function createEvent(EventDTO $data): Event
    {
        try {
            return DB::transaction(function () use ($data) {

                $event = Event::create($data->toArray());

                if (!isset($event->id)) {
                    throw new Exception('La base de datos no devolvió un identificador de evento válido.');
                }

                return $event;
            }, 3);

        } catch (Exception $e) {
            Log::error('Error en creación de evento: ' . $e->getMessage(), [
                'data' => $data->toArray(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception("Error al procesar y crear el evento.");
        }
    }

    public function updateCustomUrl(Event $event, string $customUrl): ?Event
    {
        try {
            DB::transaction(function () use ($event, $customUrl) {
                $updated = $event->update([
                    'custom_url' => $customUrl,
                ]);

                if (!$updated) {
                    throw new Exception("No se pudo actualizar el custom_url para el evento ID {$event->id}.");
                }
            });

            return $event->refresh();

        } catch (Exception $e) {
            Log::error("Error al actualizar custom_url del evento para el usuario ID {$event->user_id}: " . $e->getMessage(), [
                'custom_url' => $customUrl,
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception("No fue posible actualizar la URL personalizada del evento.");
        }
    }

    /**
     * Slug único para la URL amigable a partir de los nombres de la pareja
     * (ana-y-luis, ana-y-luis-2...), generado con eloquent-sluggable.
     *
     * Si se pasa el evento, su propia URL no cuenta como duplicado: al reenviar el
     * registro conserva la que ya tenía en lugar de recibir un sufijo.
     */
    public function uniqueCustomUrl(string $partner1, string $partner2, ?Event $event = null): string
    {
        return $this->uniqueCustomUrlFrom($this->customUrlRoot($partner1, $partner2), $event);
    }

    /**
     * La misma URL si está libre; si no, la primera con sufijo que lo esté.
     */
    public function uniqueCustomUrlFrom(string $root, ?Event $event = null): string
    {
        $root = $root !== '' ? $root : 'boda';
        $candidate = $root;

        for ($suffix = 2; $this->customUrlTaken($candidate, $event); $suffix++) {
            $candidate = "{$root}-{$suffix}";
        }

        return $candidate;
    }

    /**
     * Ocupada = la usa otro evento o es la URL vieja de otro evento (esa sigue
     * redirigiendo a su dueño y no se puede reasignar).
     */
    public function customUrlTaken(string $customUrl, ?Event $event = null): bool
    {
        $ownId = $event?->exists ? $event->id : null;

        return Event::where('custom_url', $customUrl)
                ->when($ownId, fn ($query) => $query->whereKeyNot($ownId))
                ->exists()
            || EventUrlRedirect::where('custom_url', $customUrl)
                ->when($ownId, fn ($query) => $query->where('event_id', '!=', $ownId))
                ->exists();
    }

    /**
     * Guarda los nombres cortos de la URL y ajusta la dirección.
     *
     * Con $desiredUrl se respeta la que el organizador escribió; sin ella se
     * deriva de los nombres. Si los nombres no cambiaron, la URL se queda como
     * está (aunque se libere una con menos sufijo): los enlaces ya enviados no
     * deben moverse solos.
     */
    public function updateUrlNames(Event $event, string $partner1, string $partner2, ?string $desiredUrl = null): void
    {
        DB::transaction(function () use ($event, $partner1, $partner2, $desiredUrl) {
            $event->update([
                'url_partner_1' => $partner1,
                'url_partner_2' => $partner2,
            ]);

            $this->changeCustomUrl($event, $desiredUrl ?: $this->customUrlFor($event, $partner1, $partner2));
        });
    }

    /**
     * URL que le corresponde al evento con estos nombres: la actual si ya
     * coincide con ellos, o una nueva única.
     */
    public function customUrlFor(Event $event, string $partner1, string $partner2): string
    {
        $root = $this->customUrlRoot($partner1, $partner2);

        return $this->customUrlMatchesRoot($event->custom_url, $root)
            ? $event->custom_url
            : $this->uniqueCustomUrl($partner1, $partner2, $event);
    }

    /**
     * Cambia la URL amigable guardando la anterior para redirigir los enlaces que
     * ya se enviaron.
     */
    public function changeCustomUrl(Event $event, string $newUrl): void
    {
        if ($event->custom_url === $newUrl) {
            return;
        }

        DB::transaction(function () use ($event, $newUrl) {
            if ($event->custom_url) {
                $event->urlRedirects()->firstOrCreate(['custom_url' => $event->custom_url]);
            }

            // Si vuelve a una URL que ya tuvo, deja de ser una redirección.
            $event->urlRedirects()->where('custom_url', $newUrl)->delete();

            $event->update(['custom_url' => $newUrl]);
        });
    }

    /**
     * Evento al que perteneció una URL amigable anterior (para redirigir).
     */
    public function findByPreviousUrl(string $customUrl): ?Event
    {
        return EventUrlRedirect::where('custom_url', $customUrl)->first()?->event;
    }

    /**
     * Eventos de otras personas donde el usuario es coadministrador.
     */
    public function sharedEventsFor(User $user): Collection
    {
        return Event::whereHas('coadmins', fn ($query) => $query->accepted()->where('user_id', $user->id))
            ->with('user')
            ->orderByDesc('event_date')
            ->get();
    }

    /** URL base de los nombres, sin sufijo: "ana-y-luis". */
    private function customUrlRoot(string $partner1, string $partner2): string
    {
        $root = SlugService::createSlug(Event::class, 'custom_url', "{$partner1} y {$partner2}", ['unique' => false]);

        return $root !== '' ? $root : 'boda';
    }

    /** "ana-y-luis" y "ana-y-luis-3" corresponden a la base "ana-y-luis". */
    private function customUrlMatchesRoot(?string $customUrl, string $root): bool
    {
        return $customUrl !== null
            && ($customUrl === $root || preg_match('/^' . preg_quote($root, '/') . '-\d+$/', $customUrl) === 1);
    }


    public function disableEvent(Event $event): Event
    {
        try {
            $updated = $event->update(['is_active' => false]);

            if (!$updated) {
                throw new Exception('No se pudo mutar el estado is_active a falso.');
            }

            return $event;

        } catch (Exception $e) {
            Log::error("Error al desactivar el evento ID {$event->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception("No fue posible desactivar el evento en este momento.");
        }
    }

    /**
     * Desactiva el evento asociado a una orden.
     *
     * @param int $orderId
     * @return void
     * @throws Exception
     */
    public function deactivateByOrderId(int $orderId): void
    {
        try {
            $event = Event::where('order_id', $orderId)->first();
            if (!$event) {
                Log::warning("No se encontró ningún evento asociado a la orden para desactivar.", [
                    'order_id' => $orderId,
                ]);
                return;
            }

            $event->update([
                'is_active' => false,
            ]);

            Log::info("El evento {$event->id} ha sido desactivado exitosamente por reembolso.", [
                'event_id' => $event->id,
                'order_id' => $orderId,
            ]);

        } catch (Exception $e) {
            Log::error("Error de base de datos al desactivar el evento para la orden {$orderId}: " . $e->getMessage(), [
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}