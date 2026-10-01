<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Pantalla del superadmin para reemplazar las imágenes de la plantilla de una
 * boda concreta.
 */
class EventTemplateAssetsTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
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

        Storage::fake('public');
        $this->seed(RolesAndAdminSeeder::class);

        $this->superadmin = User::factory()->create();
        $this->superadmin->roles()->attach(Role::named('superadmin')->id);

        $organizer = User::factory()->create();
        $organizer->roles()->attach(Role::named('organizer')->id);

        $this->event = Event::factory()->create([
            'user_id' => $organizer->id,
            'template_id' => Template::factory()->create([
                'name' => 'Boda Editorial',
                'view_path' => 'build-templates.template-editorial.index',
            ])->id,
            'custom_url' => 'sofia-y-andres',
            'event_date' => now()->addMonths(2),
            'features' => [
                'general' => ['name_wife' => 'Sofía', 'name_husband' => 'Andrés'],
                // Con una corrida, el bloque de transporte se pinta y con él su
                // mapa, que es la imagen que reemplaza el superadmin aquí.
                'transport' => ['rides' => [['time' => '22:00', 'route' => 'Hoteles → Parroquia']]],
            ],
        ]);
    }

    public function test_el_menu_del_superadmin_lleva_a_personalizacion(): void
    {
        $this->actingAs($this->superadmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee(route('superadmin.events.assets.index'), false)
            ->assertSee('Personalización');
    }

    public function test_el_listado_muestra_las_bodas_con_plantilla(): void
    {
        $this->event->update([
            'template_assets' => ['general' => ['monogram' => '/storage/plantillas/1/general/otro.png']],
        ]);

        $this->actingAs($this->superadmin)
            ->get(route('superadmin.events.assets.index'))
            ->assertOk()
            ->assertSee('sofia-y-andres')
            ->assertSee('Boda Editorial')
            // Cuántas imágenes tiene reemplazadas hoy.
            ->assertSee('1 imágenes');
    }

    public function test_la_pantalla_lista_lo_que_declaran_las_secciones(): void
    {
        $this->actingAs($this->superadmin)
            ->get(route('superadmin.events.assets.edit', $this->event))
            ->assertOk()
            ->assertSee('Mapa ilustrado')
            ->assertSee('Ilustración de la sección')
            /*
            | Y lo que es de la pareja aquí no se toca: la foto de portada y el
            | monograma los sube el organizador en su wizard.
            */
            ->assertDontSee('Foto de portada')
            ->assertDontSee('Monograma de la pareja');
    }

    public function test_el_superadmin_reemplaza_una_imagen_y_la_invitacion_la_usa(): void
    {
        $this->actingAs($this->superadmin)
            ->post(route('superadmin.events.assets.update', $this->event), [
                'section' => 'transport',
                'asset' => 'map',
                'image' => UploadedFile::fake()->image('mapa-sa.png'),
            ])
            ->assertRedirect();

        $url = $this->event->fresh()->template_assets['transport']['map'];

        $this->assertStringContainsString('plantillas/' . $this->event->id, $url);

        $this->get('/invitacion/sofia-y-andres')
            ->assertOk()
            ->assertSee($url, false)
            ->assertDontSee('images/assets-editorial/transporte-mapa.png', false);
    }

    public function test_se_puede_volver_a_la_imagen_de_la_plantilla(): void
    {
        $this->event->update([
            'template_assets' => ['transport' => ['map' => '/storage/plantillas/1/transport/otro.png']],
        ]);

        $this->actingAs($this->superadmin)
            ->delete(route('superadmin.events.assets.reset', $this->event), [
                'section' => 'transport',
                'asset' => 'map',
            ])
            ->assertRedirect();

        $this->assertNull($this->event->fresh()->template_assets);

        $this->get('/invitacion/sofia-y-andres')
            ->assertOk()
            ->assertSee('images/assets-editorial/transporte-mapa.png', false);
    }

    public function test_una_secuencia_se_guarda_numerada(): void
    {
        $travel = Event::factory()->create([
            'user_id' => $this->event->user_id,
            'template_id' => Template::factory()->create([
                'view_path' => 'build-templates.template-travel.index',
            ])->id,
            'custom_url' => 'harry-y-zoe',
            'event_date' => now()->addMonths(2),
        ]);

        $this->actingAs($this->superadmin)
            ->post(route('superadmin.events.assets.update', $travel), [
                'section' => 'general',
                'asset' => 'frames_card',
                'frames' => [
                    UploadedFile::fake()->image('01.png'),
                    UploadedFile::fake()->image('02.png'),
                ],
            ])
            ->assertRedirect();

        Storage::disk('public')->assertExists("plantillas/{$travel->id}/general/frames_card/frame1.png");
        Storage::disk('public')->assertExists("plantillas/{$travel->id}/general/frames_card/frame2.png");
    }

    public function test_un_organizador_no_entra(): void
    {
        $this->actingAs(User::find($this->event->user_id))
            ->get(route('superadmin.events.assets.edit', $this->event))
            ->assertForbidden();
    }
}
