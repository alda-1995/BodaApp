<?php

namespace Tests\Feature\Invitation;

use App\Models\Event;
use App\Models\EventGuest;
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
 * Quien ya confirmó desde su invitación personal no vuelve a ver el formulario.
 *
 * Volver a enseñárselo invita a responder dos veces, y ya respondió. Se le da
 * las gracias, que es lo que ve justo después de enviarlo.
 *
 * Sólo aplica a la liga personal: la liga abierta no identifica a nadie, así
 * que no hay forma de saber si quien la abre ya había contestado.
 */
class RsvpAlreadyConfirmedTest extends TestCase
{
    use RefreshDatabase;

    /** Las plantillas que arman su confirmación con el contrato de capas. */
    public static function plantillas(): array
    {
        return [
            'Boda Editorial' => ['build-templates.template-editorial.index', 'te-rsvp__status'],
            'Boda Destino' => ['build-templates.template-destino.index', 'td-rsvp__status'],
        ];
    }

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
    }

    /**
     * @dataProvider plantillas
     */
    public function test_quien_ya_confirmo_ve_el_agradecimiento_y_no_el_formulario(string $vista, string $capa): void
    {
        $this->makeEvent($vista);
        $uuid = $this->invitacion();
        $this->confirmar($uuid);

        $html = $this->get("/invitacion/harry-y-zoe/{$uuid}")->assertOk()->getContent();

        $this->assertStringNotContainsString('data-rsvp-form', $html, 'El formulario no debería pintarse.');
        $this->assertStringContainsString('Gracias por confirmar', $html);
        // Y la capa llega ya encendida: no depende de que corra el JS.
        $this->assertMatchesRegularExpression(
            '/class="' . $capa . ' is-visible"[^>]*data-rsvp-status="confirmed"/',
            $html,
        );
    }

    /**
     * @dataProvider plantillas
     */
    public function test_quien_todavia_no_confirma_sigue_viendo_el_formulario(string $vista): void
    {
        $this->makeEvent($vista);
        $uuid = $this->invitacion();

        $html = $this->get("/invitacion/harry-y-zoe/{$uuid}")->assertOk()->getContent();

        $this->assertStringContainsString('data-rsvp-form', $html);
        $this->assertStringNotContainsString('is-visible', $html);
    }

    /**
     * @dataProvider plantillas
     */
    public function test_la_liga_abierta_no_se_ve_afectada(string $vista): void
    {
        $this->makeEvent($vista);
        // Alguien confirmó, pero no es quien abre la liga general.
        $this->confirmar($this->invitacion());

        $html = $this->get('/invitacion/harry-y-zoe')->assertOk()->getContent();

        $this->assertStringContainsString('data-rsvp-form', $html);
    }

    /**
     * @dataProvider plantillas
     */
    public function test_a_quien_ya_confirmo_no_se_le_dice_que_la_fecha_paso(string $vista): void
    {
        // Respondió a tiempo; que el plazo venciera después no es asunto suyo.
        // Se guarda directo porque el endpoint —con razón— ya no la aceptaría.
        $this->makeEvent($vista, deadline: now()->subDay());
        $uuid = $this->invitacion();
        $this->confirmarEnBd($uuid);

        $html = $this->get("/invitacion/harry-y-zoe/{$uuid}")->assertOk()->getContent();

        $this->assertStringNotContainsString('La fecha límite para confirmar ya pasó', $html);
        $this->assertStringContainsString('Gracias por confirmar', $html);
    }

    /* ------------------------------------------------------------------ */

    private function makeEvent(string $vista, $deadline = null): void
    {
        $this->event = Event::factory()->create([
            'user_id' => $this->organizer->id,
            'template_id' => Template::factory()->create(['view_path' => $vista])->id,
            'custom_url' => 'harry-y-zoe',
            'event_date' => now()->addMonths(3),
            'is_active' => true,
            'features' => [
                'general' => ['name_wife' => 'Zoe', 'name_husband' => 'Harry'],
                'rsvp' => [
                    'rsvp_deadline' => ($deadline ?? now()->addMonth())->toDateTimeString(),
                    'enable_open_confirmation_link' => true,
                    'thank_you_message' => 'Nos vemos en Mérida.',
                ],
            ],
        ]);
    }

    /** Una invitación personal, con su liga propia. */
    private function invitacion(): string
    {
        $guest = Guest::factory()->create([
            'user_id' => $this->organizer->id,
            'name' => 'Familia Martínez',
        ]);

        $uuid = (string) Str::uuid();
        $guest->events()->attach($this->event->id, ['uuid' => $uuid, 'max_passes' => 2]);

        return $uuid;
    }

    private function confirmar(string $uuid): void
    {
        $this->postJson('/invitacion/harry-y-zoe/confirmar', [
            'uuid' => $uuid,
            'attendance' => 'confirmed',
            'passes' => 2,
        ])->assertOk();

        $this->assertSame(1, Rsvp::count());
    }

    /** La misma confirmación, pero sin pasar por el endpoint. */
    private function confirmarEnBd(string $uuid): void
    {
        Rsvp::create([
            'event_guest_id' => EventGuest::where('uuid', $uuid)->sole()->id,
            'attendance' => 'confirmed',
            'confirmed_passes' => 2,
            'confirmed_at' => now()->subWeek(),
        ]);
    }
}
