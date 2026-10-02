<?php

namespace Tests\Feature\Event;

use App\Models\AppFile;
use App\Models\Event;
use App\Models\Role;
use App\Models\SystemSection;
use App\Models\Template;
use App\Models\User;
use App\Services\FileStorageService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Guardado del wizard del organizador con DefaultTemplateStrategy:
 * PUT -> EventSaveStepRequest -> EventWizardService::saveStep() -> BD + storage.
 *
 * Los tests marcados "BUG:" describen el comportamiento correcto y hoy fallan:
 * documentan un defecto real del guardado.
 */
class EventWizardSaveTest extends TestCase
{
    use RefreshDatabase;

    protected User $organizer;
    protected Event $event;

    /**
     * Seguro: RefreshDatabase vacía la BD. Si la configuración no apunta a una BD
     * de tests, se aborta ANTES de migrar para no destruir datos reales.
     */
    protected function beforeRefreshingDatabase()
    {
        $database = (string) config('database.connections.' . config('database.default') . '.database');

        if (!str_ends_with($database, '_test')) {
            throw new RuntimeException("Abortado: RefreshDatabase iba a vaciar '{$database}'. Los tests deben correr sobre una BD *_test (ver phpunit.xml).");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->organizer = $this->makeOrganizer();

        // Sin view_path, TemplateDiscoveryService resuelve DefaultTemplateStrategy.
        $template = Template::factory()->create(['view_path' => null]);

        $this->event = Event::factory()->create([
            'user_id' => $this->organizer->id,
            'template_id' => $template->id,
            'title' => 'Título original',
            'features' => null,
        ]);
    }

    /* =====================================================================
     | Acceso
     * ===================================================================*/

    public function test_invitado_no_puede_guardar(): void
    {
        $this->put($this->updateUrl('general'), $this->generalData())->assertRedirect();

        $this->assertNull($this->event->fresh()->features);
    }

    public function test_usuario_sin_rol_organizer_recibe_403(): void
    {
        $this->saveStep('general', $this->generalData(), User::factory()->create())->assertForbidden();

        $this->assertNull($this->event->fresh()->features);
    }

    public function test_organizer_no_puede_editar_el_evento_de_otro(): void
    {
        // BUG: $this->authorize('update', $event) está comentado en EventSetupController.
        $response = $this->saveStep('general', $this->generalData(['name_wife' => 'Intrusa']), $this->makeOrganizer());

        $response->assertForbidden();
        $this->assertNull($this->event->fresh()->features, 'Otro organizador sobrescribió el evento.');
    }

    public function test_el_dueno_puede_ver_el_wizard(): void
    {
        $this->actingAs($this->organizer)
            ->get(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'general']))
            ->assertOk();
    }

    public function test_organizer_no_puede_ver_el_wizard_de_otro(): void
    {
        $this->actingAs($this->makeOrganizer())
            ->get(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'general']))
            ->assertForbidden();
    }

    public function test_paso_inexistente_no_escribe_en_features(): void
    {
        // BUG: un paso que no existe no tiene reglas, valida vacío y saveStep crea la clave.
        $response = $this->saveStep('paso-inventado', ['cualquier' => 'cosa']);

        $response->assertNotFound();
        $this->assertArrayNotHasKey('paso-inventado', $this->event->fresh()->features ?? []);
    }

    /* =====================================================================
     | Paso general: columnas nativas + features
     * ===================================================================*/

    public function test_guarda_general_y_mapea_columnas_nativas(): void
    {
        $this->saveStep('general', $this->generalData())->assertSessionHasNoErrors();

        $event = $this->event->fresh();

        $this->assertSame('Boda Ana y Luis', $event->title);
        $this->assertSame('2026-12-01 18:00', $event->event_date->format('Y-m-d H:i'));
        $this->assertSame('Ana', $event->features['general']['name_wife']);
        $this->assertArrayNotHasKey('name_event', $event->features['general'], 'name_event va a la columna title, no a features.');
        $this->assertArrayNotHasKey('date_event_person', $event->features['general']);
    }

    public function test_avanza_current_step_y_redirige_al_siguiente_paso(): void
    {
        $this->saveStep('general', $this->generalData())
            ->assertRedirect(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'itinerary']));

        $this->assertSame('itinerary', $this->event->fresh()->features['meta']['current_step']);
    }

    public function test_obligatorios_faltantes_no_guardan_nada(): void
    {
        $this->saveStep('general', [])
            ->assertSessionHasErrors(['name_event', 'name_wife', 'name_husband', 'family_parents', 'date_event_person']);

        $event = $this->event->fresh();
        $this->assertNull($event->features);
        $this->assertSame('Título original', $event->title);
    }

    public function test_ignora_campos_que_no_pertenecen_al_paso(): void
    {
        $this->saveStep('general', $this->generalData([
            'campo_inyectado' => 'x',
            'meta' => ['current_step' => 'faqs'],
        ]))->assertSessionHasNoErrors();

        $features = $this->event->fresh()->features;
        $this->assertArrayNotHasKey('campo_inyectado', $features['general']);
        $this->assertSame('itinerary', $features['meta']['current_step'], 'El cliente no debe poder fijar meta.');
    }

    /* =====================================================================
     | Persistencia entre pasos
     * ===================================================================*/

    public function test_guardar_un_paso_no_borra_los_demas(): void
    {
        $this->saveStep('general', $this->generalData());
        $this->saveStep('dress_code', ['dress_code_type' => 'Formal', 'reference_image' => null]);

        $features = $this->event->fresh()->features;
        $this->assertSame('Ana', $features['general']['name_wife']);
        $this->assertSame('Formal', $features['dress_code']['dress_code_type']);
    }

    public function test_el_tipo_de_vestimenta_acepta_texto_libre(): void
    {
        // Ya no es una lista cerrada: el organizador escribe lo que quiera.
        $this->saveStep('dress_code', [
            'dress_code_type' => 'Cocktail con sombrero',
            'reference_image' => null,
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            'Cocktail con sombrero',
            $this->event->fresh()->features['dress_code']['dress_code_type'],
        );
    }

    public function test_el_tipo_de_vestimenta_sigue_siendo_obligatorio_y_acotado(): void
    {
        $this->saveStep('dress_code', ['dress_code_type' => '', 'reference_image' => null])
            ->assertSessionHasErrors('dress_code_type');

        $this->saveStep('dress_code', ['dress_code_type' => str_repeat('a', 81), 'reference_image' => null])
            ->assertSessionHasErrors('dress_code_type');
    }

    public function test_vaciar_un_campo_opcional_lo_guarda_como_null(): void
    {
        $this->saveStep('dress_code', ['dress_code_type' => 'Formal', 'color_or_theme' => 'Tonos tierra', 'reference_image' => null]);
        $this->saveStep('dress_code', ['dress_code_type' => 'Formal', 'color_or_theme' => '', 'reference_image' => null]);

        $this->assertNull($this->event->fresh()->features['dress_code']['color_or_theme']);
    }

    /* =====================================================================
     | Itinerario (repeater de texto)
     * ===================================================================*/

    public function test_guarda_las_filas_del_itinerario_en_orden(): void
    {
        $this->saveStep('itinerary', ['events' => [
            $this->itineraryRow('Ceremonia', 'https://maps.app.goo.gl/abc'),
            $this->itineraryRow('Recepción'),
        ]])->assertSessionHasNoErrors();

        $events = $this->event->fresh()->features['itinerary']['events'];
        $this->assertSame(['Ceremonia', 'Recepción'], array_column($events, 'name'));
        $this->assertSame('https://maps.app.goo.gl/abc', $events[0]['location_maps'], 'Un texto con URL no es un archivo.');
    }

    public function test_fila_incompleta_del_itinerario_no_guarda(): void
    {
        $this->saveStep('itinerary', ['events' => [
            $this->itineraryRow('Ceremonia'),
            $this->itineraryRow(''),
        ]])->assertSessionHasErrors('events.1.name');

        $this->assertArrayNotHasKey('itinerary', $this->event->fresh()->features ?? []);
    }

    public function test_ubicacion_de_google_maps_debe_ser_una_url(): void
    {
        $this->saveStep('itinerary', ['events' => [
            $this->itineraryRow('Ceremonia', 'esto no es una liga'),
        ]])->assertSessionHasErrors('events.0.location_maps');

        $this->assertArrayNotHasKey('itinerary', $this->event->fresh()->features ?? []);
    }

    public function test_eliminar_una_fila_del_itinerario(): void
    {
        $this->saveStep('itinerary', ['events' => [$this->itineraryRow('Ceremonia'), $this->itineraryRow('Recepción')]]);
        $this->saveStep('itinerary', ['events' => [$this->itineraryRow('Recepción')]]);

        $this->assertSame(['Recepción'], array_column($this->event->fresh()->features['itinerary']['events'], 'name'));
    }

    public function test_vaciar_el_itinerario_elimina_todas_las_filas(): void
    {
        // BUG: sin filas el navegador no envía 'events'; saveStep hace array_merge con
        // lo guardado y las filas viejas sobreviven. No hay forma de vaciar un repeater.
        $this->saveStep('itinerary', ['events' => [$this->itineraryRow('Ceremonia')]]);
        $this->saveStep('itinerary', []);

        $this->assertEmpty($this->event->fresh()->features['itinerary']['events'] ?? []);
    }

    public function test_filas_con_indices_salteados_se_guardan_como_lista(): void
    {
        // Si el reindexado del frontend falla, llegan índices 0 y 2: deben guardarse
        // como lista y no como objeto JSON {"0":…,"2":…}.
        $this->saveStep('itinerary', ['events' => [
            0 => $this->itineraryRow('Ceremonia'),
            2 => $this->itineraryRow('Recepción'),
        ]]);

        $this->assertTrue(array_is_list($this->event->fresh()->features['itinerary']['events']));
    }

    /* =====================================================================
     | Código de vestimenta (imagen fuera de repeater)
     * ===================================================================*/

    public function test_sube_la_imagen_de_referencia(): void
    {
        $this->saveStep('dress_code', [
            'dress_code_type' => 'Formal',
            'reference_image' => UploadedFile::fake()->image('ref.jpg'),
        ])->assertSessionHasNoErrors();

        $file = AppFile::sole();
        $this->assertSame('dress_code', $file->section);
        $this->assertSame('reference_image', $file->field_name);
        Storage::disk('public')->assertExists($file->file_path);

        $dress = $this->event->fresh()->features['dress_code'];
        $this->assertArrayNotHasKey('reference_image_url', $dress, 'Los auxiliares de imagen no van a features.');
        $this->assertArrayNotHasKey('reference_image_uuid', $dress);
    }

    public function test_conserva_la_imagen_existente_sin_resubirla(): void
    {
        $original = $this->uploadReferenceImage();

        $this->saveStep('dress_code', [
            'dress_code_type' => 'Casual',
            'reference_image' => null,
            'reference_image_url' => $original->url,
            'reference_image_uuid' => $original->uuid,
        ])->assertSessionHasNoErrors();

        $this->assertSame($original->uuid, AppFile::sole()->uuid);
        Storage::disk('public')->assertExists($original->file_path);
    }

    public function test_conserva_la_imagen_aunque_no_llegue_la_llave_del_archivo(): void
    {
        $original = $this->uploadReferenceImage();

        // Cuando el organizador no toca el input de archivo, el navegador manda
        // los auxiliares pero no la llave del campo. Sin ellos el guardado creía
        // que la imagen se había quitado y la borraba al editar cualquier texto.
        $this->saveStep('dress_code', [
            'dress_code_type' => 'Casual',
            'reference_image_url' => $original->url,
            'reference_image_uuid' => $original->uuid,
        ])->assertSessionHasNoErrors();

        $this->assertSame($original->uuid, AppFile::sole()->uuid);
        Storage::disk('public')->assertExists($original->file_path);
    }

    public function test_reemplaza_la_imagen_existente(): void
    {
        $original = $this->uploadReferenceImage();

        $this->saveStep('dress_code', [
            'dress_code_type' => 'Formal',
            'reference_image' => UploadedFile::fake()->image('nueva.jpg'),
            'reference_image_uuid' => $original->uuid,
        ])->assertSessionHasNoErrors();

        $nueva = AppFile::sole();
        $this->assertNotSame($original->uuid, $nueva->uuid);
        Storage::disk('public')->assertMissing($original->file_path);
        Storage::disk('public')->assertExists($nueva->file_path);
    }

    public function test_quitar_la_imagen_la_borra_de_bd_y_storage(): void
    {
        $original = $this->uploadReferenceImage();

        $this->saveStep('dress_code', ['dress_code_type' => 'Formal', 'reference_image' => null])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, AppFile::count());
        Storage::disk('public')->assertMissing($original->file_path);
    }

    public function test_rechaza_un_archivo_que_no_es_imagen(): void
    {
        $this->saveStep('dress_code', [
            'dress_code_type' => 'Formal',
            'reference_image' => UploadedFile::fake()->create('contrato.pdf', 20, 'application/pdf'),
        ])->assertSessionHasErrors('reference_image');

        $this->assertSame(0, AppFile::count());
    }

    /* =====================================================================
     | Galería (repeater de imágenes)
     * ===================================================================*/

    public function test_sube_varias_fotos(): void
    {
        $this->saveGallery([
            ['image' => UploadedFile::fake()->image('a.jpg')],
            ['image' => UploadedFile::fake()->image('b.jpg')],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            ['photos.0.image', 'photos.1.image'],
            AppFile::pluck('field_name')->all()
        );
    }

    public function test_reordenar_fotos_actualiza_su_posicion(): void
    {
        [$a, $b] = $this->uploadTwoPhotos();

        // El usuario arrastra B al primer lugar.
        $this->saveGallery([
            ['image' => null, 'image_url' => $b->url, 'image_uuid' => $b->uuid],
            ['image' => null, 'image_url' => $a->url, 'image_uuid' => $a->uuid],
        ])->assertSessionHasNoErrors();

        $this->assertSame('photos.0.image', AppFile::where('uuid', $b->uuid)->value('field_name'));
        $this->assertSame('photos.1.image', AppFile::where('uuid', $a->uuid)->value('field_name'));
    }

    public function test_eliminar_una_foto_borra_solo_esa(): void
    {
        [$a, $b] = $this->uploadTwoPhotos();

        $this->saveGallery([
            ['image' => null, 'image_url' => $b->url, 'image_uuid' => $b->uuid],
        ])->assertSessionHasNoErrors();

        $this->assertSame([$b->uuid], AppFile::pluck('uuid')->all());
        Storage::disk('public')->assertMissing($a->file_path);
        Storage::disk('public')->assertExists($b->file_path);
    }

    public function test_agregar_una_foto_a_una_galeria_con_fotos_existentes(): void
    {
        // Caso más común: la galería ya tiene fotos y se agrega una en el mismo guardado.
        // Si la petición trae algún archivo, ImageUploadField aplica las reglas de archivo
        // (file, image, mimes...) a TODAS las filas, también a las que sólo conservan su
        // imagen (image = null + url + uuid).
        [$a] = $this->uploadTwoPhotos();

        $this->saveGallery([
            ['image' => null, 'image_url' => $a->url, 'image_uuid' => $a->uuid],
            ['image' => UploadedFile::fake()->image('nueva.jpg')],
        ])->assertSessionHasNoErrors();

        // Queda la foto A conservada + la nueva; la B se omitió y se borra.
        $this->assertSame(2, AppFile::count());
        $this->assertTrue(AppFile::where('uuid', $a->uuid)->exists());
    }

    public function test_rechaza_un_archivo_que_no_es_imagen_en_la_galeria(): void
    {
        // Regresión: dentro del repeater la clave es 'photos.*.image'. hasFile() resuelve
        // el comodín con data_get(), así que image/mimes/max sí se aplican.
        $this->saveGallery([
            ['image' => UploadedFile::fake()->create('virus.pdf', 20, 'application/pdf')],
        ])->assertSessionHasErrors('photos.0.image');

        $this->assertSame(0, AppFile::count());
    }

    public function test_si_falla_un_archivo_no_quedan_archivos_huerfanos(): void
    {
        // El segundo archivo falla al guardarse: la transacción debe revertir el primero
        // en BD, y el rollback físico debe borrarlo del disco.
        $this->app->instance(FileStorageService::class, new class extends FileStorageService {
            private int $calls = 0;

            public function store(Model $model, UploadedFile $file, string $section, string $fieldName, string $disk = 'public'): AppFile
            {
                if (++$this->calls === 2) {
                    throw new Exception('Fallo simulado de almacenamiento');
                }

                return parent::store($model, $file, $section, $fieldName, $disk);
            }
        });

        $this->saveGallery([
            ['image' => UploadedFile::fake()->image('a.jpg')],
            ['image' => UploadedFile::fake()->image('b.jpg')],
        ])->assertSessionHas('error');

        $this->assertSame(0, AppFile::count());
        $this->assertSame([], Storage::disk('public')->allFiles("events/{$this->event->id}"));
        $this->assertArrayNotHasKey('gallery', $this->event->fresh()->features ?? []);
    }

    /* =====================================================================
     | Mesa de regalos (sección dinámica del admin)
     * ===================================================================*/

    public function test_cuenta_bancaria_exige_banco(): void
    {
        $this->seedRegistriesSection();

        $this->saveStep('gift_registry', $this->giftData([
            ['type' => 'cuenta_bancaria', 'banco' => '', 'lista_de_regalos' => ''],
        ]))->assertSessionHasErrors('registries.0.banco');
    }

    public function test_amazon_no_exige_campos_bancarios_y_se_guarda(): void
    {
        $this->seedRegistriesSection();

        $this->saveStep('gift_registry', $this->giftData([
            ['type' => 'amazon', 'banco' => '', 'lista_de_regalos' => 'https://amazon.com.mx/lista'],
        ]))->assertSessionHasNoErrors();

        $row = $this->event->fresh()->features['gift_registry']['registries'][0];
        $this->assertSame('amazon', $row['type']);
        $this->assertSame('https://amazon.com.mx/lista', $row['lista_de_regalos']);
    }

    /* =====================================================================
     | Confirmación y FAQ
     * ===================================================================*/

    public function test_rsvp_guarda_los_booleanos_desmarcados(): void
    {
        $this->saveStep('rsvp', [
            'rsvp_deadline' => '2026-11-01 00:00:00',
            'welcome_message' => 'Bienvenidos',
            'thank_you_message' => 'Gracias',
            'ask_dietary_requirements' => '0',
            'enable_open_confirmation_link' => '1',
        ])->assertSessionHasNoErrors();

        $rsvp = $this->event->fresh()->features['rsvp'];
        $this->assertSame('0', (string) $rsvp['ask_dietary_requirements'], 'Un checkbox desmarcado no debe perderse.');
        $this->assertSame('1', (string) $rsvp['enable_open_confirmation_link']);
    }

    public function test_faqs_son_obligatorias(): void
    {
        $this->saveStep('faqs', [])->assertSessionHasErrors('faqs');

        $this->assertArrayNotHasKey('faqs', $this->event->fresh()->features ?? []);
    }

    public function test_guarda_las_faqs(): void
    {
        $this->saveStep('faqs', ['faqs' => [
            ['question' => '¿Se admiten niños?', 'content' => "Sí.\nHabrá área infantil."],
        ]])->assertSessionHasNoErrors();

        // assertEquals y no assertSame: la columna JSON de MySQL reordena las claves
        // de cada objeto (content antes de question), y el orden no importa aquí.
        $this->assertEquals(
            [['question' => '¿Se admiten niños?', 'content' => "Sí.\nHabrá área infantil."]],
            $this->event->fresh()->features['faqs']['faqs']
        );
    }

    /* =====================================================================
     | Helpers
     * ===================================================================*/

    private function makeOrganizer(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => 'organizer'], ['display_name' => 'Organizador']));

        return $user;
    }

    private function updateUrl(string $step): string
    {
        return route('events.wizard.update', ['event' => $this->event->slug, 'step' => $step]);
    }

    private function saveStep(string $step, array $data, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->organizer)
            ->from(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => $step]))
            ->put($this->updateUrl($step), $data);
    }

    private function generalData(array $overrides = []): array
    {
        return array_merge([
            'name_event' => 'Boda Ana y Luis',
            'name_wife' => 'Ana',
            'name_husband' => 'Luis',
            'family_parents' => 'Familia Pérez y Familia López',
            'date_event_person' => '2026-12-01 18:00:00',
        ], $overrides);
    }

    private function itineraryRow(string $name, string $maps = ''): array
    {
        return [
            'name' => $name,
            'place_event' => 'Iglesia',
            'location_event' => 'Calle 1',
            'location_maps' => $maps,
            'date_event' => '2026-12-01 18:00:00',
        ];
    }

    private function saveGallery(array $photos)
    {
        return $this->saveStep('gallery', [
            'guest_photos_message' => 'Comparte tus fotos',
            'allow_guest_uploads' => '1',
            'photos' => $photos,
        ]);
    }

    private function uploadReferenceImage(): AppFile
    {
        $this->saveStep('dress_code', [
            'dress_code_type' => 'Formal',
            'reference_image' => UploadedFile::fake()->image('ref.jpg'),
        ]);

        return AppFile::sole();
    }

    /** @return AppFile[] [a, b] */
    private function uploadTwoPhotos(): array
    {
        $this->saveGallery([
            ['image' => UploadedFile::fake()->image('a.jpg')],
            ['image' => UploadedFile::fake()->image('b.jpg')],
        ]);

        return [
            AppFile::where('field_name', 'photos.0.image')->sole(),
            AppFile::where('field_name', 'photos.1.image')->sole(),
        ];
    }

    private function giftData(array $registries): array
    {
        return [
            'section_title' => 'Mesa de regalos',
            'message' => 'Tu presencia es nuestro mejor regalo.',
            'registries' => $registries,
        ];
    }

    /** Sección 'registries' en el formato que genera el admin actual. */
    private function seedRegistriesSection(): void
    {
        SystemSection::create([
            'key' => 'registries',
            'title' => 'Opciones de regalo',
            'order' => 1,
            'is_global' => true,
            'is_active' => true,
            'type' => 'repeater',
            'parent' => 'gift_registry',
            'schema' => [
                ['key' => 'type', 'type' => 'select', 'label' => 'Tipo de mesa', 'is_required' => '1',
                    'options' => ['amazon' => 'Amazon', 'cuenta_bancaria' => 'Cuenta bancaria']],
                ['key' => 'banco', 'type' => 'text', 'label' => 'Banco', 'is_required' => '1',
                    'rules' => ['string', 'max:255'],
                    'depends_on' => ['field' => 'type', 'values' => ['cuenta_bancaria']]],
                ['key' => 'lista_de_regalos', 'type' => 'url', 'label' => 'Lista de regalos', 'is_required' => '1',
                    'rules' => ['url', 'max:255'],
                    'depends_on' => ['field' => 'type', 'values' => ['amazon']]],
            ],
        ]);
    }
}
