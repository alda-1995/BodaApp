<?php

namespace Tests\Feature\Invitation;

use App\Models\Event;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Services\Invitation\StaticInvitationExport;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

/**
 * La copia que se lleva el organizador cuando su invitación vence.
 *
 * Es una carpeta suelta que se abre con doble clic: nada de servidor, nada de
 * JavaScript y nada que apunte a internet.
 */
class StaticInvitationExportTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW = 'build-templates.template-clasica.index';

    private User $organizer;
    private Event $event;

    /** Seguro: RefreshDatabase vacía la BD; aborta si no es una BD *_test. */
    protected function beforeRefreshingDatabase()
    {
        $database = (string) config('database.connections.' . config('database.default') . '.database');

        if (!str_ends_with($database, '_test')) {
            throw new RuntimeException("Abortado: RefreshDatabase iba a vaciar '{$database}'.");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndAdminSeeder::class);

        $this->organizer = User::factory()->create();
        $this->organizer->roles()->attach(Role::named('organizer')->id);

        $this->event = Event::factory()->create([
            'user_id' => $this->organizer->id,
            'template_id' => Template::factory()->create(['view_path' => self::VIEW])->id,
            'custom_url' => 'zoe-y-harry',
            'event_date' => '2024-05-18 17:00:00',
            'expires_at' => '2024-06-08 17:00:00',
            'is_active' => true,
            'features' => $this->features(),
        ]);
    }

    /* ---------------------------------------------------------------------
     | Quién puede descargarla
     * -------------------------------------------------------------------*/

    public function test_el_organizador_descarga_su_invitacion_vencida(): void
    {
        $this->actingAs($this->organizer)
            ->get(route('organizer.invitation.download', $this->event))
            ->assertOk()
            ->assertDownload('invitacion-zoe-hernandez-y-harry-sanchez.zip');
    }

    /** Mientras sirve no hay nada que guardar: su dirección es la copia buena. */
    public function test_una_invitacion_en_pie_no_se_descarga(): void
    {
        $this->event->update(['event_date' => now()->addMonth(), 'expires_at' => now()->addMonths(2)]);

        $this->actingAs($this->organizer)
            ->get(route('organizer.invitation.download', $this->event))
            ->assertNotFound();
    }

    public function test_la_invitacion_de_otro_no_se_descarga(): void
    {
        $otro = User::factory()->create();
        $otro->roles()->attach(Role::named('organizer')->id);

        $this->actingAs($otro)
            ->get(route('organizer.invitation.download', $this->event))
            ->assertForbidden();
    }

    public function test_sin_sesion_no_se_descarga(): void
    {
        $this->get(route('organizer.invitation.download', $this->event))
            ->assertRedirect(route('login'));
    }

    /* ---------------------------------------------------------------------
     | Qué trae el paquete
     * -------------------------------------------------------------------*/

    public function test_trae_su_index_y_su_hoja_de_estilos(): void
    {
        $rutas = $this->paths();

        $this->assertContains('invitacion-zoe-hernandez-y-harry-sanchez/index.html', $rutas);
        $this->assertContains('invitacion-zoe-hernandez-y-harry-sanchez/invitacion.css', $rutas);
    }

    public function test_el_html_lleva_los_textos_del_organizador(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('Zoe Hernández', $html);
        $this->assertStringContainsString('Harry Sánchez', $html);
        $this->assertStringContainsString('Parroquia de San José', $html);
        $this->assertStringContainsString('Vestido largo.', $html);
        $this->assertStringContainsString('¿Se admiten niños?', $html);
    }

    /**
     * Es un archivo suelto: desde file:// el navegador no carga módulos ES ni
     * deja hablar con ningún servidor, así que un <script> sólo daría errores.
     */
    public function test_no_lleva_nada_de_javascript(): void
    {
        $html = $this->html();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('x-data', $html);
        $this->assertStringNotContainsString('x-cloak', $html);
        $this->assertStringNotContainsString('csrf-token', $html);
    }

    /** Sin servidor no hay a quién confirmarle. */
    public function test_no_lleva_el_formulario_de_confirmacion(): void
    {
        $html = $this->html();

        $this->assertStringNotContainsString('id="confirmacion"', $html);
        $this->assertStringNotContainsString('data-rsvp-form', $html);
    }

    /**
     * Nada de lo que la página necesita para verse puede quedar colgando de
     * internet: ni el CDN, ni las tipografías, ni el propio sitio. Si una
     * sobrevive, la copia se ve rota el día que no haya red.
     */
    public function test_ningun_archivo_que_necesita_para_verse_apunta_a_internet(): void
    {
        $html = $this->html();

        preg_match_all('#(?:<img[^>]+src|<link[^>]+href|<script[^>]+src)="([^"]+)"#i', $html, $archivos);
        preg_match_all('#url\(([^)]+)\)#i', $html, $fondos);

        foreach (array_merge($archivos[1], $fondos[1]) as $url) {
            $this->assertStringNotContainsString('http', $url, "Quedó apuntando fuera: {$url}");
        }

        $this->assertStringNotContainsString('http', $this->file('invitacion.css'));
    }

    /**
     * Las ligas que escribió el organizador sí se quedan: el mapa de la iglesia
     * o la tienda de la mesa de regalos siguen sirviendo, y son suyas.
     */
    public function test_las_ligas_que_escribio_el_organizador_se_conservan(): void
    {
        $this->assertStringContainsString('https://maps.google.com', $this->html());
    }

    /** Las preguntas se leen: sin Alpine, abrirlas con un clic ya no es opción. */
    public function test_las_respuestas_de_las_preguntas_quedan_a_la_vista(): void
    {
        $this->assertStringContainsString('Sí, todos son bienvenidos.', $this->html());
        $this->assertStringContainsString('[x-cloak] { display: revert', $this->file('invitacion.css'));
    }

    /** La cuenta regresiva de una boda pasada marcaría cero: va la fecha. */
    public function test_la_cuenta_regresiva_se_cambia_por_la_fecha(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('El gran día fue', $html);
        $this->assertStringNotContainsString('data-countdown', $html);
    }

    /**
     * Todo lo que la página pide tiene que estar dentro, con el nombre con el
     * que lo pide. Es lo único que distingue un paquete que abre bien de uno
     * que abre con huecos.
     *
     * Las rutas del CSS se miran igual que las del HTML: la hoja va en la raíz,
     * junto al index.html, justo para que valgan lo mismo desde las dos.
     */
    public function test_todo_lo_que_la_pagina_pide_esta_dentro(): void
    {
        $pedidas = array_merge(
            $this->referencesIn($this->html()),
            $this->referencesIn($this->file('invitacion.css')),
        );

        $this->assertNotEmpty($pedidas, 'La copia no pide ni una imagen: algo se rompió antes.');

        $rutas = $this->paths();

        foreach ($pedidas as $ruta) {
            $this->assertContains(
                'invitacion-zoe-hernandez-y-harry-sanchez/' . $ruta,
                $rutas,
                "La página pide {$ruta} y no viaja en el paquete.",
            );
        }
    }

    /**
     * Las fotos que subió el organizador viajan igual que las del diseño.
     *
     * Viven en otro sitio —el disco 'public', no la carpeta public/—, así que
     * es el único camino que no comparten y hay que verlo aparte.
     */
    public function test_las_fotos_que_subio_el_organizador_viajan_en_el_paquete(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('eventos/1/galeria/nuestra-foto.jpg', 'esto es un jpg de mentira');

        $this->event->files()->create([
            'section' => 'gallery',
            'field_name' => 'photos.0.image',
            'file_path' => 'eventos/1/galeria/nuestra-foto.jpg',
            'disk' => 'public',
            'original_name' => 'nuestra-foto.jpg',
            'mime_type' => 'image/jpeg',
            'file_type' => 'image',
            'file_size' => 25,
            'sort_order' => 0,
        ]);

        $zip = app(StaticInvitationExport::class)->zipFor($this->event->fresh());

        $archivo = new ZipArchive();
        $archivo->open($zip);

        $rutas = [];
        for ($i = 0; $i < $archivo->numFiles; $i++) {
            $rutas[] = (string) $archivo->getNameIndex($i);
        }

        $html = (string) $archivo->getFromName('invitacion-zoe-hernandez-y-harry-sanchez/index.html');
        $suya = array_values(array_filter($rutas, fn ($r) => str_contains($r, 'nuestra-foto')));

        // Y el collage la pide por su nombre dentro del paquete, no por la del sitio.
        $this->assertStringContainsString('src="imagenes/nuestra-foto-', $html);

        $this->assertNotEmpty($suya, 'La foto del organizador se quedó fuera del paquete.');
        $this->assertSame('esto es un jpg de mentira', $archivo->getFromName($suya[0]));

        $archivo->close();
        @unlink($zip);
    }

    /** Las rutas relativas que aparecen en un archivo del paquete. */
    private function referencesIn(string $contenido): array
    {
        preg_match_all(
            '#(?:src="|href="|url\(\'|url\()(?!https?:|data:|mailto:|tel:|\#)([^"\')]+)#i',
            $contenido,
            $encontradas,
        );

        return array_values(array_unique($encontradas[1]));
    }

    /* ------------------------------------------------------------------ */

    private function html(): string
    {
        return $this->file('index.html');
    }

    private function file(string $ruta): string
    {
        $zip = new ZipArchive();
        $zip->open($this->zip());

        $contenido = $zip->getFromName('invitacion-zoe-hernandez-y-harry-sanchez/' . $ruta);
        $zip->close();

        $this->assertNotFalse($contenido, "El paquete no trae {$ruta}.");

        return (string) $contenido;
    }

    /** @return array<int, string> */
    private function paths(): array
    {
        $zip = new ZipArchive();
        $zip->open($this->zip());

        $rutas = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $rutas[] = (string) $zip->getNameIndex($i);
        }
        $zip->close();

        return $rutas;
    }

    /** Se arma una sola vez por prueba: bajar las tipografías no es gratis. */
    private function zip(): string
    {
        static $zip = null;

        return $zip ??= app(StaticInvitationExport::class)->zipFor($this->event);
    }

    private function features(): array
    {
        return [
            'general' => [
                'name_wife' => 'Zoe Hernández',
                'name_husband' => 'Harry Sánchez',
                'invitation_line' => 'Los invitamos a celebrar',
            ],
            'itinerary' => [
                'events' => [
                    [
                        'name' => 'Ceremonia religiosa',
                        'place_event' => 'Parroquia de San José',
                        'location_event' => 'Calle Juárez 25',
                        'location_maps' => 'https://maps.google.com',
                        'date_event' => '2024-05-18 17:00:00',
                    ],
                ],
            ],
            'dress_code' => [
                'dress_code_type' => 'Etiqueta',
                'women_attire' => 'Vestido largo.',
                'men_attire' => 'Traje oscuro.',
            ],
            'gallery' => [
                'intro' => 'Cinco años de viajes y domingos sin plan.',
            ],
            'gift_registry' => [
                'section_title' => 'Nuestra mesa de regalos',
                'message' => 'Tu presencia es nuestro mejor regalo.',
                'registries' => [
                    ['type' => 'cuenta_bancaria', 'banco' => 'BBVA', 'clabe' => '012180012345678901'],
                ],
            ],
            'faqs' => [
                'faqs' => [
                    ['question' => '¿Se admiten niños?', 'content' => 'Sí, todos son bienvenidos.'],
                ],
            ],
        ];
    }
}
