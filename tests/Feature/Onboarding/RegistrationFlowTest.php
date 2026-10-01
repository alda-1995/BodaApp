<?php

namespace Tests\Feature\Onboarding;

use App\Models\Event;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Tests\TestCase;

/**
 * Flujo de registro (onboarding) completo:
 *   paso 1: crear contraseña desde el enlace del correo,
 *   paso 2: nombre, nombres de la pareja y URL de la invitación.
 *
 * Los tests marcados "BUG:" describen el comportamiento correcto y hoy fallan.
 */
class RegistrationFlowTest extends TestCase
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

    /* =====================================================================
     | Paso 1 · Pantalla para crear la contraseña
     * ===================================================================*/

    public function test_el_enlace_sin_correo_o_token_manda_al_login(): void
    {
        $this->get(route('onboarding.password.view'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_el_enlace_con_token_invalido_manda_al_login(): void
    {
        $user = $this->newUser();

        $this->get(route('onboarding.password.view', ['email' => $user->email, 'token' => 'token-falso']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_el_enlace_con_un_correo_desconocido_manda_al_login(): void
    {
        $this->get(route('onboarding.password.view', ['email' => 'nadie@gmail.com', 'token' => 'lo-que-sea']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_el_enlace_valido_muestra_el_formulario(): void
    {
        $user = $this->newUser();
        $token = Password::createToken($user);

        $this->get(route('onboarding.password.view', ['email' => $user->email, 'token' => $token]))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee($token);
    }

    /* =====================================================================
     | Paso 1 · Guardar la contraseña
     * ===================================================================*/

    public function test_la_contrasena_debe_confirmarse_y_ser_segura(): void
    {
        $user = $this->newUser();
        $token = Password::createToken($user);

        $this->post(route('onboarding.password.store'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'corta',
            'password_confirmation' => 'otra',
        ])->assertSessionHasErrors('password');
    }

    public function test_no_guarda_la_contrasena_con_un_token_invalido(): void
    {
        $user = $this->newUser();
        $anterior = $user->password;

        $this->post(route('onboarding.password.store'), $this->passwordPayload($user->email, 'token-falso'))
            ->assertRedirect(route('password.request', ['email' => $user->email]))
            ->assertSessionHas('error');

        $this->assertSame($anterior, $user->fresh()->password, 'La contraseña no debe cambiar.');
    }

    public function test_guarda_la_contrasena_y_lleva_al_paso_dos(): void
    {
        $user = $this->newUser();
        $token = Password::createToken($user);

        $response = $this->post(route('onboarding.password.store'), $this->passwordPayload($user->email, $token));

        $response->assertRedirectContains('onboarding/setup-profile');

        $user->refresh();
        $this->assertTrue(Hash::check('Password123', $user->password), 'Debe guardar la contraseña elegida.');
        $this->assertNotNull($user->password_changed_at);

        // El enlace al que redirige debe estar firmado y ser válido.
        $this->get($response->headers->get('Location'))->assertOk();
    }

    public function test_el_token_no_sirve_dos_veces(): void
    {
        $user = $this->newUser();
        $token = Password::createToken($user);

        $this->post(route('onboarding.password.store'), $this->passwordPayload($user->email, $token));

        $this->post(route('onboarding.password.store'), $this->passwordPayload($user->email, $token, 'OtraPassword456'))
            ->assertRedirect(route('password.request', ['email' => $user->email]))
            ->assertSessionHas('error');

        $this->assertTrue(Hash::check('Password123', $user->fresh()->password), 'La contraseña debe seguir siendo la primera.');
    }

    /* =====================================================================
     | Paso 2 · Pantalla de perfil
     * ===================================================================*/

    public function test_el_paso_dos_exige_un_enlace_firmado(): void
    {
        $user = $this->newUser();

        $this->get(route('onboarding.profile.view', ['email' => $user->email]))
            ->assertRedirect(route('onboarding.password.view'))
            ->assertSessionHas('error');
    }

    public function test_el_paso_dos_con_correo_desconocido_manda_al_login(): void
    {
        $this->get($this->profileUrl('nadie@gmail.com'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_el_paso_dos_muestra_el_formulario(): void
    {
        [$user] = $this->userWithEvent();

        $this->get($this->profileUrl($user->email))
            ->assertOk()
            ->assertSee('invitation_url_preview', false);
    }

    /* =====================================================================
     | Paso 2 · Guardar el perfil
     * ===================================================================*/

    public function test_exige_nombre_y_nombres_de_la_pareja(): void
    {
        [$user] = $this->userWithEvent();

        $this->post(route('onboarding.profile.store'), ['email' => $user->email])
            ->assertSessionHasErrors(['name', 'partner_1_name', 'partner_2_name']);
    }

    public function test_el_perfil_con_correo_desconocido_manda_al_login(): void
    {
        $this->post(route('onboarding.profile.store'), [
            'email' => 'nadie@gmail.com',
            'name' => 'Quien sea',
            'partner_1_name' => 'Ana',
            'partner_2_name' => 'Luis',
        ])->assertSessionHasErrors('email'); // el correo debe existir
    }

    public function test_guarda_el_perfil_inicia_sesion_y_lleva_al_panel(): void
    {
        [$user, $event] = $this->userWithEvent();

        $this->post(route('onboarding.profile.store'), $this->profilePayload($user->email))
            ->assertRedirect(route('panel'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Aldair Reyes', $user->name);
        $this->assertSame('ana-y-luis', $event->fresh()->custom_url);
        $this->assertAuthenticatedAs($user);
    }

    public function test_al_terminar_el_registro_puede_entrar_al_panel(): void
    {
        // Cierra el flujo: el panel exige el rol organizer, que asigna el checkout.
        [$user] = $this->userWithEvent();

        $this->followingRedirects()
            ->post(route('onboarding.profile.store'), $this->profilePayload($user->email))
            ->assertOk();
    }

    public function test_puede_iniciar_sesion_con_la_contrasena_que_creo(): void
    {
        $user = $this->newUser();
        $token = Password::createToken($user);

        $this->post(route('onboarding.password.store'), $this->passwordPayload($user->email, $token));
        $this->post(route('logout'));

        $this->post(route('login.perform'), ['email' => $user->email, 'password' => 'Password123'])
            ->assertRedirect(route('panel'));

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_si_el_usuario_no_tiene_evento_no_rompe_ni_inicia_sesion(): void
    {
        $user = $this->newUser(); // sin evento

        $this->post(route('onboarding.profile.store'), $this->profilePayload($user->email))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame('Sin nombre', $user->fresh()->name, 'Nada debe guardarse a medias.');
    }

    public function test_un_usuario_ya_autenticado_no_repite_el_registro(): void
    {
        [$user] = $this->userWithEvent();

        $this->actingAs($user)
            ->get($this->profileUrl($user->email))
            ->assertRedirect(route('panel'));
    }

    /* =====================================================================
     | Seguridad
     * ===================================================================*/

    public function test_nadie_puede_completar_el_registro_de_otra_persona(): void
    {
        // BUG: el POST del paso 2 no exige enlace firmado ni token. Hoy cualquiera
        // que conozca un correo puede enviar el formulario, cambiarle el nombre,
        // fijar la URL de su invitación y quedar autenticado como esa persona.
        [$victima, $event] = $this->userWithEvent();

        $this->post(route('onboarding.profile.store'), $this->profilePayload($victima->email, 'Intruso'));

        // Un desconocido no debe quedar autenticado como el dueño de la cuenta.
        $this->assertGuest();
        $this->assertSame('Sin nombre', $victima->fresh()->name);
        $this->assertNull($event->fresh()->custom_url);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Como los crea el checkout: OrderService::register() les da el rol organizer.
     */
    private function newUser(): User
    {
        $user = User::factory()->create([
            'name' => 'Sin nombre',
            'email' => 'novios' . uniqid() . '@gmail.com',
        ]);

        $user->roles()->attach(Role::firstOrCreate(['name' => 'organizer'], ['display_name' => 'Organizador']));

        return $user;
    }

    /** @return array{0: User, 1: Event} */
    private function userWithEvent(): array
    {
        $user = $this->newUser();

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
            'custom_url' => null,
        ]);

        return [$user, $event];
    }

    private function profileUrl(string $email): string
    {
        return URL::temporarySignedRoute('onboarding.profile.view', now()->addMinutes(30), ['email' => $email]);
    }

    private function passwordPayload(string $email, string $token, string $password = 'Password123'): array
    {
        return [
            'email' => $email,
            'token' => $token,
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }

    private function profilePayload(string $email, string $name = 'Aldair Reyes'): array
    {
        return [
            'email' => $email,
            'name' => $name,
            'partner_1_name' => 'Ana',
            'partner_2_name' => 'Luis',
        ];
    }
}
