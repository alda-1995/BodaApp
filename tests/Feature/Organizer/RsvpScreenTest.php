<?php

namespace Tests\Feature\Organizer;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Role;
use App\Models\Rsvp;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Pantalla "Confirmación de Asistencia" del organizador.
 */
class RsvpScreenTest extends TestCase
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

        $this->seed(RolesAndAdminSeeder::class);

        $this->organizer = User::factory()->create();
        $this->organizer->roles()->attach(Role::named('organizer')->id);

        $this->event = Event::factory()->create([
            'user_id' => $this->organizer->id,
            'template_id' => Template::factory()->create(['view_path' => 'build-templates.template-travel.index'])->id,
            'custom_url' => 'harry-y-zoe',
            'event_date' => now()->addMonths(3),
        ]);
    }

    public function test_muestra_el_resumen_y_a_quien_confirmo(): void
    {
        $this->invite('Ema Zamorano', attendance: 'confirmed', passes: 1, dietary: 'No carne roja');
        $this->invite('Raquel Ruiz', attendance: 'confirmed', passes: 2);
        $this->invite('Rosario Álvarez', attendance: 'declined');
        $this->invite('Pendiente Pérez');

        $this->actingAs($this->organizer)
            ->get(route('organizer.rsvps.index'))
            ->assertOk()
            // 3 respondieron, 3 personas confirmadas, 1 sin responder.
            ->assertSeeInOrder(['Confirmaciones recibidas', '3'])
            ->assertSeeInOrder(['Total de personas confirmadas', '3'])
            ->assertSeeInOrder(['Pendientes de responder', '1'])
            ->assertSee('Ema Zamorano')
            ->assertSee('No carne roja')
            ->assertDontSee('Pendiente Pérez');
    }

    public function test_la_otra_pestana_junta_a_quien_falta_y_a_quien_dijo_que_no(): void
    {
        $this->invite('Ema Zamorano', attendance: 'confirmed', passes: 1);
        $this->invite('Rosario Álvarez', attendance: 'declined');
        $this->invite('Pendiente Pérez');

        $this->actingAs($this->organizer)
            ->get(route('organizer.rsvps.index', ['status' => 'not_confirmed']))
            ->assertOk()
            ->assertSee('Pendiente Pérez')
            ->assertSee('Sin responder')
            ->assertSee('No asistirá')
            ->assertDontSee('Ema Zamorano');
    }

    public function test_la_columna_toma_el_nombre_de_la_pregunta_del_evento(): void
    {
        $this->invite('Ema Zamorano', attendance: 'confirmed', passes: 1, answers: [
            '¿Con qué canción te pararías a bailar?' => 'Algo de los ángeles',
        ]);

        $this->actingAs($this->organizer)
            ->get(route('organizer.rsvps.index'))
            ->assertOk()
            ->assertSee('¿Con qué canción te pararías a bailar?', false)
            ->assertSee('Algo de los ángeles');
    }

    public function test_se_puede_buscar_por_nombre(): void
    {
        $this->invite('Ema Zamorano', attendance: 'confirmed', passes: 1);
        $this->invite('Raquel Ruiz', attendance: 'confirmed', passes: 2);

        $this->actingAs($this->organizer)
            ->get(route('organizer.rsvps.index', ['search' => 'Raquel']))
            ->assertOk()
            ->assertSee('Raquel Ruiz')
            ->assertDontSee('Ema Zamorano');
    }

    public function test_descarga_el_reporte_de_confirmados(): void
    {
        $this->invite('Ema Zamorano', attendance: 'confirmed', passes: 1);

        $this->actingAs($this->organizer)
            ->get(route('organizer.rsvps.export'))
            ->assertOk()
            ->assertDownload('confirmados-harry-y-zoe.xlsx');
    }

    public function test_solo_se_ven_las_confirmaciones_propias(): void
    {
        $otro = User::factory()->create();
        $otro->roles()->attach(Role::named('organizer')->id);

        $this->invite('Ema Zamorano', attendance: 'confirmed', passes: 1);

        // Sin invitación vigente propia, el middleware ni siquiera lo deja entrar.
        $response = $this->actingAs($otro)->get(route('organizer.rsvps.index'));

        $response->assertRedirect();
        $this->assertStringNotContainsString('Ema Zamorano', $response->getContent());
    }

    private function invite(
        string $name,
        ?string $attendance = null,
        int $passes = 0,
        ?string $dietary = null,
        array $answers = [],
    ): void {
        $guest = Guest::factory()->create([
            'user_id' => $this->organizer->id,
            'name' => $name,
            'phone' => '5512345678',
        ]);

        $guest->events()->attach($this->event->id, ['uuid' => (string) Str::uuid(), 'max_passes' => 2]);

        if (!$attendance) {
            return;
        }

        Rsvp::create([
            'event_guest_id' => $guest->invitationFor($this->event)->id,
            'attendance' => $attendance,
            'confirmed_passes' => $passes,
            'dietary_restrictions' => $dietary,
            'answers' => $answers ?: null,
            'confirmed_at' => now(),
        ]);
    }
}
