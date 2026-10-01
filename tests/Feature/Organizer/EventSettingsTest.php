<?php

namespace Tests\Feature\Organizer;

use App\Models\ColorPalette;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Services\EventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Pantalla de Configuración del organizador: cuenta, nombres de la pareja (y su
 * URL amigable), colores y opciones de confirmación.
 */
class EventSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;
    private Event $event;
    private ColorPalette $palette;

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

        [$this->organizer, $this->event] = $this->makeOrganizerWithEvent('Harry', 'Zoe', 'harry-y-zoe');

        $this->palette = ColorPalette::create([
            'name' => 'Clásica',
            'primary_color' => '#1E1E1E',
            'secondary_color' => '#F5E9DC',
            'accent_color' => '#FFFFFF',
            'is_active' => true,
        ]);
    }

    /* ---------------------------------------------------------------------
     | Pantalla
     * -------------------------------------------------------------------*/

    public function test_la_pantalla_muestra_los_datos_actuales(): void
    {
        $this->actingAs($this->organizer)
            ->get(route('organizer.settings.index'))
            ->assertOk()
            ->assertSee('Configuración')
            ->assertSee('Mr. Iguana')
            ->assertSee('value="Harry"', false)
            ->assertSee('value="Zoe"', false)
            ->assertSee('Clásica')
            // La dirección se edita: prefijo fijo + la parte que el organizador controla.
            ->assertSee(rtrim(Event::invitationUrlFor(''), '/'))
            ->assertSee('value="harry-y-zoe"', false)
            ->assertSee('Coadministradores');
    }

    public function test_el_menu_del_organizador_incluye_configuracion(): void
    {
        $this->actingAs($this->organizer)
            ->get(route('panel'))
            ->assertOk()
            ->assertSee('href="' . route('organizer.settings.index') . '"', false);
    }

    public function test_solo_el_organizador_entra(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('organizer.settings.index'))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------------
     | Guardado
     * -------------------------------------------------------------------*/

    public function test_guarda_cuenta_pareja_paleta_y_confirmacion(): void
    {
        $this->save(['name' => 'Alfonso Ponce', 'allow_children' => '1', 'open_link_max_passes' => 4])
            ->assertRedirect(route('organizer.settings.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Alfonso Ponce', $this->organizer->fresh()->name);

        $features = $this->event->fresh()->features;
        $this->assertSame(['Harry', 'Zoe'], $this->event->fresh()->urlNames());
        $this->assertSame($this->palette->id, $features['theme']['palette_id']);
        $this->assertSame('#1E1E1E', $features['theme']['primary_color']);
        $this->assertFalse($features['theme']['is_custom']);
        $this->assertTrue($features['rsvp']['allow_children']);
        $this->assertSame(4, $features['rsvp']['open_link_max_passes']);
    }

    public function test_desmarcar_ninos_se_guarda_como_no(): void
    {
        $this->save(['allow_children' => null])->assertSessionHasNoErrors();

        $this->assertFalse($this->event->fresh()->features['rsvp']['allow_children']);
    }

    public function test_no_borra_lo_que_el_wizard_guardo_en_confirmacion(): void
    {
        $this->event->update(['features' => ['rsvp' => ['welcome_message' => 'Bienvenidos']]]);

        $this->save()->assertSessionHasNoErrors();

        $rsvp = $this->event->fresh()->features['rsvp'];
        $this->assertSame('Bienvenidos', $rsvp['welcome_message']);
        $this->assertArrayHasKey('allow_children', $rsvp);
    }

    public function test_colores_personalizados(): void
    {
        $this->save([
            'custom_colors' => '1',
            'palette_id' => null,
            'primary_color' => '#aa0000',
            'secondary_color' => '#00bb00',
        ])->assertSessionHasNoErrors();

        $theme = $this->event->fresh()->features['theme'];
        $this->assertTrue($theme['is_custom']);
        $this->assertNull($theme['palette_id']);
        $this->assertSame('#AA0000', $theme['primary_color']);
        $this->assertSame('#00BB00', $theme['secondary_color']);
    }

    public function test_color_personalizado_invalido_no_se_guarda(): void
    {
        $this->save(['custom_colors' => '1', 'primary_color' => 'rojo', 'secondary_color' => '#00bb00'])
            ->assertSessionHasErrors('primary_color');

        $this->assertArrayNotHasKey('theme', $this->event->fresh()->features ?? []);
    }

    public function test_sin_personalizar_hay_que_elegir_una_paleta_activa(): void
    {
        $this->save(['palette_id' => null])->assertSessionHasErrors('palette_id');

        $inactive = ColorPalette::create([
            'name' => 'Retirada',
            'primary_color' => '#000000',
            'secondary_color' => '#111111',
            'accent_color' => '#222222',
            'is_active' => false,
        ]);

        $this->save(['palette_id' => $inactive->id])->assertSessionHasErrors('palette_id');
    }

    public function test_maximo_de_acompanantes_fuera_de_rango(): void
    {
        $this->save(['open_link_max_passes' => -1])->assertSessionHasErrors('open_link_max_passes');
        $this->save(['open_link_max_passes' => 21])->assertSessionHasErrors('open_link_max_passes');
    }

    public function test_nombres_de_pareja_obligatorios(): void
    {
        $this->save(['partner_1_name' => '', 'partner_2_name' => ''])
            ->assertSessionHasErrors(['partner_1_name', 'partner_2_name']);

        $this->assertSame('harry-y-zoe', $this->event->fresh()->custom_url);
    }

    /* ---------------------------------------------------------------------
     | URL amigable
     * -------------------------------------------------------------------*/

    public function test_cambiar_los_nombres_cambia_la_url_y_la_anterior_redirige(): void
    {
        $guest = $this->guestWithInvitation();
        $uuid = $guest->events()->first()->pivot->uuid;

        $this->save(['partner_1_name' => 'Ana', 'partner_2_name' => 'Luis'])->assertSessionHasNoErrors();

        $this->assertSame('ana-y-luis', $this->event->fresh()->custom_url);

        // Los enlaces que ya se enviaron siguen llegando a la invitación.
        $this->get('/invitacion/harry-y-zoe')
            ->assertStatus(301)
            ->assertRedirect(Event::invitationUrlFor('ana-y-luis'));
        $this->get("/invitacion/harry-y-zoe/{$uuid}")
            ->assertRedirect(Event::invitationUrlFor('ana-y-luis') . '/' . $uuid);
    }

    public function test_sin_cambiar_los_nombres_la_url_no_se_mueve(): void
    {
        // Tiene sufijo porque otra pareja tenía la base; aunque se libere, no cambia.
        $this->event->update(['custom_url' => 'harry-y-zoe-2']);

        $this->save()->assertSessionHasNoErrors();

        $this->assertSame('harry-y-zoe-2', $this->event->fresh()->custom_url);
        $this->assertDatabaseCount('event_url_redirects', 0);
    }

    public function test_otra_pareja_no_puede_quedarse_con_una_url_anterior(): void
    {
        $this->save(['partner_1_name' => 'Ana', 'partner_2_name' => 'Luis']);

        // harry-y-zoe sigue redirigiendo a este evento: la nueva pareja recibe sufijo.
        [$other, $otherEvent] = $this->makeOrganizerWithEvent('Otro', 'Evento', 'otro-y-evento');

        $this->actingAs($other)
            ->put(route('organizer.settings.update'), $this->payload(['name' => 'Otra']))
            ->assertSessionHasNoErrors();

        $this->assertSame('harry-y-zoe-2', $otherEvent->fresh()->custom_url);
        $this->get('/invitacion/harry-y-zoe')->assertRedirect(Event::invitationUrlFor('ana-y-luis'));
    }

    public function test_volver_a_los_nombres_anteriores_recupera_la_url(): void
    {
        $this->save(['partner_1_name' => 'Ana', 'partner_2_name' => 'Luis']);
        $this->save(['partner_1_name' => 'Harry', 'partner_2_name' => 'Zoe']);

        $this->assertSame('harry-y-zoe', $this->event->fresh()->custom_url);
        $this->assertDatabaseMissing('event_url_redirects', ['custom_url' => 'harry-y-zoe']);
        $this->assertDatabaseHas('event_url_redirects', ['custom_url' => 'ana-y-luis', 'event_id' => $this->event->id]);
        $this->get('/invitacion/ana-y-luis')->assertRedirect(Event::invitationUrlFor('harry-y-zoe'));
    }

    public function test_los_nombres_del_evento_no_mueven_la_direccion(): void
    {
        // Los del wizard llevan apellidos y son para la plantilla, no para la URL.
        $this->event->update(['features' => [
            'general' => ['name_wife' => 'Zoe Hernández Prado', 'name_husband' => 'Harry Sánchez Lomelí'],
        ]]);

        $this->save()->assertSessionHasNoErrors();

        $this->assertSame('harry-y-zoe', $this->event->fresh()->custom_url);
    }

    public function test_puede_escribir_su_propia_direccion(): void
    {
        $this->save(['custom_url' => 'Nuestra Boda 2027!'])->assertSessionHasNoErrors();

        // Se normaliza como cualquier URL amigable.
        $this->assertSame('nuestra-boda-2027', $this->event->fresh()->custom_url);
        $this->get('/invitacion/harry-y-zoe')->assertRedirect(Event::invitationUrlFor('nuestra-boda-2027'));
    }

    public function test_no_puede_tomar_una_direccion_ocupada(): void
    {
        [, $otherEvent] = $this->makeOrganizerWithEvent('Ana', 'Luis', 'ana-y-luis');
        // Al cambiarla, la anterior queda redirigiendo a ese evento.
        app(EventService::class)->changeCustomUrl($otherEvent, 'ana-y-luis-nueva');

        // Ni la actual de otra pareja, ni una anterior suya (sigue redirigiendo).
        foreach (['ana-y-luis-nueva', 'ana-y-luis'] as $taken) {
            $this->save(['custom_url' => $taken])->assertSessionHasErrors('custom_url');
        }

        $this->assertSame('harry-y-zoe', $this->event->fresh()->custom_url);
    }

    public function test_la_direccion_debe_tener_un_minimo(): void
    {
        $this->save(['custom_url' => 'ab'])->assertSessionHasErrors('custom_url');
    }

    public function test_la_vista_previa_sugiere_una_direccion_libre(): void
    {
        $this->makeOrganizerWithEvent('Ana', 'Luis', 'ana-y-luis');

        $this->actingAs($this->organizer)
            ->getJson(route('organizer.settings.url_preview', ['custom_url' => 'ana-y-luis']))
            ->assertOk()
            ->assertJson([
                'slug' => 'ana-y-luis',
                'available' => false,
                'suggestion' => 'ana-y-luis-2',
            ]);

        $this->actingAs($this->organizer)
            ->getJson(route('organizer.settings.url_preview', ['custom_url' => 'boda-libre']))
            ->assertJson(['available' => true, 'suggestion' => null, 'changed' => true]);
    }

    public function test_url_desconocida_da_404(): void
    {
        $this->get('/invitacion/no-existe')->assertNotFound();
    }

    public function test_la_vista_previa_avisa_si_la_url_cambiara(): void
    {
        $this->actingAs($this->organizer)
            ->getJson(route('organizer.settings.url_preview', ['partner_1_name' => 'Ana', 'partner_2_name' => 'Luis']))
            ->assertOk()
            ->assertJson(['url' => Event::invitationUrlFor('ana-y-luis'), 'changed' => true]);

        $this->actingAs($this->organizer)
            ->getJson(route('organizer.settings.url_preview', ['partner_1_name' => 'Harry', 'partner_2_name' => 'Zoe']))
            ->assertJson(['url' => Event::invitationUrlFor('harry-y-zoe'), 'changed' => false]);

        $this->assertSame('harry-y-zoe', $this->event->fresh()->custom_url, 'La vista previa no guarda nada.');
    }

    /* ---------------------------------------------------------------------
     | Helpers
     * -------------------------------------------------------------------*/

    private function save(array $overrides = [])
    {
        return $this->actingAs($this->organizer)
            ->from(route('organizer.settings.index'))
            ->put(route('organizer.settings.update'), $this->payload($overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Mr. Iguana',
            'partner_1_name' => 'Harry',
            'partner_2_name' => 'Zoe',
            'palette_id' => $this->palette->id,
            'allow_children' => '1',
            'open_link_max_passes' => 3,
        ], $overrides);
    }

    private function makeOrganizerWithEvent(string $partner1, string $partner2, string $customUrl): array
    {
        $user = User::factory()->create(['name' => 'Mr. Iguana']);
        $user->roles()->attach(Role::firstOrCreate(['name' => 'organizer'])->id);

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
            'custom_url' => $customUrl,
            'url_partner_1' => $partner1,
            'url_partner_2' => $partner2,
        ]);

        return [$user, $event];
    }

    private function guestWithInvitation(): Guest
    {
        $guest = Guest::factory()->create(['user_id' => $this->organizer->id]);
        $guest->events()->attach($this->event->id, ['uuid' => (string) Str::uuid(), 'max_passes' => 1]);

        return $guest;
    }
}
