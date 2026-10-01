<?php

namespace App\Notifications;

use App\Notifications\Contracts\NotificationChannel;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Resuelve los medios de envío declarados en config/notifications.php.
 *
 * Es el único punto que conoce las clases de los canales: el resto del sistema
 * trabaja con claves ('email', 'whatsapp').
 */
class ChannelManager
{
    /** @var array<string, NotificationChannel> */
    private array $resolved = [];

    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Canales listos para usarse: registrados y con credenciales configuradas.
     *
     * @return array<string, NotificationChannel>
     */
    public function available(): array
    {
        $available = [];

        foreach (array_keys($this->registered()) as $key) {
            $channel = $this->resolve($key);

            if ($channel->isConfigured()) {
                $available[$key] = $channel;
            }
        }

        return $available;
    }

    /** @return array<int, string> */
    public function availableKeys(): array
    {
        return array_keys($this->available());
    }

    /**
     * Etiquetas de todos los medios registrados, tengan o no credenciales: el
     * historial debe poder nombrar envíos hechos por un medio ya desactivado.
     *
     * @return array<string, string>
     */
    public function labels(): array
    {
        $labels = [];

        foreach (array_keys($this->registered()) as $key) {
            $labels[$key] = $this->resolve($key)->label();
        }

        return $labels;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->registered());
    }

    public function get(string $key): NotificationChannel
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException("El medio de envío '{$key}' no está registrado.");
        }

        return $this->resolve($key);
    }

    /** @return array<string, class-string<NotificationChannel>> */
    private function registered(): array
    {
        return (array) config('notifications.channels', []);
    }

    private function resolve(string $key): NotificationChannel
    {
        return $this->resolved[$key] ??= $this->container->make($this->registered()[$key]);
    }
}
