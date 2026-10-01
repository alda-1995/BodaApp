<?php

namespace Tests\Feature\Organizer;

use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestNotification;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Rsvp;
use App\Models\Template;
use App\Models\User;
use App\Services\Organizer\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Las cifras del panel del organizador.
 *
 * La maqueta traía números escritos a mano (80 enviadas, 106 confirmaciones).
 * Aquí se fija de dónde sale cada uno de verdad.
 */
class DashboardMetricsTest extends TestCase
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

        $this->event = Event::factory()->create([
            'user_id' => $this->organizer->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
            'custom_url' => 'ana-y-luis',
            'event_date' => now()->addDays(45),
        ]);
    }

    private function metrics(): array
    {
        return app(DashboardMetrics::class)->for($this->event->fresh());
    }

    /** Un invitado de este evento, con su liga personal. */
    private function invitado(string $nombre, int $lugares = 1): Guest
    {
        $guest = Guest::factory()->create(['user_id' => $this->organizer->id, 'name' => $nombre]);

        $guest->events()->attach($this->event->id, [
            'uuid' => (string) Str::uuid(),
            'max_passes' => $lugares,
        ]);

        return $guest;
    }

    /** Deja constancia de un envío a ese invitado, como lo haría el servicio. */
    private function envio(Guest $guest, string $estado = GuestNotification::STATUS_SENT): void
    {
        $notification = Notification::create([
            'event_id' => $this->event->id,
            'title' => 'Invitación',
            'message' => 'Te esperamos',
            'channel' => 'email',
        ]);

        GuestNotification::create([
            'guest_id' => $guest->id,
            'notification_id' => $notification->id,
            'status' => $estado,
            'sent_at' => $estado === GuestNotification::STATUS_SENT ? now() : null,
        ]);
    }

    private function respuesta(Guest $guest, string $asistencia, int $lugares = 1): void
    {
        Rsvp::create([
            'event_guest_id' => $guest->events()->where('event_id', $this->event->id)->first()->pivot->id,
            'attendance' => $asistencia,
            'confirmed_passes' => $lugares,
            'confirmed_at' => now(),
        ]);
    }

    /* =====================================================================
     | Cuenta regresiva
     * ===================================================================*/

    public function test_la_cuenta_regresiva_sale_de_la_fecha_de_la_boda(): void
    {
        $this->assertSame('45 días', $this->metrics()['countdown']);
    }

    public function test_sin_fecha_no_hay_cuenta_regresiva(): void
    {
        // Un "0 días" se leería como que la boda es hoy.
        $this->event->update(['event_date' => null]);

        $this->assertSame('Sin fecha', $this->metrics()['countdown']);
    }

    public function test_el_dia_de_la_boda_y_despues(): void
    {
        $this->event->update(['event_date' => now()]);
        $this->assertSame('¡Es hoy!', $this->metrics()['countdown']);

        $this->event->update(['event_date' => now()->subWeek()]);
        $this->assertSame('Ya se casaron', $this->metrics()['countdown']);
    }

    /* =====================================================================
     | Invitaciones
     * ===================================================================*/

    public function test_cuenta_invitados_alcanzados_no_mensajes_enviados(): void
    {
        $betsy = $this->invitado('Betsy');
        $this->invitado('Carlos');
        $this->invitado('Diana');

        // A Betsy se le mandó la invitación y después un recordatorio: sigue
        // siendo una sola invitada alcanzada.
        $this->envio($betsy);
        $this->envio($betsy);

        $cifras = $this->metrics();

        $this->assertSame(3, $cifras['guests']);
        $this->assertSame(1, $cifras['sent']);
        $this->assertSame(2, $cifras['pending']);
    }

    public function test_un_envio_fallido_no_cuenta_como_alcanzado(): void
    {
        $carlos = $this->invitado('Carlos');
        $this->envio($carlos, GuestNotification::STATUS_FAILED);

        $cifras = $this->metrics();

        $this->assertSame(0, $cifras['sent']);
        $this->assertSame(1, $cifras['pending']);
    }

    public function test_los_pendientes_nunca_bajan_de_cero(): void
    {
        // Si se borra a un invitado ya notificado, la resta se iría negativa.
        $betsy = $this->invitado('Betsy');
        $this->envio($betsy);
        $this->event->guests()->detach($betsy->id);

        $this->assertSame(0, $this->metrics()['pending']);
    }

    /* =====================================================================
     | Confirmaciones
     * ===================================================================*/

    public function test_separa_a_quien_confirma_de_quien_no_puede_ir(): void
    {
        $betsy = $this->invitado('Betsy', lugares: 2);
        $carlos = $this->invitado('Carlos');
        $this->invitado('Diana');

        $this->respuesta($betsy, 'confirmed', lugares: 2);
        $this->respuesta($carlos, 'declined');

        $cifras = $this->metrics();

        $this->assertSame(1, $cifras['confirmed'], 'Sólo Betsy dijo que sí.');
        $this->assertSame(2, $cifras['confirmed_passes'], 'Y apartó dos lugares.');
        $this->assertSame(3, $cifras['guests']);
    }

    /* =====================================================================
     | Cupo de mensajes
     * ===================================================================*/

    public function test_los_mensajes_restantes_salen_del_cupo_del_mes(): void
    {
        config(['notifications.monthly_quota' => 10]);

        $betsy = $this->invitado('Betsy');
        $this->envio($betsy);
        $this->envio($betsy);

        $this->assertSame(8, $this->metrics()['remaining_messages']);
    }

    /* =====================================================================
     | La pantalla
     * ===================================================================*/

    public function test_el_panel_pinta_las_cifras_reales_y_no_las_de_la_maqueta(): void
    {
        config(['notifications.monthly_quota' => 400]);

        $betsy = $this->invitado('Betsy');
        $this->invitado('Carlos');
        $this->envio($betsy);
        $this->respuesta($betsy, 'confirmed');

        $html = $this->actingAs($this->organizer)
            ->get(route('panel'))
            ->assertOk()
            ->assertSee('45 días')
            ->assertSee('1 / 1', false)   // enviadas / pendientes
            ->assertSee('1 / 2', false)   // confirmaciones / invitados
            ->assertSee('399')            // le quedan 399 mensajes
            ->getContent();

        /*
        | Y el par que traía escrito la maqueta ya no aparece. Se comprueba con
        | "80 / 20" y no con los números sueltos: un "106" cualquiera se cuela
        | en una clase o un color del HTML y la prueba fallaría sin motivo.
        */
        $this->assertStringNotContainsString('80 / 20', $html);
    }
}
