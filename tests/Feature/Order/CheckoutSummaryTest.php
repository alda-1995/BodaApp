<?php

namespace Tests\Feature\Order;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Resumen de compra: lo que alguien ve justo antes de pagar.
 *
 * Es la última pantalla donde puede darse cuenta de que está comprando otra
 * cosa, así que lo que diga tiene que salir de SU plantilla. Esta tarjeta vivía
 * escrita a mano ("Alfonso y Elena", "Categoría: Elegante") y decía lo mismo
 * comprara quien comprara lo que comprara.
 */
class CheckoutSummaryTest extends TestCase
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

    public function test_el_resumen_muestra_la_plantilla_que_se_va_a_comprar(): void
    {
        $template = Template::factory()->create([
            'name' => 'Boda Destino',
            'slug' => 'boda-destino',
            'price' => 1499,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $this->get(route('checkout.checkout-preview', $template->slug))
            ->assertOk()
            ->assertSee('Boda Destino')
            ->assertSee('$1,499.00 MXN')
            // La vigencia no se promete en el resumen.
            ->assertDontSee('días después de tu boda');
    }

    public function test_cada_plantilla_ensena_lo_suyo_y_no_lo_de_la_otra(): void
    {
        $destino = Template::factory()->create(['name' => 'Boda Destino', 'slug' => 'boda-destino', 'price' => 1499]);
        $editorial = Template::factory()->create(['name' => 'Boda Editorial', 'slug' => 'boda-editorial', 'price' => 990]);

        $this->get(route('checkout.checkout-preview', $destino->slug))
            ->assertOk()
            ->assertSee('Boda Destino')
            ->assertDontSee('Boda Editorial')
            ->assertSee('$1,499.00')
            ->assertDontSee('$990.00');

        $this->get(route('checkout.checkout-preview', $editorial->slug))
            ->assertOk()
            ->assertSee('Boda Editorial')
            ->assertDontSee('Boda Destino')
            ->assertSee('$990.00')
            ->assertDontSee('$1,499.00');
    }

    public function test_no_quedan_datos_de_ejemplo_escritos_a_mano(): void
    {
        $template = Template::factory()->create(['name' => 'Boda Destino', 'slug' => 'boda-destino']);

        $html = $this->get(route('checkout.checkout-preview', $template->slug))->assertOk();

        // Una pareja y una categoría que no son de nadie y que no existen en la base.
        $html->assertDontSee('Alfonso y Elena')
            ->assertDontSee('Categoría');
    }

    public function test_el_resumen_lleva_al_pago_de_esa_misma_plantilla(): void
    {
        $template = Template::factory()->create(['name' => 'Boda Destino', 'slug' => 'boda-destino', 'price' => 1499]);

        $this->get(route('checkout.checkout-preview', $template->slug))
            ->assertOk()
            ->assertSee(route('checkout.detail-payment', $template->slug))
            ->assertSee(route('templates.preview', $template->slug));
    }

    public function test_el_paso_del_pago_cobra_por_esa_plantilla(): void
    {
        $template = Template::factory()->create(['name' => 'Boda Destino', 'slug' => 'boda-destino', 'price' => 1499]);

        $this->get(route('checkout.detail-payment', $template->slug))
            ->assertOk()
            // El id que viaja al checkout de Stripe es el de esta plantilla.
            ->assertSee('value="' . $template->id . '"', false)
            ->assertSee('$1,499.00 MXN');
    }
}
