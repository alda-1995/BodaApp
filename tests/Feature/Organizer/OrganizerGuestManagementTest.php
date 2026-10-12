<?php

namespace Tests\Feature\Organizer;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

/**
 * Invitados del organizador: listado, alta, detalle, edición, baja e importación.
 */
class OrganizerGuestManagementTest extends TestCase
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
    }

    /* ---------------------------------------------------------------------
     | Listado y menú
     * -------------------------------------------------------------------*/

    public function test_lista_solo_los_invitados_del_organizador(): void
    {
        $this->addGuest($this->organizer, ['name' => 'Betsy Torres']);
        [$otro] = $this->makeOrganizerWithEvent();
        $this->addGuest($otro, ['name' => 'Invitado Ajeno']);

        $this->actingAs($this->organizer)
            ->get(route('organizer.guests.index'))
            ->assertOk()
            ->assertSee('Betsy Torres')
            ->assertDontSee('Invitado Ajeno');
    }

    /**
     * Cada boda lleva su propia lista.
     *
     * Un invitado de una boda anterior se veía aquí a medias —sin tope de
     * acompañantes y sin enlace, porque esos datos cuelgan de la unión con el
     * evento— y en cambio no aparecía en notificaciones, que sí filtra por
     * boda. Se veía como si algo se hubiera roto.
     */
    public function test_no_lista_invitados_de_una_boda_anterior(): void
    {
        [$deAntes, ] = $this->guestOfPreviousWeddingAndCurrentOne();

        $this->actingAs($this->organizer)
            ->get(route('organizer.guests.index'))
            ->assertOk()
            ->assertSee('Betsy Torres')
            ->assertDontSee($deAntes->name);
    }

    /** Y tampoco se puede abrir el suyo desde esta boda. */
    public function test_no_deja_abrir_un_invitado_de_otra_boda(): void
    {
        [$deAntes, ] = $this->guestOfPreviousWeddingAndCurrentOne();

        $this->actingAs($this->organizer)
            ->get(route('organizer.guests.show', $deAntes->id))
            ->assertNotFound();
    }

    /**
     * Deja al organizador con dos bodas: la de setUp, que pasa a ser la vieja, y
     * una nueva que es la que manda.
     *
     * El orden importa: la boda "actual" es la última que se creó, así que la
     * anterior tiene que existir antes. Montarlo al revés hace que la prueba
     * compruebe justo lo contrario de lo que dice.
     *
     * @return array{0: Guest, 1: Guest}  el de la boda vieja y el de la nueva
     */
    private function guestOfPreviousWeddingAndCurrentOne(): array
    {
        $deAntes = Guest::factory()->create([
            'user_id' => $this->organizer->id,
            'name' => 'Invitado De La Boda Pasada',
        ]);
        $deAntes->events()->attach($this->event->id, [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'max_passes' => 1,
        ]);

        $actual = Event::factory()->create([
            'user_id' => $this->organizer->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
        ]);

        $deAhora = Guest::factory()->create([
            'user_id' => $this->organizer->id,
            'name' => 'Betsy Torres',
        ]);
        $deAhora->events()->attach($actual->id, [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'max_passes' => 1,
        ]);

        return [$deAntes, $deAhora];
    }

    public function test_busca_por_nombre_correo_o_telefono(): void
    {
        $this->addGuest($this->organizer, ['name' => 'Betsy Torres', 'phone' => '+528119125833']);
        $this->addGuest($this->organizer, ['name' => 'Ema Zamorano', 'phone' => '+525518152937']);

        $this->actingAs($this->organizer)
            ->get(route('organizer.guests.index', ['search' => '8119']))
            ->assertSee('Betsy Torres')
            ->assertDontSee('Ema Zamorano');
    }

    public function test_el_menu_del_organizador_incluye_invitados(): void
    {
        $this->actingAs($this->organizer)
            ->get(route('panel'))
            ->assertSee(route('organizer.guests.index'), false);
    }

    /* ---------------------------------------------------------------------
     | Alta
     * -------------------------------------------------------------------*/

    public function test_crea_invitado_ligado_al_usuario_y_a_su_evento(): void
    {
        $this->actingAs($this->organizer)
            ->post(route('organizer.guests.store'), $this->payload(['max_passes' => 2]))
            ->assertRedirect(route('organizer.guests.index'))
            ->assertSessionHasNoErrors();

        $guest = Guest::sole();
        $this->assertSame($this->organizer->id, $guest->user_id);

        $invitation = $guest->invitationFor($this->event);
        $this->assertNotNull($invitation, 'El invitado debe quedar ligado al evento del organizador.');
        $this->assertSame(2, (int) $invitation->max_passes);
        $this->assertNotEmpty($invitation->uuid);
    }

    public function test_valida_los_campos_obligatorios(): void
    {
        $this->actingAs($this->organizer)
            ->post(route('organizer.guests.store'), ['name' => '', 'phone' => '', 'max_passes' => ''])
            ->assertSessionHasErrors(['name', 'phone', 'max_passes']);

        $this->assertSame(0, Guest::count());
    }

    public function test_el_telefono_debe_llevar_lada_internacional(): void
    {
        $this->actingAs($this->organizer)
            ->post(route('organizer.guests.store'), $this->payload(['phone' => '8119125833']))
            ->assertSessionHasErrors('phone');
    }

    public function test_el_correo_es_unico_dentro_de_la_boda_y_no_global(): void
    {
        [$otro] = $this->makeOrganizerWithEvent();
        $this->addGuest($otro, ['email' => 'betsy@correo.com']);

        // Otro organizador ya lo tiene: aquí sí se permite.
        $this->actingAs($this->organizer)
            ->post(route('organizer.guests.store'), $this->payload(['email' => 'betsy@correo.com']))
            ->assertSessionHasNoErrors();

        // Repetido dentro de la misma boda: no.
        $this->actingAs($this->organizer)
            ->post(route('organizer.guests.store'), $this->payload(['name' => 'Otra', 'email' => 'betsy@correo.com']))
            ->assertSessionHasErrors('email');
    }

    /**
     * A quien invitaste a tu boda anterior lo puedes volver a invitar a la
     * nueva: es lo normal, y antes la regla de correo único —que miraba todos
     * los invitados del organizador— lo impedía.
     */
    public function test_se_puede_reinvitar_a_alguien_de_una_boda_anterior(): void
    {
        $deAntes = Guest::factory()->create([
            'user_id' => $this->organizer->id,
            'email' => 'aldair.canalla.agency@gmail.com',
        ]);
        $deAntes->events()->attach($this->event->id, [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'max_passes' => 1,
        ]);

        // La nueva boda, que pasa a ser la que manda.
        Event::factory()->create([
            'user_id' => $this->organizer->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
        ]);

        $this->actingAs($this->organizer)
            ->post(route('organizer.guests.store'), $this->payload([
                'name' => 'Aldair',
                'email' => 'aldair.canalla.agency@gmail.com',
            ]))
            ->assertSessionHasNoErrors();
    }

    /* ---------------------------------------------------------------------
     | Detalle, edición y baja
     * -------------------------------------------------------------------*/

    public function test_muestra_el_detalle_con_su_enlace(): void
    {
        $guest = $this->addGuest($this->organizer, ['name' => 'Betsy Torres']);
        $uuid = $guest->invitationFor($this->event)->uuid;

        $this->actingAs($this->organizer)
            ->get(route('organizer.guests.show', $guest->id))
            ->assertOk()
            ->assertSee('Betsy Torres')
            ->assertSee($uuid);
    }

    public function test_actualiza_datos_y_tope_sin_cambiar_el_enlace(): void
    {
        $guest = $this->addGuest($this->organizer, ['name' => 'Betsy']);
        $uuidOriginal = $guest->invitationFor($this->event)->uuid;

        $this->actingAs($this->organizer)
            ->put(route('organizer.guests.update', $guest->id), $this->payload(['name' => 'Betsy Torres', 'max_passes' => 3]))
            ->assertRedirect(route('organizer.guests.show', $guest->id))
            ->assertSessionHasNoErrors();

        $guest->refresh();
        $invitation = $guest->invitationFor($this->event);
        $this->assertSame('Betsy Torres', $guest->name);
        $this->assertSame(3, (int) $invitation->max_passes);
        $this->assertSame($uuidOriginal, $invitation->uuid, 'Editar no debe cambiar el enlace ya compartido.');
    }

    public function test_puede_conservar_su_propio_correo_al_editar(): void
    {
        $guest = $this->addGuest($this->organizer, ['email' => 'betsy@correo.com']);

        $this->actingAs($this->organizer)
            ->put(route('organizer.guests.update', $guest->id), $this->payload(['email' => 'betsy@correo.com']))
            ->assertSessionHasNoErrors();
    }

    public function test_elimina_al_invitado(): void
    {
        $guest = $this->addGuest($this->organizer);

        $this->actingAs($this->organizer)
            ->delete(route('organizer.guests.destroy', $guest->id))
            ->assertRedirect(route('organizer.guests.index'));

        $this->assertModelMissing($guest);
        $this->assertDatabaseMissing('event_guest', ['guest_id' => $guest->id]);
    }

    public function test_no_puede_ver_editar_ni_borrar_invitados_de_otro(): void
    {
        [$otro] = $this->makeOrganizerWithEvent();
        $ajeno = $this->addGuest($otro);

        $this->actingAs($this->organizer)->get(route('organizer.guests.show', $ajeno->id))->assertNotFound();
        $this->actingAs($this->organizer)->get(route('organizer.guests.edit', $ajeno->id))->assertNotFound();
        $this->actingAs($this->organizer)->put(route('organizer.guests.update', $ajeno->id), $this->payload())->assertNotFound();
        $this->actingAs($this->organizer)->delete(route('organizer.guests.destroy', $ajeno->id))->assertNotFound();

        $this->assertModelExists($ajeno);
    }

    /* ---------------------------------------------------------------------
     | Importación
     * -------------------------------------------------------------------*/

    public function test_importa_csv_de_excel_con_punto_y_coma_y_acentos(): void
    {
        $csv = "\xEF\xBB\xBF" . implode("\n", [
            'Nombre;Teléfono;Correo;Acompañantes',
            'Betsy Torres;81 1912 5833;betsy@correo.com;2', // 10 dígitos: se antepone +52
            'Ema Zamorano;+525518152937;;',                  // acompañantes vacío: 1
            ';;;',                                          // fila vacía: se ignora
            'Sin Teléfono;;sin@correo.com;1',               // error: teléfono obligatorio
        ]);

        $this->actingAs($this->organizer)
            ->post(route('organizer.guests.process_import'), [
                'file' => UploadedFile::fake()->createWithContent('invitados.csv', $csv),
            ])
            ->assertRedirect(route('organizer.guests.index'))
            ->assertSessionHas('success', 'Se importaron 2 invitados.');

        $this->assertSame(['Betsy Torres', 'Ema Zamorano'], Guest::orderBy('name')->pluck('name')->all());

        $betsy = Guest::where('name', 'Betsy Torres')->sole();
        $this->assertSame('+528119125833', $betsy->phone);
        $this->assertSame(2, (int) $betsy->invitationFor($this->event)->max_passes);
        $this->assertSame(1, (int) Guest::where('name', 'Ema Zamorano')->sole()->invitationFor($this->event)->max_passes);

        $errors = session('import_errors');
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Fila 5', $errors[0]);
    }

    public function test_descarga_el_formato_de_importacion(): void
    {
        $response = $this->actingAs($this->organizer)->get(route('organizer.guests.import_template'));

        $response->assertOk();

        // Sólo la fila de encabezados, separada por comas y sin datos de ejemplo.
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $response->streamedContent());
        $this->assertSame("Nombre,Teléfono,Correo,Acompañantes\n", $content);
        $this->assertCount(4, str_getcsv(trim($content)), 'Cada campo debe quedar en su propia columna.');
    }

    public function test_el_formato_descargado_se_puede_llenar_e_importar(): void
    {
        // Ida y vuelta: el usuario descarga el formato, agrega una fila y lo sube.
        $template = $this->actingAs($this->organizer)
            ->get(route('organizer.guests.import_template'))
            ->streamedContent();

        $filled = $template . "Betsy Torres,+528119125833,betsy@correo.com,2\n";

        $this->actingAs($this->organizer)
            ->post(route('organizer.guests.process_import'), [
                'file' => UploadedFile::fake()->createWithContent('formato_invitados.csv', $filled),
            ])
            ->assertSessionHas('success', 'Se importaron 1 invitados.');

        $guest = Guest::sole();
        $this->assertSame('Betsy Torres', $guest->name);
        $this->assertSame('+528119125833', $guest->phone);
        $this->assertSame('betsy@correo.com', $guest->email);
        $this->assertSame(2, (int) $guest->invitationFor($this->event)->max_passes);
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
        ]);

        return [$user, $event];
    }

    private function addGuest(User $owner, array $attributes = []): Guest
    {
        $guest = Guest::factory()->create(array_merge(['user_id' => $owner->id], $attributes));
        $guest->events()->attach($owner->events()->first()->id, [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'max_passes' => 1,
        ]);

        return $guest->fresh();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Betsy Torres',
            'phone' => '+528119125833',
            'email' => '',
            'max_passes' => 1,
        ], $overrides);
    }
}
