<?php

namespace Tests\Feature\Event;

use App\Models\AppFile;
use App\Models\Event;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Services\EventWizardService;
use App\Services\Template\TemplateDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * El orden de las fotos de la galería.
 *
 * Las fotos no viven en features sino en app_files, y su posición es el índice
 * de su field_name ('photos.0.image'). Al reordenarlas arrastrando, ese índice
 * cambia; lo que se lee después tiene que respetarlo y no el orden en que se
 * subieron, que es lo que hacía antes: la galería se reordenaba en pantalla y
 * al recargar volvía a su orden viejo.
 */
class GalleryOrderTest extends TestCase
{
    use RefreshDatabase;

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

        $organizer = User::factory()->create();
        $organizer->roles()->attach(Role::firstOrCreate(['name' => 'organizer'], ['display_name' => 'Organizador'])->id);

        $this->event = Event::factory()->create([
            'user_id' => $organizer->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
        ]);
    }

    /** Registra una foto en la posición que se le diga. */
    private function foto(string $archivo, int $posicion): void
    {
        AppFile::create([
            'fileable_type' => Event::class,
            'fileable_id' => $this->event->id,
            'section' => 'gallery',
            'field_name' => "photos.{$posicion}.image",
            'original_name' => $archivo,
            'file_path' => "events/{$this->event->id}/gallery/{$archivo}",
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'file_type' => 'image',
            'file_size' => 1024,
        ]);
    }

    /** @return array<int, string> los archivos tal como los leería el wizard */
    private function fotosLeidas(): array
    {
        $strategy = app(TemplateDiscoveryService::class)->resolveStrategy(null);
        $values = app(EventWizardService::class)->resolveSavedValues($this->event->fresh(), $strategy, 'gallery');

        return collect($values['photos'] ?? [])
            ->map(fn (array $fila) => basename((string) ($fila['image']['url'] ?? '')))
            ->values()
            ->all();
    }

    public function test_las_fotos_se_leen_en_el_orden_guardado_y_no_en_el_de_subida(): void
    {
        /*
        | Se crean a propósito desordenadas: la que va primero se subió al final.
        | Es justo lo que queda tras arrastrar una foto hacia arriba.
        */
        $this->foto('segunda.jpg', 1);
        $this->foto('tercera.jpg', 2);
        $this->foto('primera.jpg', 0);

        $this->assertSame(['primera.jpg', 'segunda.jpg', 'tercera.jpg'], $this->fotosLeidas());
    }

    public function test_de_la_decima_en_adelante_no_se_cuelan_entre_las_primeras(): void
    {
        // Ordenando como texto, 'photos.10' caería entre 'photos.1' y 'photos.2'.
        // Se crean revueltas para que el orden no salga por casualidad.
        foreach ([10, 1, 11, 0, 9, 2] as $posicion) {
            $this->foto("foto-{$posicion}.jpg", $posicion);
        }

        $this->assertSame([
            'foto-0.jpg', 'foto-1.jpg', 'foto-2.jpg', 'foto-9.jpg', 'foto-10.jpg', 'foto-11.jpg',
        ], $this->fotosLeidas());
    }

    public function test_una_imagen_suelta_del_paso_no_se_mezcla_con_la_lista(): void
    {
        // El fondo de la galería no es del repetidor y no tiene índice.
        AppFile::create([
            'fileable_type' => Event::class,
            'fileable_id' => $this->event->id,
            'section' => 'gallery',
            'field_name' => 'background_image',
            'original_name' => 'fondo.jpg',
            'file_path' => "events/{$this->event->id}/gallery/fondo.jpg",
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'file_type' => 'image',
            'file_size' => 1024,
        ]);

        $this->foto('segunda.jpg', 1);
        $this->foto('primera.jpg', 0);

        $strategy = app(TemplateDiscoveryService::class)->resolveStrategy(null);
        $values = app(EventWizardService::class)->resolveSavedValues($this->event->fresh(), $strategy, 'gallery');

        $this->assertSame(['primera.jpg', 'segunda.jpg'], $this->fotosLeidas());
        $this->assertStringContainsString('fondo.jpg', $values['background_image']['url']);
    }
}
