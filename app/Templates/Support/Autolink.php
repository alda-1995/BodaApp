<?php

namespace App\Templates\Support;

/**
 * Convierte en enlaces los correos, teléfonos y ligas que el organizador
 * escribió dentro de un texto libre (por ejemplo, la respuesta de una pregunta
 * frecuente), sin pedirle campos aparte ni dejarlo escribir HTML.
 *
 * Todo el texto se escapa; lo único que se agrega son las etiquetas <a>.
 */
class Autolink
{
    /** Un teléfono creíble trae entre 8 y 15 dígitos (México: 10). */
    private const MIN_DIGITS = 8;
    private const MAX_DIGITS = 15;

    private const PATTERN = '#(?P<url>https?://[^\s<]+|www\.[^\s<]+)'
        . '|(?P<email>[\w.+-]+@[\w-]+\.[\w.-]*\w)'
        . '|(?P<phone>\+?\d[\d\s().-]{6,}\d)#u';

    public static function toHtml(string $text): string
    {
        preg_match_all(
            self::PATTERN,
            $text,
            $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL
        );

        $html = '';
        $cursor = 0;

        foreach ($matches as $match) {
            [$value, $offset] = $match[0];

            // Lo que hay entre enlace y enlace es texto plano.
            $html .= e(substr($text, $cursor, $offset - $cursor));
            $html .= self::render($match, (string) $value);

            $cursor = $offset + strlen((string) $value);
        }

        return nl2br($html . e(substr($text, $cursor)));
    }

    private static function render(array $match, string $value): string
    {
        if (($match['url'][0] ?? null) !== null) {
            $href = str_starts_with($value, 'www.') ? "https://{$value}" : $value;

            return self::anchor($href, $value, external: true);
        }

        if (($match['email'][0] ?? null) !== null) {
            return self::anchor("mailto:{$value}", $value);
        }

        $digits = (string) preg_replace('/\D/', '', $value);
        $length = strlen($digits);

        // Un número corto (o kilométrico) casi siempre es una cifra, no un teléfono.
        if ($length < self::MIN_DIGITS || $length > self::MAX_DIGITS) {
            return e($value);
        }

        $prefix = str_starts_with($value, '+') ? '+' : '';

        return self::anchor("tel:{$prefix}{$digits}", $value);
    }

    private static function anchor(string $href, string $label, bool $external = false): string
    {
        $attributes = $external ? ' target="_blank" rel="noopener"' : '';

        return '<a href="' . e($href) . '"' . $attributes . '>' . e($label) . '</a>';
    }
}
