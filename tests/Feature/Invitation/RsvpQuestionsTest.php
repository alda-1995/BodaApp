<?php

namespace Tests\Feature\Invitation;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Role;
use App\Models\Rsvp;
use App\Models\Template;
use App\Models\User;
use App\Templates\Sections\Catalog\RsvpSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Las preguntas que el organizador le hace a sus invitados.
 *
 * Un solo interruptor manda sobre las tres pantallas: el repetidor del wizard,
 * las preguntas del formulario de la invitación y las columnas de la tabla de
 * Confirmaciones. Aquí se fija que las tres digan lo mismo.
 */
class RsvpQuestionsTest extends TestCase
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
        $this->organizer->roles()->attach(
            Role::firstOrCreate(['name' => 'organizer'], ['display_name' => 'Organizador'])->id
        );

        $this->event = Event::factory()->create([
            'user_id' => $this->organizer->id,
            // Con una plantilla de verdad, para que la invitación se pinte.
            'template_id' => Template::factory()->create([
                'view_path' => 'build-templates.template-editorial.index',
            ])->id,
            'custom_url' => 'ana-y-luis',
            'event_date' => now()->addMonths(2),
            'features' => $this->features(true),
        ]);
    }

    /** @param array<string, ?string> $preguntas pregunta => aclaración */
    private function features(bool $preguntar, ?array $preguntas = null): array
    {
        $preguntas ??= [
            '¿Alguna restricción alimentaria?' => 'Sin gluten, alergias, vegetariano...',
            '¿Vienes en auto?' => null,
        ];

        return [
            'general' => ['name_wife' => 'Ana', 'name_husband' => 'Luis'],
            'rsvp' => [
                'rsvp_deadline' => now()->addMonth()->toDateTimeString(),
                'ask_dietary_requirements' => $preguntar,
                'custom_questions' => collect($preguntas)
                    ->map(fn (?string $aclaracion, string $pregunta) => [
                        'question' => $pregunta,
                        'description' => $aclaracion,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    private function datosDeLaSeccion(): array
    {
        $values = $this->event->fresh()->features['rsvp'];

        return (new RsvpSection())->data($values, []);
    }

    /* =====================================================================
     | El interruptor
     * ===================================================================*/

    public function test_encendido_entrega_las_preguntas_configuradas(): void
    {
        $datos = $this->datosDeLaSeccion();

        $this->assertTrue($datos['ask_dietary_requirements']);
        $this->assertSame([
            ['question' => '¿Alguna restricción alimentaria?', 'description' => 'Sin gluten, alergias, vegetariano...'],
            // Sin aclaración queda en null, no en cadena vacía.
            ['question' => '¿Vienes en auto?', 'description' => null],
        ], $datos['custom_questions']);
    }

    public function test_apagado_no_entrega_ninguna_aunque_sigan_guardadas(): void
    {
        /*
        | Las preguntas se quedan en features a propósito: apagar y volver a
        | encender no debe obligar a escribirlas otra vez.
        */
        $this->event->update(['features' => $this->features(false)]);

        $datos = $this->datosDeLaSeccion();

        $this->assertFalse($datos['ask_dietary_requirements']);
        $this->assertSame([], $datos['custom_questions']);

        $guardadas = $this->event->fresh()->features['rsvp']['custom_questions'];
        $this->assertCount(2, $guardadas, 'Lo capturado no se borra, sólo deja de usarse.');
    }

    public function test_el_repetidor_del_wizard_depende_del_interruptor(): void
    {
        $campos = (new RsvpSection())->fields();

        $this->assertSame(
            ['field' => 'ask_dietary_requirements', 'values' => [true]],
            $campos['custom_questions']->getDependsOn(),
        );
    }

    /* =====================================================================
     | El formulario de la invitación
     * ===================================================================*/

    public function test_el_formulario_pinta_una_pregunta_por_cada_una_con_su_aclaracion(): void
    {
        $this->get('/invitacion/ana-y-luis')
            ->assertOk()
            ->assertSee('name="answers[¿Alguna restricción alimentaria?]"', false)
            ->assertSee('name="answers[¿Vienes en auto?]"', false)
            // La aclaración va bajo su pregunta.
            ->assertSee('Sin gluten, alergias, vegetariano...');
    }

    public function test_ya_no_hay_un_campo_fijo_de_restricciones(): void
    {
        // Ahora es una pregunta más, escrita por el organizador.
        $this->get('/invitacion/ana-y-luis')
            ->assertOk()
            ->assertDontSee('name="dietary_restrictions"', false);
    }

    public function test_apagado_el_formulario_no_pregunta_nada_de_eso(): void
    {
        $this->event->update(['features' => $this->features(false)]);

        $this->get('/invitacion/ana-y-luis')
            ->assertOk()
            ->assertDontSee('name="answers[¿Vienes en auto?]"', false)
            ->assertDontSee('Sin gluten, alergias, vegetariano...');
    }

    /* =====================================================================
     | La tabla de Confirmaciones
     * ===================================================================*/

    public function test_hay_una_columna_por_pregunta_aunque_nadie_haya_contestado(): void
    {
        $this->responde('Betsy', ['¿Vienes en auto?' => 'En taxi']);

        $this->actingAs($this->organizer)
            ->get(route('organizer.rsvps.index'))
            ->assertOk()
            // La primera no la contestó nadie y aun así tiene su columna.
            ->assertSee('¿Alguna restricción alimentaria?')
            ->assertSee('¿Vienes en auto?')
            ->assertSee('En taxi');
    }

    public function test_una_respuesta_que_ya_no_esta_configurada_no_se_pierde(): void
    {
        /*
        | El recado para los novios lo trae fija la plantilla, y una pregunta
        | borrada después del wizard deja respuestas guardadas. Las dos tienen
        | que seguir viéndose: alguien se tomó la molestia de contestarlas.
        */
        $this->responde('Betsy', ['Mensaje para los novios' => 'Los queremos']);

        $this->actingAs($this->organizer)
            ->get(route('organizer.rsvps.index'))
            ->assertOk()
            ->assertSee('Mensaje para los novios')
            ->assertSee('Los queremos');
    }

    private function responde(string $nombre, array $respuestas): void
    {
        $guest = Guest::factory()->create(['user_id' => $this->organizer->id, 'name' => $nombre]);

        $guest->events()->attach($this->event->id, ['uuid' => (string) Str::uuid(), 'max_passes' => 2]);

        Rsvp::create([
            'event_guest_id' => $guest->events()->where('event_id', $this->event->id)->first()->pivot->id,
            'attendance' => 'confirmed',
            'confirmed_passes' => 2,
            'answers' => $respuestas,
            'confirmed_at' => now(),
        ]);
    }
}
