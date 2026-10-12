<?php

namespace App\Services\Invitation;

use App\Models\Event;
use App\Templates\TemplateContext;
use App\Templates\TemplateRenderer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * La copia que se lleva el organizador cuando su invitación vence.
 *
 * Arma una carpeta suelta —index.html, una hoja de estilos, las tipografías y
 * las imágenes— que se abre con doble clic y se ve igual sin red y sin
 * servidor. Es un recuerdo, no una invitación que siga funcionando: lo que
 * necesitaba hablar con el servidor (la confirmación) no viaja, y lo que
 * necesitaba JavaScript se pinta ya resuelto.
 *
 * El HTML sale de la misma vista que la invitación de verdad, así que no se
 * pueden desincronizar: lo único que cambia es que el contexto va marcado como
 * copia estática y que después se reescriben las rutas.
 */
class StaticInvitationExport
{
    /**
     * Carpetas del paquete.
     *
     * La hoja de estilos va en la raíz, junto al index.html, y no en una
     * carpeta suya: un url() escrito dentro de una variable CSS se resuelve
     * contra la hoja y no contra la página, así que con la hoja en 'css/' las
     * texturas se buscaban en 'css/imagenes/'. Con las dos en el mismo sitio,
     * la ruta vale igual se lea desde donde se lea.
     */
    private const FUENTES = 'fuentes';
    private const IMAGENES = 'imagenes';

    /**
     * Atributos que sólo entiende Alpine. Sin él puestos estorban: 'x-cloak'
     * deja las respuestas de las preguntas escondidas para siempre.
     */
    private const ALPINE = [
        'x-data', 'x-show', 'x-cloak', 'x-collapse', 'x-text', 'x-html', 'x-init',
        'x-bind', 'x-on', 'x-transition', 'x-ref', 'x-for', 'x-if', 'x-model',
    ];

    /** Lo que se recoge mientras se reescribe: ruta en el paquete => de dónde sale. */
    private array $archivos = [];

    /** El manifiesto de Vite, leído una sola vez. */
    private ?array $manifiesto = null;

    public function __construct(private readonly TemplateRenderer $renderer)
    {
    }

