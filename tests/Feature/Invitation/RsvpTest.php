<?php

namespace Tests\Feature\Invitation;

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
 * Confirmaciones que llegan desde la invitación pública.
 */
class RsvpTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW = 'build-templates.template-travel.index';
    private const URL = '/invitacion/harry-y-zoe/confirmar';

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
            'event_date' => now()->addMonths(3),
            'features' => $this->features(),
        ]);
    }

    public function test_un_invitado_confirma_desde_su_liga(): void
    {
        $uuid = $this->guestUuid('Familia Martínez', maxPasses: 4);

        $this->postJson(self::URL, [
            'uuid' => $uuid,
            'attendance' => 'confirmed',
            'passes' => 3,
            'dietary_restrictions' => 'Sin gluten',
            'answers' => ['¿Con qué canción te pararías a bailar?' => 'La Bamba'],
        ])->assertOk();

        $rsvp = Rsvp::sole();

        $this->assertSame('confirmed', $rsvp->attendance);
        $this->assertSame(3, $rsvp->confirmed_passes);
        $this->assertSame('Sin gluten', $rsvp->dietary_restrictions);
        $this->assertSame(['¿Con qué canción te pararías a bailar?' => 'La Bamba'], $rsvp->answers);
    }

    public function test_quien_no_asiste_no_aparta_lugares(): void
    {
        $uuid = $this->guestUuid('Familia Martínez', maxPasses: 4);

        $this->postJson(self::URL, ['uuid' => $uuid, 'attendance' => 'declined'])->assertOk();

        $this->assertSame(0, Rsvp::sole()->confirmed_passes);
    }

    public function test_no_se_pueden_confirmar_mas_lugares_de_los_asignados(): void
    {
        $uuid = $this->guestUuid('Familia Martínez', maxPasses: 2);

        $this->postJson(self::URL, ['uuid' => $uuid, 'attendance' => 'confirmed', 'passes' => 5])
            ->assertStatus(422)
            ->assertJsonValidationErrors('passes');

        $this->assertSame(0, Rsvp::count());
    }

    public function test_una_invitacion_no_confirma_dos_veces(): void
    {
        $uuid = $this->guestUuid('Familia Martínez', maxPasses: 2);

        $this->postJson(self::URL, ['uuid' => $uuid, 'attendance' => 'confirmed', 'passes' => 2])->assertOk();

        $this->postJson(self::URL, ['uuid' => $uuid, 'attendance' => 'confirmed', 'passes' => 1])
            ->assertStatus(409);

        $this->assertSame(1, Rsvp::count());
    }

    public function test_la_liga_abierta_da_de_alta_al_invitado(): void
    {
        $this->postJson(self::URL, [
            'name' => 'Ana Ruiz',
            'phone' => '5512345678',
            'attendance' => 'confirmed',
            'passes' => 1,
        ])->assertOk();

        $guest = Guest::where('name', 'Ana Ruiz')->sole();

        $this->assertSame($this->organizer->id, $guest->user_id);
        $this->assertSame(1, Rsvp::count());
    }

    public function test_la_liga_abierta_se_puede_apagar(): void
    {
        $features = $this->event->features;
        $features['rsvp']['enable_open_confirmation_link'] = false;
        $this->event->update(['features' => $features]);

        $this->postJson(self::URL, ['name' => 'Ana Ruiz', 'attendance' => 'confirmed', 'passes' => 1])
            ->assertStatus(403);

        $this->assertSame(0, Rsvp::count());
    }

    /* =====================================================================
     | Los novios mirando su propia invitación
     |
     | Entran por "Ver mi invitación" a ver cómo va quedando. La liga abierta no
     | identifica a nadie, así que una confirmación suya sería un invitado
     | inventado en su propia lista.
     * ===================================================================*/

    public function test_el_organizador_no_confirma_desde_su_propia_liga_abierta(): void
    {
        $this->actingAs($this->organizer)
            ->postJson(self::URL, ['name' => 'Ana Ruiz', 'attendance' => 'confirmed', 'passes' => 1])
            ->assertStatus(403)
            ->assertJsonFragment(['message' => 'Estás viendo tu propia invitación: desde aquí no se confirma. Comparte el enlace con tus invitados.']);

        $this->assertSame(0, Rsvp::count());
        $this->assertSame(0, Guest::count(), 'Tampoco se le da de alta como invitado.');
    }

    public function test_un_coadministrador_tampoco_confirma_desde_la_liga_abierta(): void
    {
        $coadmin = User::factory()->create();
        $coadmin->roles()->attach(Role::named('coadmin')->id);
        $this->event->coadmins()->create([
            'user_id' => $coadmin->id,
            'email' => $coadmin->email,
            'invited_by' => $this->organizer->id,
            'accepted_at' => now(),
        ]);

        $this->actingAs($coadmin)
            ->postJson(self::URL, ['name' => 'Ana Ruiz', 'attendance' => 'confirmed', 'passes' => 1])
            ->assertStatus(403);

        $this->assertSame(0, Rsvp::count());
    }

    /** Por su liga personal sí: ahí está respondiendo por ese invitado. */
    public function test_el_organizador_si_confirma_desde_una_liga_personal(): void
    {
        $uuid = $this->guestUuid('Familia Martínez', maxPasses: 2);

        $this->actingAs($this->organizer)
            ->postJson(self::URL, ['uuid' => $uuid, 'attendance' => 'confirmed', 'passes' => 2])
            ->assertOk();

        $this->assertSame(1, Rsvp::count());
    }

    /** Cualquier otra persona con la sesión abierta confirma como siempre. */
    public function test_otra_persona_con_sesion_confirma_normal(): void
    {
        $ajeno = User::factory()->create();

        $this->actingAs($ajeno)
            ->postJson(self::URL, ['name' => 'Ana Ruiz', 'attendance' => 'confirmed', 'passes' => 1])
            ->assertOk();

        $this->assertSame(1, Rsvp::count());
    }

    public function test_al_organizador_se_le_apaga_el_boton_de_confirmar(): void
    {
        $html = $this->actingAs($this->organizer)
            ->get('/invitacion/harry-y-zoe')
            ->assertOk()
            ->assertSee('Estás viendo tu propia invitación')
            ->getContent();

        // El formulario sigue pintándose: entran justamente a ver cómo queda.
        $this->assertStringContainsString('data-rsvp-form', $html);
        $this->assertMatchesRegularExpression('/<button type="submit"[^>]*disabled/', $html);
    }

    public function test_a_un_invitado_no_se_le_apaga_nada(): void
    {
        $html = $this->get('/invitacion/harry-y-zoe')->assertOk()->getContent();

        $this->assertStringNotContainsString('Estás viendo tu propia invitación', $html);
        $this->assertDoesNotMatchRegularExpression('/<button type="submit"[^>]*disabled/', $html);
    }

    /* =====================================================================
     | Lo que ve quien abre la invitación
     |
     | La liga abierta apagada y la fecha límite vencida son dos cosas
     | distintas y se explican distinto: una dice "sólo por tu invitación
     | personal" y la otra "ya se acabó el plazo".
     * ===================================================================*/

    public function test_sin_liga_abierta_no_se_pinta_el_formulario_a_quien_no_fue_invitado(): void
    {
        $this->apagarLigaAbierta();

        $this->get('/invitacion/harry-y-zoe')
            ->assertOk()
            ->assertSee('desde la invitación personal', false)
            // Y no se le deja llenar nada para rechazarlo al final.
            ->assertDontSee('data-rsvp-form', false)
            ->assertDontSee('La fecha límite para confirmar ya pasó');
    }

    public function test_sin_liga_abierta_quien_llega_por_la_suya_si_confirma(): void
    {
        $this->apagarLigaAbierta();
        $uuid = $this->guestUuid('Familia Martínez', maxPasses: 2);

        $this->get('/invitacion/harry-y-zoe/' . $uuid)
            ->assertOk()
            ->assertSee('data-rsvp-form', false)
            ->assertDontSee('desde la invitación personal', false);
    }

    public function test_la_fecha_limite_manda_sobre_la_liga_abierta(): void
    {
        // Vencido el plazo ya no confirma nadie, ni siquiera con su liga: ése
        // es el motivo que hay que explicar.
        $this->apagarLigaAbierta();
        $features = $this->event->features;
        $features['rsvp']['rsvp_deadline'] = now()->subDay()->toDateTimeString();
        $this->event->update(['features' => $features]);

        $this->get('/invitacion/harry-y-zoe')
            ->assertOk()
            ->assertSee('La fecha límite para confirmar ya pasó')
            ->assertDontSee('desde la invitación personal', false);
    }

    public function test_con_liga_abierta_cualquiera_ve_el_formulario(): void
    {
        $this->get('/invitacion/harry-y-zoe')
            ->assertOk()
            ->assertSee('data-rsvp-form', false)
            ->assertDontSee('desde la invitación personal', false);
    }

    private function apagarLigaAbierta(): void
    {
        $features = $this->event->features;
        $features['rsvp']['enable_open_confirmation_link'] = false;
        $this->event->update(['features' => $features]);
    }

    public function test_pasada_la_fecha_limite_ya_no_se_recibe(): void
    {
        $features = $this->event->features;
        $features['rsvp']['rsvp_deadline'] = now()->subDay()->toDateTimeString();
        $this->event->update(['features' => $features]);

        $this->postJson(self::URL, ['name' => 'Ana Ruiz', 'attendance' => 'confirmed', 'passes' => 1])
            ->assertStatus(422);
    }

    public function test_una_invitacion_vencida_no_recibe_confirmaciones(): void
    {
        $this->event->update(['event_date' => now()->subMonths(3)]);

        $this->postJson(self::URL, ['name' => 'Ana Ruiz', 'attendance' => 'confirmed', 'passes' => 1])
            ->assertStatus(410);
    }

    public function test_el_formulario_pide_lo_indispensable(): void
    {
        $this->postJson(self::URL, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'attendance']);
    }

    private function guestUuid(string $name, int $maxPasses): string
    {
        $guest = Guest::factory()->create(['user_id' => $this->organizer->id, 'name' => $name]);
        $uuid = (string) Str::uuid();

        $guest->events()->attach($this->event->id, ['uuid' => $uuid, 'max_passes' => $maxPasses]);

        return $uuid;
    }

    private function features(): array
    {
        return [
            'general' => ['name_wife' => 'Zoe', 'name_husband' => 'Harry'],
            'rsvp' => [
                'rsvp_deadline' => now()->addMonth()->toDateTimeString(),
                'enable_open_confirmation_link' => true,
                'ask_dietary_requirements' => true,
            ],
        ];
    }
}
