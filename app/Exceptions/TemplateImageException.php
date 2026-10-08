<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Falló lo de la imagen de una plantilla, pero el resto sí se guardó.
 *
 * Existe para poder decirlo con esas palabras. Sin ella, el error del disco se
 * mezclaba con los de Stripe y el superadmin leía "Error al sincronizar con
 * Stripe" cuando Stripe había funcionado y lo único que falló fue la foto —y,
 * peor, creía que no se había guardado nada cuando la plantilla ya existía.
 *
 * El mensaje que lleva es para leerse en pantalla: dice qué sí quedó guardado y
 * qué hacer. El detalle técnico va al log, no a la cara del usuario.
 */
class TemplateImageException extends Exception
{
    public static function alGuardar(Throwable $previous): self
    {
        return new self(
            'Se guardaron los cambios de la plantilla, pero no pudimos subir la imagen. Vuelve a intentarlo.',
            0,
            $previous,
        );
    }

    public static function alCrear(Throwable $previous): self
    {
        return new self(
            'La plantilla se creó, pero no pudimos subir su imagen. Edítala para intentarlo de nuevo.',
            0,
            $previous,
        );
    }

    public static function alQuitar(Throwable $previous): self
    {
        return new self(
            'Se guardaron los cambios de la plantilla, pero no pudimos quitar la imagen. Vuelve a intentarlo.',
            0,
            $previous,
        );
    }
}