    /**
     * Deja el .zip en un archivo temporal y devuelve su ruta.
     *
     * Quien lo pida se encarga de borrarlo; el controlador lo manda con
     * deleteFileAfterSend().
     */
    public function zipFor(Event $event): string
    {
        $this->archivos = [];

        // Una sola pasada por el renderizador: lo mismo alimenta al HTML y al CSS.
        $datos = $this->renderer->forEvent($event);

        $html = $this->html($event, $datos);
        $css = $this->css($datos);

        $zip = tempnam(sys_get_temp_dir(), 'invitacion-') ?: throw new RuntimeException(
            'No se pudo crear el archivo temporal de la invitación.',
        );

        $archivo = new ZipArchive();

        if ($archivo->open($zip, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo abrir el paquete de la invitación.');
        }

        $raiz = $this->folderName($event);

        $archivo->addFromString("{$raiz}/index.html", $html);
        $archivo->addFromString("{$raiz}/invitacion.css", $css);

        foreach ($this->archivos as $destino => $origen) {
            $contenido = $this->read($origen);

            // '!== null' y no a secas: un archivo vacío —el favicon lo está—
            // también tiene que viajar, o el HTML apunta a algo que no existe.
            if ($contenido !== null) {
                $archivo->addFromString("{$raiz}/{$destino}", $contenido);
            }
        }

        $archivo->close();

        return $zip;
    }

    /** El nombre de la carpeta y del archivo: "invitacion-ana-y-luis". */
    public function folderName(Event $event): string
    {
        $nombres = trim((string) (
            ($event->features['general']['name_wife'] ?? '') . ' y ' .
            ($event->features['general']['name_husband'] ?? '')
        ), ' y ');

        return 'invitacion-' . (Str::slug($nombres) ?: $event->custom_url ?: 'tamira');
    }

    /* ---------------------------------------------------------------------
     | El HTML
     * -------------------------------------------------------------------*/

    /** @param array<string, mixed> $datos lo que entrega TemplateRenderer::forEvent() */
    private function html(Event $event, array $datos): string
    {
        // Sin servidor no hay a quién confirmarle: el bloque no viaja.
        unset($datos['sections']['rsvp']);

        $datos['context'] = $this->staticContext($datos['context']);

        $html = View::make($event->template->view_path, $datos)->render();

        return $this->rewrite($this->withoutAlpine($html));
    }

    /** El mismo contexto, marcado como copia estática. */
    private function staticContext(TemplateContext $context): TemplateContext
    {
        return new TemplateContext(
            wifeName: $context->wifeName,
            husbandName: $context->husbandName,
            eventDate: $context->eventDate,
            parents: $context->parents,
            theme: $context->theme,
            guest: null,
            isDemo: false,
            rsvpUrl: null,
            isOrganizerPreview: false,
            isStaticExport: true,
        );
    }

    /**
     * Quita los atributos de Alpine y los botones que ya no abren nada.
     *
     * Se van con una expresión regular y no con un parser de HTML porque sólo
     * hay que borrar atributos sueltos: montar un DOM completo para esto
     * reformatearía toda la página de paso.
     */
    private function withoutAlpine(string $html): string
    {
        $nombres = implode('|', array_map('preg_quote', self::ALPINE));

        // x-show, x-on:click, :aria-expanded, @click... con o sin valor.
        return (string) preg_replace(
            '/\s(?:(?:' . $nombres . ')(?::[\w.-]+)?|[:@][\w.-]+)(?:="[^"]*"|=\'[^\']*\')?/i',
            '',
            $html,
        );
    }

    /**
     * Cambia cada dirección por su archivo dentro del paquete y lo apunta para
     * copiarlo después.
     *
     * Entran las fotos del organizador (storage), las imágenes del diseño
     * (public) y lo que viaje escrito en un style="--var: url(...)".
     */
    private function rewrite(string $html): string
    {
        return (string) preg_replace_callback(
            '/(src="|href="|url\(&#039;|url\(\'|url\()([^"\')]+)/i',
            function (array $m) {
                $local = $this->collect($m[2], self::IMAGENES);

                return $local ? $m[1] . $local : $m[0];
            },
            $html,
        );
    }

    /* ---------------------------------------------------------------------
     | La hoja de estilos
     * -------------------------------------------------------------------*/

    /**
     * Una sola hoja: las de la plantilla, las tipografías y los retoques de la
     * copia, en ese orden.
     */
    /** @param array<string, mixed> $datos lo que entrega TemplateRenderer::forEvent() */
    private function css(array $datos): string
    {
        $css = '';

        // Primero las tipografías, que es donde van las @font-face.
        foreach ($datos['fonts'] ?? [] as $fuente) {
            $css .= $this->googleFonts($fuente) . "\n";
        }

        $hojas = array_merge(
            ['resources/css/templates/shared/template.css'],
            $datos['assets'] ?? [],
        );

        foreach ($hojas as $entrada) {
            if ($archivo = $this->builtFile($entrada, 'css')) {
                $css .= $this->rewriteCss((string) file_get_contents($archivo)) . "\n";
            }
        }

        return $css . $this->staticSheet();
    }

    /**
     * El CSS de Google Fonts con sus tipografías dentro del paquete.
     *
     * Se pide con un navegador moderno en el User-Agent: así responde con
     * woff2, que es un archivo por familia y no uno por subconjunto.
     */
    private function googleFonts(string $url): string
    {
        $respuesta = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                . '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ])->timeout(20)->get($url);

        if (!$respuesta->successful()) {
            return '';
        }

        return $this->rewriteCss($respuesta->body());
    }

    /** Cada url() del CSS pasa a apuntar a la carpeta de al lado. */
    private function rewriteCss(string $css): string
    {
        return (string) preg_replace_callback(
            '/url\((["\']?)([^"\')]+)\1\)/i',
            function (array $m) {
                $local = $this->collect($m[2], $this->cssFolder($m[2]));

                return $local ? "url('{$local}')" : $m[0];
            },
            $css,
        );
    }

    private function cssFolder(string $url): string
    {
        return preg_match('/\.(woff2?|ttf|otf|eot)(\?|$)/i', $url) ? self::FUENTES : self::IMAGENES;
    }

    /**
     * Los retoques propios de la copia.
     *
     * Lo que sin JavaScript quedaría escondido se enseña, y lo que ya no hace
     * nada deja de parecer que se puede tocar.
     */
    private function staticSheet(): string
    {
        return <<<'CSS'

        /* --- Retoques de la copia descargable ------------------------------
         * Esta página no lleva JavaScript, así que lo que Alpine mostraba al
         * hacer clic se muestra desde el principio, y lo que era un botón deja
         * de anunciarse como tal.
         */
        [x-cloak] { display: revert !important; }

        .tc-faq__respuesta,
        .td-faq__answer,
        .te-faq__answer { display: block !important; height: auto !important; }

        .tc-faq__cabecera,
        .tc-faq__signo { cursor: default; }

        .tc-faq__signo { display: none; }
        CSS;
    }

    /* ---------------------------------------------------------------------
     | Archivos
     * -------------------------------------------------------------------*/

    /**
     * Apunta una dirección para copiarla y devuelve su ruta en el paquete.
     *
     * Devuelve null para lo que no se puede empaquetar —un mailto, un ancla, el
     * mapa de Google—, y entonces quien llama deja la dirección como estaba.
     */
    private function collect(string $url, string $carpeta): ?string
    {
        $url = trim(html_entity_decode($url));

        if ($url === '' || !$this->packable($url)) {
            return null;
        }

        $origen = $this->source($url);

        if ($origen === null) {
            return null;
        }

        $nombre = $this->fileName($url);
        $destino = "{$carpeta}/{$nombre}";

        $this->archivos[$destino] = $origen;

        return $destino;
    }

    /** Lo que vale la pena guardar: una imagen, una tipografía. */
    private function packable(string $url): bool
    {
        if (preg_match('/^(data:|mailto:|tel:|#|javascript:)/i', $url)) {
            return false;
        }

        return (bool) preg_match('/\.(png|jpe?g|gif|webp|avif|svg|ico|woff2?|ttf|otf)(\?|$)/i', $url);
    }

    /**
     * De dónde sale el archivo: una ruta del disco o una dirección que bajar.
     *
     * Las fotos del organizador viven en storage/app/public y las imágenes del
     * diseño en public/; las dos se sirven desde la misma dirección, así que se
     * distinguen por el tramo /storage/.
     */
    private function source(string $url): ?string
    {
        if (preg_match('#^https?://#i', $url) && !Str::startsWith($url, (string) config('app.url'))) {
            return $url;
        }

        $ruta = (string) parse_url($url, PHP_URL_PATH);

        if ($ruta === '') {
            return null;
        }

        $ruta = '/' . ltrim($ruta, '/');

        /*
        | Las fotos del organizador se piden por el disco y no por una ruta
        | escrita a mano: así valen igual aunque 'public' apunte a otro sitio,
        | y las pruebas pueden montar un disco falso.
        */
        $archivo = Str::startsWith($ruta, '/storage/')
            ? Storage::disk('public')->path(ltrim(Str::after($ruta, '/storage/'), '/'))
            : public_path(ltrim($ruta, '/'));

        return is_file($archivo) ? $archivo : null;
    }

    /** Un nombre que no choque: el del archivo, con el camino pegado delante. */
    private function fileName(string $url): string
    {
        $ruta = (string) (parse_url($url, PHP_URL_PATH) ?: $url);
        $nombre = basename($ruta);
        $extension = pathinfo($nombre, PATHINFO_EXTENSION);

        return Str::slug(pathinfo($nombre, PATHINFO_FILENAME) . '-' . substr(md5($url), 0, 6))
            . ($extension ? '.' . strtolower($extension) : '');
    }

    private function read(string $origen): ?string
    {
        if (preg_match('#^https?://#i', $origen)) {
            $respuesta = Http::timeout(20)->get($origen);

            return $respuesta->successful() ? $respuesta->body() : null;
        }

        return is_file($origen) ? (string) file_get_contents($origen) : null;
    }

    /** El archivo que Vite compiló para una entrada del manifiesto. */
    private function builtFile(string $entrada, string $extension): ?string
    {
        if ($this->manifiesto === null) {
            $ruta = public_path('build/manifest.json');
            $this->manifiesto = is_file($ruta)
                ? (array) json_decode((string) file_get_contents($ruta), true)
                : [];
        }

        $salida = $this->manifiesto[$entrada]['file'] ?? null;

        if (!is_string($salida) || !Str::endsWith($salida, ".{$extension}")) {
            return null;
        }

        $archivo = public_path('build/' . $salida);

        return is_file($archivo) ? $archivo : null;
    }
}
