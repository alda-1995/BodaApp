<?php

namespace Tests\Feature\Invitation;

use App\Models\ColorPalette;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Services\EventService;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Invitación digital pública: se arma con las secciones del catálogo y cada una
 * se pinta con el bloque de su tipo dentro de la carpeta de la plantilla.
 */
class InvitationPageTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW = 'build-templates.template-travel.index';

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
            'custom_url' => 'harry-y-zoe',
            'url_partner_1' => 'Harry',
            'url_partner_2' => 'Zoe',
            'event_date' => '2027-01-10 15:30:00',
            'features' => $this->features(),
        ]);
    }

    public function test_muestra_lo_que_el_organizador_capturo(): void
    {
        $this->get('/invitacion/harry-y-zoe')
            ->assertOk()
            // Portada
            ->assertSee('Zoe Hernández')
            ->assertSee('Harry Sánchez')
            ->assertSee('10 de enero de 2027')
            ->assertSee('Sr. Luis Hernández y Sra. Ana Prado')
            // Itinerario
            ->assertSee('Ceremonia religiosa')
            ->assertSee('Parroquia de San José')
            ->assertSee('Cena y fiesta')
            // Mesa de regalos
            ->assertSee('Nuestra mesa de regalos')
            ->assertSee('012180012345678901')
            // Preguntas frecuentes
            ->assertSee('¿Se admiten niños?')
            ->assertSee('Sí, todos son bienvenidos.')
            // Código de vestimenta
            ->assertSee('Etiqueta');
    }

    public function test_cada_bloque_sale_de_la_carpeta_de_la_plantilla(): void
    {
        $html = $this->get('/invitacion/harry-y-zoe')->assertOk()->getContent();

        // Clases propias de la plantilla (CSS3, sin Tailwind).
        $this->assertStringContainsString('class="tv-banner"', $html);
        $this->assertStringContainsString('tv-timeline', $html);
        $this->assertStringContainsString('tv-gifts', $html);
        // Su hoja de estilos y su JS, no los del panel.
        $this->assertStringContainsString('templates/template-travel/template.css', $html);
        $this->assertStringContainsString('templates/template-travel/index.js', $html);
    }

    public function test_la_historia_usa_lo_capturado_o_su_texto_por_defecto(): void
    {
        // Sin capturar nada, el bloque trae los textos de la plantilla.
        $this->get('/invitacion/harry-y-zoe')
            ->assertOk()
            ->assertSee('Un poco de nuestra historia');

        $features = $this->event->features;
        $features['history'] = [
            'title' => 'Así empezó todo',
            'subtitle' => 'De un café en Coyoacán a este día.',
        ];
        $this->event->update(['features' => $features]);

        $this->get('/invitacion/harry-y-zoe')
            ->assertOk()
            ->assertSee('Así empezó todo')
            ->assertSee('De un café en Coyoacán a este día.')
            ->assertDontSee('Un poco de nuestra historia');
    }

    public function test_las_fuentes_son_las_que_declara_la_plantilla(): void
    {
        $html = $this->get('/invitacion/harry-y-zoe')->assertOk()->getContent();

        // Las de Google las declara su estrategia...
        $this->assertStringContainsString('fonts.googleapis.com/css2?family=Montaga', $html);
        // ...y la tipografía propia viaja en el CSS de la plantilla.
        $this->assertStringContainsString('templates/template-travel/template.css', $html);
    }

    public function test_el_itinerario_se_ordena_por_hora(): void
    {
        // Se capturó primero la fiesta, pero el día empieza con la ceremonia.
        $this->get('/invitacion/harry-y-zoe')
            ->assertOk()
            ->assertSeeInOrder(['Ceremonia religiosa', 'Cena y fiesta']);
    }

    public function test_las_secciones_vacias_no_se_pintan(): void
    {
        $this->event->update(['features' => [
            'general' => ['name_wife' => 'Zoe', 'name_husband' => 'Harry'],
        ]]);

        $this->get('/invitacion/harry-y-zoe')
            ->assertOk()
            ->assertSee('Zoe')
            ->assertDontSee('Mesa de regalos')
            ->assertDontSee('Código de vestimenta')
            ->assertDontSee('tv-timeline', false);
    }

    public function test_saluda_al_invitado_de_la_liga_personal(): void
    {
        $uuid = $this->guestUuid('Familia Martínez López');

        $this->get("/invitacion/harry-y-zoe/{$uuid}")
            ->assertOk()
            ->assertSee('Familia Martínez López');
    }

    public function test_un_uuid_ajeno_no_muestra_a_nadie(): void
    {
        $this->guestUuid('Familia Martínez López');

        $this->get('/invitacion/harry-y-zoe/' . Str::uuid())
            ->assertOk()
            ->assertDontSee('Familia Martínez López');
    }

    public function test_usa_la_paleta_elegida(): void
    {
        $palette = ColorPalette::create([
            'name' => 'Vino',
            'primary_color' => '#7B2D3B',
            'secondary_color' => '#F5E9DC',
            'accent_color' => '#FFFFFF',
            'is_active' => true,
        ]);
        $this->event->applyColorPalette($palette);

        $this->get('/invitacion/harry-y-zoe')
            ->assertOk()
            ->assertSee('--color-brand: #7B2D3B;', false)
            ->assertSee('--color-brand-soft: #F5E9DC;', false);
    }

    public function test_la_direccion_anterior_redirige(): void
    {
        app(EventService::class)->changeCustomUrl($this->event, 'zoe-y-harry');

        $this->get('/invitacion/harry-y-zoe')
            ->assertStatus(301)
            ->assertRedirect(Event::invitationUrlFor('zoe-y-harry'));
    }

    public function test_una_invitacion_vencida_ya_no_se_ve(): void
    {
        $this->event->update(['event_date' => now()->subMonths(3)]);

        $this->get('/invitacion/harry-y-zoe')
            ->assertStatus(410)
            ->assertSee('Esta invitación ya no está disponible');
    }

    public function test_una_direccion_inexistente_da_404(): void
    {
        $this->get('/invitacion/no-existe')->assertNotFound();
    }

    public function test_la_vista_previa_usa_datos_de_ejemplo(): void
    {
        $template = Template::where('view_path', self::VIEW)->sole();

        $this->get(route('templates.preview', $template->slug))
            ->assertOk()
            // Datos de ejemplo de las secciones, no los del evento real.
            ->assertSee('Sofía')
            ->assertSee('Alejandro')
            ->assertDontSee('Zoe Hernández');
    }

    /* ---------------------------------------------------------------------
     | Helpers
     * -------------------------------------------------------------------*/

    private function guestUuid(string $name): string
    {
        $guest = Guest::factory()->create(['user_id' => $this->organizer->id, 'name' => $name]);
        $uuid = (string) Str::uuid();
        $guest->events()->attach($this->event->id, ['uuid' => $uuid, 'max_passes' => 2]);

        return $uuid;
    }

    private function features(): array
    {
        return [
            'general' => [
                'name_wife' => 'Zoe Hernández',
                'name_husband' => 'Harry Sánchez',
                'family_parents' => 'Sr. Luis Hernández y Sra. Ana Prado',
            ],
            'itinerary' => [
                'events' => [
                    [
                        'name' => 'Cena y fiesta',
                        'place_event' => 'Salón Los Arcos',
                        'location_event' => 'Av. Reforma 100',
                        'location_maps' => 'https://maps.google.com',
                        'date_event' => '2027-01-10 20:00:00',
                    ],
                    [
                        'name' => 'Ceremonia religiosa',
                        'place_event' => 'Parroquia de San José',
                        'location_event' => 'Calle Juárez 25',
                        'location_maps' => null,
                        'date_event' => '2027-01-10 15:30:00',
                    ],
                ],
            ],
            'dress_code' => [
                'dress_code_type' => 'Etiqueta',
                'women_attire' => 'Vestido largo.',
                'men_attire' => 'Traje oscuro.',
            ],
            'gift_registry' => [
                'section_title' => 'Nuestra mesa de regalos',
                'message' => 'Tu presencia es nuestro mejor regalo.',
                'registries' => [
                    [
                        'type' => 'cuenta_bancaria',
                        'banco' => 'BBVA',
                        'clabe' => '012180012345678901',
                    ],
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
