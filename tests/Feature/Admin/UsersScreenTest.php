<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\Order;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Lista de usuarios del superadmin: estado de cada cuenta y sus fechas.
 */
class UsersScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

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

        $this->superadmin = User::factory()->create();
        $this->superadmin->roles()->attach(Role::named('superadmin')->id);
    }

    public function test_sin_plantilla_comprada_lo_indica(): void
    {
        $this->organizer('Sin Compra');

        $this->screen()
            ->assertSee('Sin Compra')
            ->assertSee('Sin plantilla')
            ->assertSee('Pendiente');
    }

    public function test_sin_fecha_de_boda_no_muestra_fechas(): void
    {
        $this->eventFor($this->organizer('Recien Comprada'), null);

        $this->screen()
            ->assertSee('Falta fecha de boda')
            // Ni fecha de boda ni vencimiento hasta que la capture.
            ->assertSeeInOrder(['Pendiente', 'Pendiente', 'Falta fecha de boda']);
    }

    public function test_con_fecha_muestra_boda_y_vencimiento(): void
    {
        $this->eventFor($this->organizer('Con Fecha'), now()->addMonths(6)->setTime(18, 0));

        $wedding = now()->addMonths(6);

        $this->screen()
            ->assertSee($wedding->format('j M Y'))
            ->assertSee($wedding->copy()->addDays(21)->format('j M Y'))
            // "Pendiente" sólo sale en las columnas de fecha, y aquí ya tiene ambas.
            ->assertDontSee('Pendiente');
    }

    public function test_cada_usuario_muestra_su_propio_evento(): void
    {
        // Antes se cargaba un solo evento entre todos y los demás salían "Pendiente".
        $primera = now()->addMonths(3);
        $segunda = now()->addMonths(8);

        $this->eventFor($this->organizer('Pareja Uno'), $primera);
        $this->eventFor($this->organizer('Pareja Dos'), $segunda);

        $this->screen()
            ->assertSee($primera->format('j M Y'))
            ->assertSee($segunda->format('j M Y'))
            ->assertDontSee('Pendiente');
    }

    public function test_estados_por_vencer_vencido_y_suspendido(): void
    {
        $this->eventFor($this->organizer('Por Vencer'), now()->subDays(10));
        $this->eventFor($this->organizer('Vencida'), now()->subMonths(3));
        $this->eventFor($this->organizer('Suspendida'), now()->addMonth())->update(['is_active' => false]);

        $this->screen()
            ->assertSee('Por vencer')
            ->assertSee('Vencido')
            ->assertSee('Suspendido');
    }

    public function test_filtra_por_estado(): void
    {
        $this->organizer('Sin Compra');
        $this->eventFor($this->organizer('Sin Fecha'), null);
        $this->eventFor($this->organizer('Con Fecha'), now()->addMonths(6));

        $this->screen(['status' => 'no_template'])
            ->assertSee('Sin Compra')
            ->assertDontSee('Sin Fecha')
            ->assertDontSee('Con Fecha');

        $this->screen(['status' => Event::STATUS_PENDING_DATE])
            ->assertSee('Sin Fecha')
            ->assertDontSee('Sin Compra')
            ->assertDontSee('Con Fecha');

        $this->screen(['status' => Event::STATUS_ACTIVE])
            ->assertSee('Con Fecha')
            ->assertDontSee('Sin Fecha');
    }

    /* ---------------------------------------------------------------------
     | Helpers
     * -------------------------------------------------------------------*/

    private function screen(array $filters = [])
    {
        return $this->actingAs($this->superadmin)
            ->get(route('admin.users.index', $filters))
            ->assertOk();
    }

    private function organizer(string $name): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->roles()->attach(Role::named('organizer')->id);

        return $user;
    }

    private function eventFor(User $user, $eventDate): Event
    {
        $template = Template::factory()->create(['view_path' => null, 'duration_days' => null]);
        // La orden va a nombre del mismo usuario: si no, su factory crea otra cuenta
        // y aparecería como una fila extra en la lista.
        $order = Order::factory()->completed()->create([
            'user_id' => $user->id,
            'template_id' => $template->id,
        ]);

        return Event::factory()->create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'order_id' => $order->id,
            'event_date' => $eventDate,
            'custom_url' => 'pareja-' . uniqid(),
        ]);
    }
}
