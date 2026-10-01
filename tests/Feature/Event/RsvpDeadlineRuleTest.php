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
 * La fecha límite para confirmar, contra la fecha de la boda.
 *
 * Son dos pasos distintos del wizard: la boda se captura en el primero y vive
 * en events.event_date; el límite se captura en el de confirmación. Por eso la
 * regla no cabe en el campo —que se declara una vez para cualquier boda— y la
 * pone la sección en rulesFor(), que sí recibe el evento.
 */
class RsvpDeadlineRuleTest extends TestCase
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
        $this->organizer->roles()->attach(
            Role::firstOrCreate(['name' => 'organizer'], ['display_name' => 'Organizador'])->id
        );

        // Sin view_path se resuelve DefaultTemplateStrategy, que trae el paso rsvp.
        $this->event = Event::factory()->create([
            'user_id' => $this->organizer->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
            'event_date' => '2026-12-01 18:00:00',
            'features' => null,
        ]);
    }

    public function test_una_fecha_limite_posterior_a_la_boda_no_se_guarda(): void
    {
        $this->guardar(['rsvp_deadline' => '2026-12-02 10:00:00'])
            ->assertSessionHasErrors('rsvp_deadline');

        $this->assertNull($this->event->fresh()->features);
    }

    public function test_una_fecha_limite_anterior_a_la_boda_si_se_guarda(): void
    {
        $this->guardar(['rsvp_deadline' => '2026-11-15 20:00:00'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-11-15 20:00:00', $this->event->fresh()->features['rsvp']['rsvp_deadline']);
    }

    public function test_el_mismo_dia_de_la_boda_vale_a_cualquier_hora(): void
    {
        /*
        | La boda es a las 18:00 y el límite a las 23:00 del mismo día: se
        | compara el día, no la hora. Poner como límite el día de la boda es
        | decisión del organizador, y a estas alturas nadie va a confirmar
        | después de la ceremonia de todos modos.
        */
        $this->guardar(['rsvp_deadline' => '2026-12-01 23:00:00'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-12-01 23:00:00', $this->event->fresh()->features['rsvp']['rsvp_deadline']);
    }

    public function test_sin_fecha_de_boda_todavia_no_hay_contra_que_comparar(): void
    {
        // El primer paso está a medias: ahí se le va a exigir la fecha de todos
        // modos, y mientras tanto el de confirmación no se bloquea.
        $this->event->update(['event_date' => null]);

        $this->guardar(['rsvp_deadline' => '2027-05-30 10:00:00'])
            ->assertSessionHasNoErrors();
    }

    public function test_una_fecha_limite_que_no_es_fecha_no_pasa(): void
    {
        // El control deja escribir a mano, así que puede llegar cualquier cosa.
        $this->guardar(['rsvp_deadline' => 'el mes que viene'])
            ->assertSessionHasErrors('rsvp_deadline');

        $this->assertNull($this->event->fresh()->features);
    }

    public function test_la_regla_no_le_quita_al_campo_lo_que_ya_pedia(): void
    {
        // Sumar la comparación no debe borrar el 'required' del campo.
        $this->guardar(['rsvp_deadline' => ''])
            ->assertSessionHasErrors('rsvp_deadline');
    }

    /* =====================================================================
     | Cuando mueven la boda, no al revés
     |
     | Adelantar la boda a antes del límite deja un límite imposible. No se
     | bloquea —el organizador tiene todo el derecho de corregir la fecha de su
     | boda— sino que se recorre el límite y se le avisa.
     * ===================================================================*/

    public function test_adelantar_la_boda_recorre_la_fecha_limite_y_avisa(): void
    {
        $this->guardar(['rsvp_deadline' => '2026-11-15 20:00:00'])->assertSessionHasNoErrors();

        // La boda se adelanta a antes de ese límite.
        $this->guardarGeneral('2026-11-01 17:30:00')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('warning', fn (?string $aviso) => $aviso !== null
                && str_contains($aviso, 'anterior a la fecha límite'));

        $event = $this->event->fresh();

        $this->assertSame('2026-11-01 17:30:00', $event->features['rsvp']['rsvp_deadline']);
        $this->assertSame('2026-11-01 17:30:00', $event->event_date->format('Y-m-d H:i:s'));
    }

    public function test_atrasar_la_boda_no_toca_la_fecha_limite(): void
    {
        $this->guardar(['rsvp_deadline' => '2026-11-15 20:00:00'])->assertSessionHasNoErrors();

        // La boda se va más lejos: el límite sigue siendo válido.
        $this->guardarGeneral('2027-03-20 17:30:00')->assertSessionMissing('warning');

        $this->assertSame('2026-11-15 20:00:00', $this->event->fresh()->features['rsvp']['rsvp_deadline']);
    }

    public function test_sin_fecha_limite_capturada_no_hay_nada_que_recorrer(): void
    {
        // Todavía no llega a ese paso: cambiar la boda no debe inventarle una.
        $this->guardarGeneral('2026-11-01 17:30:00')->assertSessionMissing('warning');

        $this->assertArrayNotHasKey('rsvp', $this->event->fresh()->features ?? []);
    }

    /** Guarda el primer paso con la fecha de boda que se le pase. */
    private function guardarGeneral(string $fechaBoda)
    {
        return $this->actingAs($this->organizer)
            ->from(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'general']))
            ->put(route('events.wizard.update', ['event' => $this->event->slug, 'step' => 'general']), [
                'name_event' => 'Boda Ana y Luis',
                'name_wife' => 'Ana',
                'name_husband' => 'Luis',
                'family_parents' => 'Familia Pérez y Familia López',
                'date_event_person' => $fechaBoda,
            ]);
    }

    /** Guarda el paso de confirmación con lo mínimo que pide, más lo que se pruebe. */
    private function guardar(array $overrides)
    {
        $datos = array_merge([
            'rsvp_deadline' => '2026-11-15 20:00:00',
            'welcome_message' => 'Esperamos contar contigo.',
            'thank_you_message' => '¡Gracias por confirmar!',
            'ask_dietary_requirements' => '1',
            'enable_open_confirmation_link' => '1',
        ], $overrides);

        return $this->actingAs($this->organizer)
            ->from(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'rsvp']))
            ->put(route('events.wizard.update', ['event' => $this->event->slug, 'step' => 'rsvp']), $datos);
    }
}
