<?php

namespace Tests\Feature\Auth;

use App\Models\Event;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Cada usuario debe terminar en el dashboard de su rol, tanto al iniciar sesión
 * como si ya tiene sesión y vuelve a entrar a /login.
 */
class LoginRedirectTest extends TestCase
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

    public function test_superadmin_va_a_su_dashboard_al_iniciar_sesion(): void
    {
        $user = $this->userWithRole('superadmin');

        $this->post(route('login.perform'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('superadmin.dashboard'));
    }

    public function test_organizer_va_al_panel_al_iniciar_sesion(): void
    {
        $user = $this->organizerWithEvent();

        $this->post(route('login.perform'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('panel'));
    }

    public function test_superadmin_con_sesion_que_entra_a_login_va_a_su_dashboard(): void
    {
        // Antes RedirectIfAuthenticated lo mandaba a HOME ('/').
        $this->actingAs($this->userWithRole('superadmin'))
            ->get(route('login'))
            ->assertRedirect(route('superadmin.dashboard'));
    }

    public function test_organizer_con_sesion_que_entra_a_login_va_al_panel(): void
    {
        $this->actingAs($this->organizerWithEvent())
            ->get(route('login'))
            ->assertRedirect(route('panel'));
    }

    public function test_organizer_con_evento_ve_su_panel(): void
    {
        $this->actingAs($this->organizerWithEvent())->get(route('panel'))->assertOk();
    }

    public function test_organizer_sin_evento_ve_el_panel_sin_error(): void
    {
        // Antes: 500 por llamar hasFeatures() sobre un evento null.
        $this->actingAs($this->userWithRole('organizer'))->get(route('panel'))->assertOk();
    }

    /* ------------------------------------------------------------------ */

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => $role]));

        return $user;
    }

    private function organizerWithEvent(): User
    {
        $user = $this->userWithRole('organizer');

        Event::factory()->create([
            'user_id' => $user->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
        ]);

        return $user;
    }
}
