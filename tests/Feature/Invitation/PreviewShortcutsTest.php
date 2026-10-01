<?php

namespace Tests\Feature\Invitation;

use App\Models\Event;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Services\CoadminService;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Atajos para ver la plantilla: el superadmin abre la vista previa con datos de
 * ejemplo y el organizador su propia invitación.
 */
class PreviewShortcutsTest extends TestCase
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
            'event_date' => now()->addMonths(4),
        ]);
    }

    public function test_el_home_ofrece_el_demo_de_cada_plantilla(): void
    {
        $template = $this->event->template;
        $demo = route('templates.preview', $template->slug);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Ver demo')
            ->assertSee('href="' . $demo . '"', false);

        // Y también antes de pagar, desde el resumen de la plantilla.
        $this->get(route('checkout.checkout-preview', $template->slug))
            ->assertOk()
            ->assertSee('Ver demo de la plantilla')
            ->assertSee('href="' . $demo . '"', false);
    }

    public function test_el_home_no_ofrece_plantillas_inactivas(): void
    {
        $this->event->template->update(['is_active' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('href="' . route('templates.preview', $this->event->template->slug) . '"', false);
    }

    public function test_el_superadmin_abre_la_vista_previa_desde_el_catalogo(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->roles()->attach(Role::named('superadmin')->id);
        // El modelo genera el slug a partir del nombre.
        $preview = route('templates.preview', $this->event->template->slug);

        $this->actingAs($superadmin)->get(route('templates.index'))
            ->assertOk()
            ->assertSee('href="' . $preview . '"', false);

        $this->actingAs($superadmin)->get(route('templates.edit', $this->event->template_id))
            ->assertOk()
            ->assertSee('Ver vista previa')
            ->assertSee('href="' . $preview . '"', false);
    }

    public function test_el_organizador_abre_su_invitacion_desde_el_panel_y_el_wizard(): void
    {
        $link = 'href="' . $this->event->invitationUrl() . '"';

        $this->actingAs($this->organizer)->get(route('panel'))
            ->assertOk()
            ->assertSee('Ver mi invitación')
            ->assertSee($link, false);

        $this->actingAs($this->organizer)
            ->get(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'general']))
            ->assertOk()
            ->assertSee('Ver mi invitación')
            ->assertSee($link, false);

        $this->actingAs($this->organizer)->get(route('organizer.settings.index'))
            ->assertOk()
            ->assertSee('Abrir mi invitación')
            ->assertSee($link, false);
    }

    public function test_el_coadministrador_abre_la_invitacion_compartida(): void
    {
        $coadmin = User::factory()->create(['email' => 'elena@email.com']);
        app(CoadminService::class)->accept(
            $this->event->coadmins()->create(['email' => 'elena@email.com']),
            $coadmin,
        );

        $this->actingAs($coadmin)->get(route('shared-events.index'))
            ->assertOk()
            ->assertSee('Ver invitación')
            ->assertSee('href="' . $this->event->invitationUrl() . '"', false);
    }
}
