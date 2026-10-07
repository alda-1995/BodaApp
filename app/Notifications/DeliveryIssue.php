<?php

namespace App\Notifications;

use Throwable;

/**
 * Traduce el error técnico de un envío a algo que el organizador entienda.
 *
 * El mensaje del proveedor se guarda para soporte, pero nunca se muestra: puede
 * traer datos internos (credenciales, ids, rutas) y no le dice nada a quien no
 * es de sistemas. En pantalla sólo se ve el texto de aquí.
 */
class DeliveryIssue
{
    public const MISSING_CONTACT = 'missing_contact';
    public const INVALID_CONTACT = 'invalid_contact';
    public const REJECTED = 'rejected';
    public const RATE_LIMITED = 'rate_limited';
    public const CONNECTION = 'connection';
    public const EVENT_UNAVAILABLE = 'event_unavailable';
    public const UNKNOWN = 'unknown';

    /**
     * Pistas del mensaje del proveedor → tipo de falla. Se revisan en orden y
     * gana la primera que coincida; si ninguna lo hace, queda como desconocida.
     *
     * @var array<string, array<int, string>>
     */
    private const HINTS = [
        self::INVALID_CONTACT => [
            'invalid', 'not a valid', 'malformed', 'no such user', 'recipient address',
            'address rejected', 'unroutable', 'not a whatsapp', 'no existe',
        ],
        self::RATE_LIMITED => [
            'rate limit', 'too many', 'throttl', 'quota', 'exceeded', '429',
        ],
        self::CONNECTION => [
            'connection', 'timed out', 'timeout', 'could not resolve', 'network',
            'unreachable', 'temporarily unavailable', 'ssl', 'curl',
        ],
        self::REJECTED => [
            'rejected', 'blocked', 'blacklist', 'spam', 'unauthorized', 'forbidden',
            'not permitted', 'denied', 'no aceptó',
        ],
    ];

    /** Deduce el tipo de falla a partir de la excepción del proveedor. */
    public static function classify(Throwable $e): string
    {
        $message = mb_strtolower($e->getMessage());

        foreach (self::HINTS as $code => $hints) {
            foreach ($hints as $hint) {
                if (str_contains($message, $hint)) {
                    return $code;
                }
            }
        }

        return self::UNKNOWN;
    }

    /**
     * Qué pasó y qué puede hacer el organizador, en su idioma.
     *
     * @param string|null $code         tipo de falla guardado en el envío
     * @param string      $channelLabel nombre del medio: "Correo", "WhatsApp"
     */
    public static function message(?string $code, string $channelLabel): string
    {
        return match ($code) {
            self::MISSING_CONTACT => "El invitado no tiene un dato de contacto para {$channelLabel}. Agrégalo en su ficha y vuelve a enviarle el mensaje.",
            self::INVALID_CONTACT => "El dato de contacto de {$channelLabel} parece estar mal escrito. Revísalo en la ficha del invitado y vuelve a enviarle el mensaje.",
            self::RATE_LIMITED => 'Se enviaron muchos mensajes en poco tiempo. Espera unos minutos y vuelve a intentarlo.',
            self::CONNECTION => "No pudimos conectarnos con el servicio de {$channelLabel}. Suele resolverse solo: inténtalo de nuevo en unos minutos.",
            self::REJECTED => "El servicio de {$channelLabel} no aceptó el mensaje. Revisa el dato de contacto del invitado o inténtalo más tarde.",
            self::EVENT_UNAVAILABLE => 'Tu invitación dejó de estar activa antes de que saliera este mensaje, así que no se envió.',
            default => 'No pudimos entregar el mensaje. Inténtalo de nuevo y, si sigue igual, escríbenos para revisarlo.',
        };
    }
}
