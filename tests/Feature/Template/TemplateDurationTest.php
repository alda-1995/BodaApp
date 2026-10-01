<?php

namespace Tests\Feature\Template;

use App\Models\Event;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Días de vigencia por plantilla: el superadmin los define y de ahí sale la
 * fecha en que la invitación se deshabilita.
 */
class TemplateDurationTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private Template $template;

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
        config(['events.default_duration_days' => 21]);

        $this->superadmin = User::factory()->create();
        $this->superadmin->roles()->attach(Role::named('superadmin')->id);

        $this->template = Template::factory()->create([
            'view_path' => 'build-templates.template-travel.index',
            'duration_days' => null,
        ]);
    }

    public function test_el_formulario_muestra_el_campo(): void
    {
        $this->actingAs($this->superadmin)
            ->get(route('templates.edit', $this->template->id))
            ->assertOk()
            ->assertSee('Días de vigencia después de la boda');
    }

    public function test_guarda_los_dias_de_vigencia(): void
    {
        $this->update(['duration_days' => 45])->assertSessionHasNoErrors();

        $this->assertSame(45, $this->template->fresh()->duration_days);
    }

    public function test_esos_dias_definen_cuando_vence_la_invitacion(): void
    {
        $this->update(['duration_days' => 45]);

        $event = Event::factory()->create([
            'template_id' => $this->template->id,
            'event_date' => '2027-01-10 18:00:00',
        ]);

        $this->assertSame('2027-02-24', $event->fresh()->expires_at->format('Y-m-d'));
    }

    public function test_vacio_usa_los_dias_por_defecto(): void
    {
        $this->update(['duration_days' => null])->assertSessionHasNoErrors();

        $event = Event::factory()->create([
            'template_id' => $this->template->id,
            'event_date' => '2027-01-10 18:00:00',
        ]);

        $this->assertNull($this->template->fresh()->duration_days);
        $this->assertSame('2027-01-31', $event->fresh()->expires_at->format('Y-m-d'));
    }

    public function test_rechaza_valores_invalidos(): void
    {
        foreach ([0, 400, 'muchos'] as $invalid) {
            $this->update(['duration_days' => $invalid])->assertSessionHasErrors('duration_days');
        }

        $this->assertNull($this->template->fresh()->duration_days);
    }

    private function update(array $overrides = [])
    {
        return $this->actingAs($this->superadmin)
            ->from(route('templates.edit', $this->template->id))
            ->put(route('templates.update', $this->template->id), array_merge([
                'name' => $this->template->name,
                // Mismo precio: así no se toca Stripe.
                'price' => $this->template->price,
                'active' => '1',
            ], $overrides));
    }
}
