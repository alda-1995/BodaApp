<?php

namespace Tests\Feature\Superadmin;

use App\Models\ColorPalette;
use App\Models\Event;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\ColorPalettesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Las paletas que deja sembradas el seeder.
 *
 * Sin ninguna, la pantalla de Configuración del organizador se queda sin
 * opciones que ofrecer.
 */
class ColorPalettesSeederTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ColorPalettesSeeder::class);
    }

    public function test_siembra_la_paleta_activa(): void
    {
        $palette = ColorPalette::sole();

        $this->assertSame('Paleta elegante', $palette->name);
        $this->assertTrue($palette->is_active);
    }

    public function test_sus_colores_son_los_que_usa_una_boda_sin_paleta(): void
    {
        /*
        | Los colores por omisión están escritos en Event, no leídos de aquí.
        | Si los dos se separan, una invitación sin paleta se vería distinta a
        | la misma invitación con "Paleta elegante", que es justo lo que nadie
        | esperaría. Esta prueba amarra los dos lados.
        */
        $palette = ColorPalette::sole();

        $event = Event::factory()->create([
            'user_id' => User::factory()->create()->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
        ]);

        $colors = $event->theme_colors;

        $this->assertSame($palette->primary_color, $colors['primary']);
        $this->assertSame($palette->secondary_color, $colors['secondary']);
        $this->assertSame($palette->accent_color, $colors['accent']);
    }

    public function test_correrlo_dos_veces_no_duplica_ni_pisa_lo_configurado(): void
    {
        // El superadmin retocó el color desde el panel.
        ColorPalette::sole()->update(['primary_color' => '#123456']);

        $this->seed(ColorPalettesSeeder::class);

        $this->assertSame(1, ColorPalette::count());
        $this->assertSame('#123456', ColorPalette::sole()->primary_color);
    }
}
