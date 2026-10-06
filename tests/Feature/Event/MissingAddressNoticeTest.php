<?php

namespace Tests\Feature\Event;

use App\Models\Event;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Cuando la invitación todavía no tiene dirección (custom_url).
 *
 * Sin ella la invitación no existe para nadie: la página pública sólo se
 * encuentra por su custom_url. Un evento puede llegar así —quien compra una
 * segunda boda ya tiene cuenta y no pasa por el onboarding que la pedía—, y
 * entonces el botón de "Ver mi invitación" no se puede ofrecer.
 *
 * No se genera sola a propósito: la dirección es de la pareja y es la que van
 * a repartir, así que se les invita a elegirla en Configuración.
 */
class MissingAddressNoticeTest extends TestCase
{
    use RefreshDatabase;

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

        $this->organizer = User::factory()->create();
        $this->organizer->roles()->attach(Role::firstOrCreate(['name' => 'organizer'])->id);

        // Un evento recién creado por la compra: todavía sin dirección.
        $this->event = Event::factory()->create([
            'user_id' => $this->organizer->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
            'custom_url' => null,
            'event_date' => now()->addMonths(5),
        ]);
    }

    /* ---------------------------------------------------------------------
     | Sin dirección: se invita a elegirla
     * -------------------------------------------------------------------*/

    public function test_el_wizard_invita_a_elegir_la_direccion(): void
    {
        $this->verWizard()
            ->assertOk()
            ->assertSee('Elegir la dirección de mi invitación')
            ->assertSee(route('organizer.settings.index'))
            // No se ofrece un botón que llevaría a un 404.
            ->assertDontSee('Ver mi invitación');
    }

    public function test_el_dashboard_invita_a_elegir_la_direccion(): void
    {
        $this->verDashboard()
            ->assertOk()
            ->assertSee('Tu invitación todavía no tiene dirección')
            ->assertSee('Ir a Configuración')
            ->assertSee(route('organizer.settings.index'))
            ->assertDontSee('Ver mi invitación');
    }

    public function test_el_aviso_lleva_a_la_pantalla_donde_se_elige(): void
    {
        // La invitación no sirve de nada si manda a un sitio sin el campo.
        $this->actingAs($this->organizer)
            ->get(route('organizer.settings.index'))
            ->assertOk()
            ->assertSee('name="custom_url"', false);
    }

    /* ---------------------------------------------------------------------
     | Con dirección: el aviso desaparece y vuelve el botón
     * -------------------------------------------------------------------*/

    public function test_con_direccion_vuelve_el_boton_y_se_va_el_aviso(): void
    {
        $this->event->update(['custom_url' => 'juana-y-juano']);

        foreach ([$this->verWizard(), $this->verDashboard()] as $pantalla) {
            $pantalla->assertOk()
                ->assertSee('Ver mi invitación')
                ->assertSee(Event::invitationUrlFor('juana-y-juano'))
                ->assertDontSee('Tu invitación todavía no tiene dirección')
                ->assertDontSee('Elegir la dirección de mi invitación');
        }
    }

    public function test_el_wizard_no_le_inventa_una_direccion_al_guardar(): void
    {
        // La dirección la elige la pareja, no el wizard: es la que repartirán.
        $this->actingAs($this->organizer)
            ->from(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'general']))
            ->put(route('events.wizard.update', ['event' => $this->event->slug, 'step' => 'general']), [
                'name_event' => 'Boda Juana y Juano',
                'name_wife' => 'Juana',
                'name_husband' => 'Juano',
                'family_parents' => 'Sus papás',
                'date_event_person' => now()->addMonths(6)->toDateTimeString(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($this->event->fresh()->custom_url);
    }

    /* ------------------------------------------------------------------ */

    private function verWizard()
    {
        return $this->actingAs($this->organizer)
            ->get(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'general']));
    }

    private function verDashboard()
    {
        return $this->actingAs($this->organizer)->get(route('panel'));
    }
}
