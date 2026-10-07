<?php

namespace Tests\Feature\Organizer;

use App\Jobs\SendGuestNotification;
use App\Mail\GuestNotificationMail;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestNotification;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Rsvp;
use App\Models\Template;
use App\Models\User;
use App\Notifications\ChannelManager;
use App\Notifications\DeliveryIssue;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;
use Tests\Support\FailingChannel;

/**
 * Notificaciones a los invitados: pantalla, envío por medio, seguimiento por
 * invitado y cupo mensual.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $organizer;
    protected Event $event;

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

        [$this->organizer, $this->event] = $this->makeOrganizerWithEvent();

        // WhatsApp necesita credenciales para ofrecerse; se simulan en las pruebas.
        config([
            'services.twilio.sid' => 'AC-test',
            'services.twilio.token' => 'token-test',
            'services.twilio.whatsapp_from' => 'whatsapp:+5210000000000',
            'services.twilio.whatsapp_template_id' => 'HX-test',
        ]);
    }

    /* ---------------------------------------------------------------------
     | Pantalla
     * -------------------------------------------------------------------*/

    public function test_la_pantalla_lista_a_los_invitados_y_el_cupo(): void
    {
        $this->addGuest('Betsy Torres');
        config(['notifications.monthly_quota' => 320]);

        $this->actingAs($this->organizer)
            ->get(route('organizer.notifications.index'))
            ->assertOk()
            ->assertSee('Betsy Torres')
            ->assertSee('Te quedan 320 mensajes')
            ->assertSee('Correo')
            ->assertSee('WhatsApp');
    }

    public function test_marca_a_quien_no_se_puede_alcanzar(): void
    {
        $this->addGuest('Astrid Morales', ['email' => null]);

        $this->actingAs($this->organizer)
            ->get(route('organizer.notifications.index'))
            ->assertSee('(Sin correo)');
    }

    public function test_quitar_un_medio_de_la_configuracion_lo_quita_de_la_pantalla(): void
    {
        // Extensible: la lista de medios sale de config/notifications.php.
        config(['notifications.channels' => ['email' => \App\Notifications\Channels\EmailChannel::class]]);
        $this->addGuest('Betsy Torres');

        $this->actingAs($this->organizer)
            ->get(route('organizer.notifications.index'))
            ->assertSee('Correo')
            ->assertDontSee('WhatsApp');
    }

    public function test_el_menu_del_organizador_incluye_notificaciones(): void
    {
        $this->actingAs($this->organizer)
            ->get(route('panel'))
            ->assertSee(route('organizer.notifications.index'), false);
    }

    public function test_filtra_pendientes_de_confirmar_y_no_invitados(): void
    {
        $confirmado = $this->addGuest('Confirmado');
        $pendiente = $this->addGuest('Pendiente');

        Rsvp::create([
            'event_guest_id' => $confirmado->invitationFor($this->event)->id,
            'attendance' => 'confirmed',
            'confirmed_passes' => 1,
        ]);
        $this->markNotified($confirmado);

        $this->actingAs($this->organizer)
            ->get(route('organizer.notifications.index', ['filtro' => 'pending']))
            ->assertSee('Pendiente')
            ->assertDontSee('Confirmado');

        $this->actingAs($this->organizer)
            ->get(route('organizer.notifications.index', ['filtro' => 'not_notified']))
            ->assertSee('Pendiente')
            ->assertDontSee('Confirmado');
    }

    /**
     * Cada boda empieza de cero.
     *
     * La lista de invitados es del organizador, no de la boda, así que un mismo
     * contacto arrastra los envíos de celebraciones anteriores. Para la boda
     * nueva no cuentan: nadie ha recibido nada todavía.
     */
    public function test_los_envios_de_una_boda_anterior_no_cuentan_en_la_nueva(): void
    {
        $invitado = $this->addGuest('Betsy Torres');
        $this->markNotified($invitado);

        // Otra boda del mismo organizador, con el mismo contacto invitado.
        $nueva = Event::factory()->create([
            'user_id' => $this->organizer->id,
            'template_id' => $this->event->template_id,
            'custom_url' => 'segunda-boda-' . uniqid(),
        ]);
        $invitado->events()->attach($nueva->id, [
            'uuid' => (string) Str::uuid(),
            'max_passes' => 1,
        ]);

        $servicio = app(NotificationService::class);

        $enLaVieja = $servicio->invitationsFor($this->event)->sole();
        $enLaNueva = $servicio->invitationsFor($nueva)->sole();

        $this->assertSame(1, $enLaVieja->guest->sent_notifications_count);
        $this->assertSame(0, $enLaNueva->guest->sent_notifications_count, 'La boda nueva empieza sin envíos.');

        // Y sigue estando en "sin notificar" de la boda nueva, que es el filtro
        // que se usa para no olvidar a nadie.
        $this->assertCount(0, $servicio->invitationsFor($this->event, NotificationService::FILTER_NOT_NOTIFIED));
        $this->assertCount(1, $servicio->invitationsFor($nueva, NotificationService::FILTER_NOT_NOTIFIED));

        // El cupo mensual ya iba por evento; queda fijado.
        $this->assertSame(1, $servicio->usedQuota($this->event));
        $this->assertSame(0, $servicio->usedQuota($nueva));
    }

    /* ---------------------------------------------------------------------
     | Envío
     * -------------------------------------------------------------------*/

    public function test_encola_un_envio_por_invitado(): void
    {
        Queue::fake();
        $betsy = $this->addGuest('Betsy Torres');
        $ema = $this->addGuest('Ema Zamorano');

        $this->actingAs($this->organizer)
            ->post(route('organizer.notifications.send'), [
                'guest_ids' => [$betsy->id, $ema->id],
                'channel' => 'email',
                'message' => 'Hola {{nombre}}, confirma tu asistencia.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $notification = Notification::sole();
        $this->assertSame('email', $notification->channel);
        $this->assertSame($this->event->id, $notification->event_id);
        $this->assertSame(2, $notification->deliveries()->where('status', GuestNotification::STATUS_QUEUED)->count());

        Queue::assertPushed(SendGuestNotification::class, 2);
    }

    public function test_omite_a_quien_no_tiene_el_dato_del_medio(): void
    {
        Queue::fake();
        $conCorreo = $this->addGuest('Con correo');
        $sinCorreo = $this->addGuest('Sin correo', ['email' => null]);

        $this->actingAs($this->organizer)->post(route('organizer.notifications.send'), [
            'guest_ids' => [$conCorreo->id, $sinCorreo->id],
            'channel' => 'email',
            'message' => 'Hola {{nombre}}',
        ]);

        $omitido = GuestNotification::where('guest_id', $sinCorreo->id)->sole();
        $this->assertSame(GuestNotification::STATUS_SKIPPED, $omitido->status);
        $this->assertSame('Sin correo', $omitido->error_message);

        Queue::assertPushed(SendGuestNotification::class, 1);
    }

    public function test_no_puede_enviar_a_invitados_de_otro_organizador(): void
    {
        Queue::fake();
        [$otro] = $this->makeOrganizerWithEvent();
        $ajeno = Guest::factory()->create(['user_id' => $otro->id]);

        $this->actingAs($this->organizer)->post(route('organizer.notifications.send'), [
            'guest_ids' => [$ajeno->id],
            'channel' => 'email',
            'message' => 'Hola {{nombre}}',
        ])->assertSessionHas('error');

        $this->assertSame(0, Notification::count());
        Queue::assertNothingPushed();
    }

    public function test_rechaza_un_medio_no_disponible(): void
    {
        $betsy = $this->addGuest('Betsy Torres');

        $this->actingAs($this->organizer)->post(route('organizer.notifications.send'), [
            'guest_ids' => [$betsy->id],
            'channel' => 'paloma-mensajera',
            'message' => 'Hola {{nombre}}',
        ])->assertSessionHasErrors('channel');
    }

    public function test_respeta_el_cupo_mensual(): void
    {
        Queue::fake();
        config(['notifications.monthly_quota' => 1]);
        $betsy = $this->addGuest('Betsy Torres');
        $ema = $this->addGuest('Ema Zamorano');

        $this->actingAs($this->organizer)->post(route('organizer.notifications.send'), [
            'guest_ids' => [$betsy->id, $ema->id],
            'channel' => 'email',
            'message' => 'Hola {{nombre}}',
        ])->assertSessionHas('error');

        $this->assertSame(0, Notification::count());
        Queue::assertNothingPushed();
    }

    /* ---------------------------------------------------------------------
     | Entrega
     * -------------------------------------------------------------------*/

    public function test_el_trabajo_envia_el_correo_personalizado_y_marca_enviado(): void
    {
        Mail::fake();
        $betsy = $this->addGuest('Betsy Torres', ['email' => 'betsy@correo.com']);
        $delivery = $this->queueDelivery($betsy, 'email', 'Hola {{nombre}}, aquí está tu invitación: {{url}}');

        (new SendGuestNotification($delivery->id))->handle(app(ChannelManager::class));

        Mail::assertSent(GuestNotificationMail::class, function (GuestNotificationMail $mail) use ($betsy) {
            return $mail->hasTo('betsy@correo.com')
                && str_contains($mail->notification->body, 'Betsy Torres')
                && str_contains($mail->notification->body, $betsy->invitationFor($this->event)->invitationUrl());
        });

        $this->assertSame(GuestNotification::STATUS_SENT, $delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->sent_at);
    }

    /**
     * La invitación puede vencer entre que se manda el lote y la cola llega a
     * este envío. No se manda un mensaje con un enlace que ya no abre.
     */
    public function test_si_la_invitacion_dejo_de_estar_activa_el_envio_se_omite(): void
    {
        Mail::fake();
        $betsy = $this->addGuest('Betsy Torres', ['email' => 'betsy@correo.com']);
        $delivery = $this->queueDelivery($betsy, 'email', 'Hola {{nombre}}');

        // Apagada después de encolar, como la dejaría el superadmin o el comando.
        $this->event->forceFill(['is_active' => false])->save();

        (new SendGuestNotification($delivery->id))->handle(app(ChannelManager::class));

        Mail::assertNothingSent();

        $delivery->refresh();
        $this->assertSame(GuestNotification::STATUS_SKIPPED, $delivery->status);
        $this->assertSame(DeliveryIssue::EVENT_UNAVAILABLE, $delivery->failure_code);
        $this->assertStringContainsString(
            'dejó de estar activa',
            $delivery->issueMessage('Correo'),
        );

        // Omitido no consume cupo: el mensaje nunca salió.
        $this->assertSame(0, app(NotificationService::class)->usedQuota($this->event));
    }

    public function test_un_envio_de_una_invitacion_vencida_tambien_se_omite(): void
    {
        Mail::fake();
        $betsy = $this->addGuest('Betsy Torres', ['email' => 'betsy@correo.com']);
        $delivery = $this->queueDelivery($betsy, 'email', 'Hola {{nombre}}');

        // Encendida, pero con la vigencia ya pasada: el comando aún no ha corrido.
        $this->event->forceFill(['expires_at' => now()->subDay(), 'is_active' => true])->save();

        (new SendGuestNotification($delivery->id))->handle(app(ChannelManager::class));

        Mail::assertNothingSent();
        $this->assertSame(GuestNotification::STATUS_SKIPPED, $delivery->fresh()->status);
    }

    public function test_un_intento_fallido_sigue_en_espera_con_su_error(): void
    {
        config(['notifications.channels.failing' => FailingChannel::class]);
        $betsy = $this->addGuest('Betsy Torres');
        $delivery = $this->queueDelivery($betsy, 'failing', 'Hola {{nombre}}');

        try {
            (new SendGuestNotification($delivery->id))->handle(app(ChannelManager::class));
            $this->fail('El trabajo debe propagar el error para que la cola reintente.');
        } catch (RuntimeException $e) {
            // esperado
        }

        // Aún quedan reintentos: no debe mostrarse como error definitivo.
        $delivery->refresh();
        $this->assertSame(GuestNotification::STATUS_QUEUED, $delivery->status);
        $this->assertSame('Reintentando', $delivery->displayStatus());
        // El texto del proveedor se guarda para soporte, pero no se muestra.
        $this->assertStringContainsString('proveedor caído', $delivery->error_message);
        $this->assertSame(DeliveryIssue::REJECTED, $delivery->failure_code);
        $this->assertStringNotContainsString('proveedor caído', $delivery->issueMessage('Correo'));
    }

    public function test_al_agotar_los_reintentos_queda_como_error(): void
    {
        $betsy = $this->addGuest('Betsy Torres');
        $delivery = $this->queueDelivery($betsy, 'email', 'Hola {{nombre}}');

        (new SendGuestNotification($delivery->id))->failed(new RuntimeException('550 invalid mailbox'));

        $delivery->refresh();
        $this->assertSame(GuestNotification::STATUS_FAILED, $delivery->status);
        $this->assertSame('Error', $delivery->displayStatus());
        $this->assertSame('550 invalid mailbox', $delivery->error_message);
        $this->assertSame(DeliveryIssue::INVALID_CONTACT, $delivery->failure_code);
        $this->assertStringContainsString('ficha del invitado', $delivery->issueMessage('Correo'));
    }

    /* ---------------------------------------------------------------------
     | Pestaña "Estado de envíos"
     * -------------------------------------------------------------------*/

    public function test_la_pestana_de_envios_muestra_en_espera_errores_y_omitidos(): void
    {
        $this->deliveryFor($this->addGuest('Invitado En Espera'), GuestNotification::STATUS_QUEUED);
        $this->deliveryFor($this->addGuest('Invitado Reintentando'), GuestNotification::STATUS_QUEUED, 'cURL error 28: timed out', DeliveryIssue::CONNECTION);
        $this->deliveryFor($this->addGuest('Invitado Con Error'), GuestNotification::STATUS_FAILED, '550 invalid mailbox', DeliveryIssue::INVALID_CONTACT);
        $this->deliveryFor($this->addGuest('Invitado Omitido'), GuestNotification::STATUS_SKIPPED, 'Sin correo', DeliveryIssue::MISSING_CONTACT);
        $this->deliveryFor($this->addGuest('Invitado Entregado'), GuestNotification::STATUS_SENT);

        $response = $this->actingAs($this->organizer)
            ->get(route('organizer.notifications.index', ['filtro' => 'envios']))
            ->assertOk()
            ->assertSee('Invitado En Espera')
            ->assertSee('En espera')
            ->assertSee('Se enviará en unos momentos.')
            ->assertSee('Invitado Reintentando')
            ->assertSee('Reintentando')
            ->assertSee('No pudimos conectarnos con el servicio de Correo.')
            ->assertSee('Invitado Con Error')
            ->assertSee('El dato de contacto de Correo parece estar mal escrito.')
            ->assertSee('Invitado Omitido')
            ->assertSee('Omitido')
            ->assertSee('El invitado no tiene un dato de contacto para Correo.')
            ->assertDontSee('Invitado Entregado');

        // Nada del proveedor llega a la pantalla.
        $response->assertDontSee('cURL')
            ->assertDontSee('timed out')
            ->assertDontSee('550')
            ->assertDontSee('mailbox');
    }

    public function test_la_pestana_indica_cuantos_envios_requieren_atencion(): void
    {
        $this->deliveryFor($this->addGuest('Uno'), GuestNotification::STATUS_QUEUED);
        $this->deliveryFor($this->addGuest('Dos'), GuestNotification::STATUS_FAILED, 'Error');
        $this->deliveryFor($this->addGuest('Tres'), GuestNotification::STATUS_SKIPPED, 'Sin correo'); // no cuenta

        $html = $this->actingAs($this->organizer)
            ->get(route('organizer.notifications.index'))
            ->getContent();

        $this->assertMatchesRegularExpression('/Estado de envíos\s*<span[^>]*>2<\/span>/u', $html);
    }

    public function test_la_pestana_no_muestra_envios_de_otro_organizador(): void
    {
        [$otro, $otroEvento] = $this->makeOrganizerWithEvent();
        $ajeno = Guest::factory()->create(['user_id' => $otro->id, 'name' => 'Invitado Ajeno']);
        $notification = Notification::create([
            'event_id' => $otroEvento->id,
            'title' => 'Invitación',
            'message' => 'Hola',
            'channel' => 'email',
        ]);
        $notification->deliveries()->create(['guest_id' => $ajeno->id, 'status' => GuestNotification::STATUS_FAILED]);

        $this->actingAs($this->organizer)
            ->get(route('organizer.notifications.index', ['filtro' => 'envios']))
            ->assertOk()
            ->assertDontSee('Invitado Ajeno');
    }

    public function test_el_cupo_baja_con_lo_enviado_y_no_con_lo_omitido(): void
    {
        Queue::fake();
        config(['notifications.monthly_quota' => 10]);
        $conCorreo = $this->addGuest('Con correo');
        $sinCorreo = $this->addGuest('Sin correo', ['email' => null]);

        $this->actingAs($this->organizer)->post(route('organizer.notifications.send'), [
            'guest_ids' => [$conCorreo->id, $sinCorreo->id],
            'channel' => 'email',
            'message' => 'Hola {{nombre}}',
        ]);

        $this->actingAs($this->organizer)
            ->get(route('organizer.notifications.index'))
            ->assertSee('Te quedan 9 mensajes');
    }

    /* ------------------------------------------------------------------ */

    /** @return array{0: User, 1: Event} */
    private function makeOrganizerWithEvent(): array
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => 'organizer']));

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
            'custom_url' => 'ana-y-luis-' . uniqid(),
        ]);

        return [$user, $event];
    }

    private function addGuest(string $name, array $attributes = []): Guest
    {
        $guest = Guest::factory()->create(array_merge([
            'user_id' => $this->organizer->id,
            'name' => $name,
        ], $attributes));

        $guest->events()->attach($this->event->id, [
            'uuid' => (string) Str::uuid(),
            'max_passes' => 1,
        ]);

        return $guest->fresh();
    }

    private function markNotified(Guest $guest): void
    {
        $notification = Notification::create([
            'event_id' => $this->event->id,
            'title' => 'Invitación',
            'message' => 'Hola {{nombre}}',
            'channel' => 'email',
        ]);

        $notification->deliveries()->create([
            'guest_id' => $guest->id,
            'status' => GuestNotification::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    private function deliveryFor(Guest $guest, string $status, ?string $error = null, ?string $code = null): GuestNotification
    {
        $notification = Notification::create([
            'event_id' => $this->event->id,
            'title' => 'Invitación',
            'message' => 'Hola {{nombre}}',
            'channel' => 'email',
        ]);

        return $notification->deliveries()->create([
            'guest_id' => $guest->id,
            'status' => $status,
            'error_message' => $error,
            'failure_code' => $code,
            'sent_at' => $status === GuestNotification::STATUS_SENT ? now() : null,
        ]);
    }

    private function queueDelivery(Guest $guest, string $channel, string $message): GuestNotification
    {
        $notification = Notification::create([
            'event_id' => $this->event->id,
            'title' => 'Invitación',
            'message' => $message,
            'channel' => $channel,
        ]);

        return $notification->deliveries()->create([
            'guest_id' => $guest->id,
            'status' => GuestNotification::STATUS_QUEUED,
        ]);
    }
}
