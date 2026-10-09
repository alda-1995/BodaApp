<?php

namespace App\Templates;

/**
 * Tipos de bloque del catálogo.
 *
 * El tipo es el puente entre el backend y el diseño: con él se busca la vista
 * dentro de la carpeta de cada plantilla
 * (components/templates/{plantilla}/blocks/{tipo}.blade.php) y, si esa plantilla
 * no trae el suyo, se usa el compartido.
 */
final class BlockType
{
    public const BANNER = 'banner';
    public const DESTINATION = 'destination';
    public const STORY = 'story';
    public const TIMELINE = 'timeline';
    public const DRESS_CODE = 'dress-code';
    public const GIFT_REGISTRY = 'gift-registry';
    public const GALLERY = 'gallery';
    public const FAQ = 'faq';
    public const CONTACT_FORM = 'contact-form';
    public const HOTELS = 'hotels';
    public const TRANSPORT = 'transport';
    public const COUNTDOWN = 'countdown';

    /**
     * Nombre legible de cada tipo, para el panel del superadmin.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::BANNER => 'Banner / portada',
            self::DESTINATION => 'Destino y cuenta regresiva',
            self::STORY => 'Historia',
            self::TIMELINE => 'Itinerario',
            self::DRESS_CODE => 'Código de vestimenta',
            self::GIFT_REGISTRY => 'Mesa de regalos',
            self::GALLERY => 'Galería',
            self::FAQ => 'Preguntas frecuentes',
            self::COUNTDOWN => 'Cuenta regresiva',
            self::CONTACT_FORM => 'Formulario de contacto',
            self::HOTELS => 'Hoteles recomendados',
            self::TRANSPORT => 'Transporte',
        ];
    }

    /** @return array<int, string> */
    public static function all(): array
    {
        return array_keys(self::labels());
    }

    public static function label(?string $type): string
    {
        return self::labels()[$type] ?? (string) $type;
    }

    public static function exists(?string $type): bool
    {
        return $type !== null && array_key_exists($type, self::labels());
    }
}
