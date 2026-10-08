<?php

namespace Tests\Feature\Order;

use App\Models\Event;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\CoadminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * A dónde se manda al comprador una vez pagada la orden.
 *
 * La compra crea la cuenta con una contraseña al azar, así que quien compra por
 * primera vez tiene que elegir la suya. Quien ya se había registrado antes no:
 * ya tiene contraseña y no hay nada que crear ni que confirmar.
 */
class CheckoutOnboardingTest extends TestCase
{
    use RefreshDatabase;

    /** Seguro: RefreshDatabase vacía la BD; aborta si no es una BD *_test. */
    protected function beforeRefreshingDatabase()
    {
        $database = (string) config('database.connections.' . config('database.default') . '.database');

        if (!str_ends_with($database, '_test')) {
            throw new RuntimeException("Abortado: RefreshDatabase iba a vaciar '{$database}'.");
        }
    }

    public function test_quien_compra_por_primera_vez_crea_su_contrasena(): void
    {
        $order = $this->completedOrderFor($this->buyer());

        $datos = $this->getJson(route('checkout.status', $order->stripe_session_id))
            ->assertOk()
            ->json();

        $this->assertTrue($datos['needs_password']);
        $this->assertStringContainsString(route('onboarding.password.view'), $datos['redirect_url']);
        $this->assertStringContainsString('token=', $datos['redirect_url']);
    }

    public function test_quien_ya_tenia_cuenta_no_pasa_por_crear_contrasena(): void
    {
        $order = $this->completedOrderFor($this->buyer(yaRegistrado: true));

        $datos = $this->getJson(route('checkout.status', $order->stripe_session_id))
            ->assertOk()
            ->json();

        $this->assertFalse($datos['needs_password']);
        $this->assertStringContainsString(route('login'), $datos['redirect_url']);
        $this->assertStringNotContainsString('onboarding', $datos['redirect_url']);
    }

    public function test_a_quien_ya_tenia_cuenta_no_se_le_emite_token_de_contrasena(): void
    {
        $comprador = $this->buyer(yaRegistrado: true);
        $order = $this->completedOrderFor($comprador);

        $this->getJson(route('checkout.status', $order->stripe_session_id))->assertOk();

        // Un token de restablecimiento para una cuenta ajena es una llave de más.
        $this->assertSame(
            0,
            DB::table('password_reset_tokens')->where('email', $comprador->email)->count(),
        );
    }

    /**
     * No hay contraseña que crear, pero sí dirección que elegir: el evento que
     * acaba de comprar nace sin una.
     */
    public function test_si_ya_venia_con_la_sesion_abierta_va_a_elegir_su_direccion(): void
    {
        $comprador = $this->buyer(yaRegistrado: true);
        $order = $this->completedOrderFor($comprador);
        // La compra le deja un evento todavía sin dirección.
        Event::factory()->create(['user_id' => $comprador->id, 'custom_url' => null]);

        $datos = $this->actingAs($comprador)
            ->getJson(route('checkout.status', $order->stripe_session_id))
            ->assertOk()
            ->json();

        $this->assertFalse($datos['needs_password']);
        $this->assertStringContainsString(route('onboarding.profile.view'), $datos['redirect_url']);
        // Firmado: el paso no se abre escribiendo la dirección a mano.
        $this->assertStringContainsString('signature=', $datos['redirect_url']);

        $this->actingAs($comprador)->get($datos['redirect_url'])->assertOk();
    }

    /**
     * Pagar con el correo de alguien no demuestra ser esa persona, y ese paso
     * termina iniciando sesión. Sin sesión abierta se le manda a entrar.
     */
    public function test_sin_la_sesion_abierta_no_se_le_abre_el_paso_de_la_direccion(): void
    {
        $order = $this->completedOrderFor($this->buyer(yaRegistrado: true));

        $datos = $this->getJson(route('checkout.status', $order->stripe_session_id))
            ->assertOk()
            ->json();

        $this->assertStringContainsString(route('login'), $datos['redirect_url']);
        $this->assertStringNotContainsString('onboarding', $datos['redirect_url']);
    }

    /* ---------------------------------------------------------------------
     | La marca de "ya eligió su contraseña" se pone donde se elige
     * -------------------------------------------------------------------*/

    public function test_el_coadministrador_que_se_registra_queda_marcado(): void
    {
        Role::firstOrCreate(['name' => 'coadmin']);
        $dueno = $this->buyer();
        $evento = Event::factory()->create(['user_id' => $dueno->id]);
        $invitacion = $evento->coadmins()->create([
            'email' => 'coadmin@example.com',
            'invited_by' => $dueno->id,
        ]);

        $user = app(CoadminService::class)->registerAndAccept($invitacion, 'Coadmin', 'secreta123');

        $this->assertNotNull($user->fresh()->password_changed_at);
    }

    public function test_restablecer_la_contrasena_marca_la_cuenta(): void
    {
        $user = $this->buyer();
        $token = app('auth.password.broker')->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'otraclave123',
            'password_confirmation' => 'otraclave123',
        ]);

        $this->assertNotNull($user->fresh()->password_changed_at);
    }

    /* ------------------------------------------------------------------ */

    /** Compradora recién creada por el pago, o alguien que ya se registró. */
    private function buyer(bool $yaRegistrado = false): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('loquesea'),
        ]);

        $user->roles()->syncWithoutDetaching([Role::firstOrCreate(['name' => 'organizer'])->id]);

        if ($yaRegistrado) {
            $user->forceFill(['password_changed_at' => now()])->save();
        }

        return $user;
    }

    private function completedOrderFor(User $user): Order
    {
        return Order::factory()->completed()->create(['user_id' => $user->id]);
    }
}
