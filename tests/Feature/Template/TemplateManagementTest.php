<?php

namespace Tests\Feature\Template;

use App\Models\Template;
use App\Models\User;
use App\Services\Template\TemplateDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Stripe\StripeClient;
use Stripe\Service\ProductService;
use Stripe\Service\PriceService;
use Stripe\Product;
use Stripe\Price;
use Mockery;
use Tests\TestCase;

class TemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $stripeMock;
    protected $productServiceMock;
    protected $priceServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // 💡 Esto le indica a Laravel que ignore TODOS los middlewares de la ruta para los tests de esta clase
        $this->withoutMiddleware();

        // Mocks para Stripe...
        $this->stripeMock = Mockery::mock(StripeClient::class);
        $this->productServiceMock = Mockery::mock(ProductService::class);
        $this->priceServiceMock = Mockery::mock(PriceService::class);

        $this->stripeMock->products = $this->productServiceMock;
        $this->stripeMock->prices = $this->priceServiceMock;

        $this->app->instance(StripeClient::class, $this->stripeMock);

        // Mock del Discovery Service
        $this->mock(TemplateDiscoveryService::class, function ($mock) {
            $mock->shouldReceive('getAvailableViews')->andReturn([
                'build-templates.boda-test' => 'boda-test'
            ]);
        });
    }

    /** @test */
    public function user_can_view_paginated()
    {
        $this->withoutExceptionHandling();

        view()->share('errors', new \Illuminate\Support\ViewErrorBag());

        Template::factory()->count(15)->create();

        $response = $this->get(route('templates.index'));

        $response->assertStatus(200);
        $response->assertViewIs('templates.index');
        $response->assertViewHas('templates');
    }

    /** @test */
    public function user_view_template_by_slug()
    {
        $this->withoutExceptionHandling();
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        
        $template = Template::factory()->create([
            'name' => 'Template Test', 
            'is_active' => true
        ]);

        $response = $this->get(route('templates.show', $template->slug));

        $response->assertStatus(200);
        $response->assertViewIs('templates.show');
        $response->assertViewHas('template');
    }

    /** @test */
    public function register_success_template_with_stripe_redirect()
    {
        $this->withoutExceptionHandling();

        view()->share('errors', new \Illuminate\Support\ViewErrorBag());

        // Mock estricto pero seguro para la fachada View
        View::shouldReceive('exists')
            ->with('build-templates.boda-test')
            ->andReturn(true);
            
        // Con esto le decimos a Mockery que cualquier otra llamada (como renderizar la vista index) 
        // la procese de manera normal usando el motor real de Laravel.
        View::shouldReceive('make')
            ->byDefault()
            ->passthru();

        // Configuración de los Mocks de Stripe (Product)
        $mockProduct = new Product('prod_123');
        $this->productServiceMock
            ->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Nueva Plantilla Test',
                'description' => 'Nueva Plantilla Test',
            ])
            ->andReturn($mockProduct);

        // Configuración de los Mocks de Stripe (Price)
        $mockPrice = new Price('price_ABC456');
        $this->priceServiceMock
            ->shouldReceive('create')
            ->once()
            ->with([
                'product' => 'prod_123',
                'unit_amount' => 55000,
                'currency' => 'mxn'
            ])
            ->andReturn($mockPrice);

        $response = $this->post(route('templates.store'), [
            'name'      => 'Nueva Plantilla Test',
            'price'     => 550.00,
            'view_path' => 'build-templates.boda-test',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('templates.index'));
        $response->assertSessionHas('success', 'Plantilla creada correctamente y sincronizada con Stripe.');

        $this->assertDatabaseHas('templates', [
            'slug'            => 'nueva-plantilla-test',
            'stripe_price_id' => 'price_ABC456',
            'price'           => 550.00,
            'view_path'       => 'build-templates.boda-test'
        ]);
    }

    /** @test */
    public function dont_register_template_with_stripe_no_redirect()
    {
        \Illuminate\Support\Facades\Facade::clearResolvedInstance('view');
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());

        View::shouldReceive('exists')
            ->with('build-templates.boda-test')
            ->andReturn(true);

        View::shouldReceive('make')
            ->byDefault()
            ->passthru();

        $this->productServiceMock
            ->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Stripe API Error: Invalid API Key'));

        $response = $this->from(route('templates.create'))->post(route('templates.store'), [
            'name'      => 'Plantilla Fallida',
            'price'     => 120.00,
            'view_path' => 'build-templates.boda-test',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('templates.create'));
        $response->assertSessionHas('error', 'Error al sincronizar con Stripe');

        $this->assertDatabaseMissing('templates', [
            'slug' => 'plantilla-fallida'
        ]);
    }

    /** @test */
    public function validate_invalid_fields_requests()
    {
        $response = $this->post(route('templates.store'), [
            'name'      => '', // Inválido
            'price'     => 'no-es-un-numero', // Inválido
            'view_path' => '', // Inválido
        ]);

        $response->assertSessionHasErrors(['name', 'price', 'view_path']);
    }

    /** @test */
    public function not_update_price_same_price_stripe()
    {
        $this->withoutExceptionHandling();
        

        \Illuminate\Support\Facades\Facade::clearResolvedInstance('view');
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());

        // 2. Mockeamos la validación del FormRequest para que acepte la vista
        View::shouldReceive('exists')
            ->with('build-templates.boda-test')
            ->andReturn(true);

        View::shouldReceive('make')
            ->byDefault()
            ->passthru();

        // 3. Creamos el registro base
        $template = Template::factory()->create([
            'price' => 300.00,
            'stripe_price_id' => 'price_actual',
            'view_path' => 'build-templates.boda-test'
        ]);

        // 4. Aseguramos que Stripe no sea invocado ya que el precio es idéntico
        $this->priceServiceMock->shouldNotReceive('create');
        $this->priceServiceMock->shouldNotReceive('update');

        // 5. Ejecutamos la actualización
        $response = $this->put(route('templates.update', $template->id), [
            'name'      => 'Nombre Modificado',
            'slug'      => $template->slug,
            'price'     => 300.00,
            'view_path' => 'build-templates.boda-test',
            'is_active' => true
        ]);

        // 6. Aseveraciones
        $response->assertRedirect(route('templates.index'));
        $this->assertDatabaseHas('templates', [
            'id'              => $template->id,
            'name'            => 'Nombre Modificado',
            'stripe_price_id' => 'price_actual'
        ]);
    }

    /** @test */
    public function update_price_stripe_desactive_price_and_update_price()
    {
        $this->withoutExceptionHandling();

        // 1. PRIMERO: Limpiamos por completo la fachada de vistas de tests previos
        \Illuminate\Support\Facades\Facade::clearResolvedInstance('view');
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());

        // 2. SEGUNDO: Definimos el comportamiento de simulación para las vistas
        \Illuminate\Support\Facades\View::shouldReceive('exists')
            ->with('build-templates.boda-test')
            ->andReturn(true);

        \Illuminate\Support\Facades\View::shouldReceive('make')
            ->byDefault()
            ->passthru();

        // 3. TERCERO: Ahora sí, creamos de forma segura el registro usando el Factory
        $template = Template::factory()->create([
            'price' => 300.00,
            'stripe_price_id' => 'price_viejo',
            'view_path' => 'build-templates.boda-test'
        ]);

        // 4. Mocks de Stripe (Permanecen intactos)
        $this->priceServiceMock
            ->shouldReceive('update')
            ->once()
            ->with('price_viejo', ['active' => false]);

        $mockPriceData = \Stripe\Price::constructFrom([
            'id' => 'price_viejo',
            'product' => 'prod_asociado'
        ]);

        $this->priceServiceMock
            ->shouldReceive('retrieve')
            ->once()
            ->with('price_viejo')
            ->andReturn($mockPriceData);

        $mockNewPrice = \Stripe\Price::constructFrom([
            'id' => 'price_nuevo_monto'
        ]);

        $this->priceServiceMock
            ->shouldReceive('create')
            ->once()
            ->with([
                'product' => 'prod_asociado',
                'unit_amount' => 45000,
                'currency' => 'mxn'
            ])
            ->andReturn($mockNewPrice);

        // 5. Ejecutamos la petición PUT
        $response = $this->put(route('templates.update', $template->id), [
            'name'      => $template->name,
            'slug'      => $template->slug,
            'price'     => 450.00,
            'view_path' => 'build-templates.boda-test',
            'is_active' => true
        ]);

        // 6. Aseveraciones finales
        $response->assertRedirect(route('templates.index'));
        $this->assertDatabaseHas('templates', [
            'id'              => $template->id,
            'price'           => 450.00,
            'stripe_price_id' => 'price_nuevo_monto'
        ]);
    }

    /** @test */
    public function delete_apply_softdelete_inactive_price_stripe()
    {
        $this->withoutExceptionHandling();

        $template = Template::factory()->create([
            'stripe_price_id' => 'price_a_borrar'
        ]);

        $mockPrice = \Stripe\Price::constructFrom([
            'id' => 'price_a_borrar',
            'product' => 'prod_a_borrar'
        ]);

        $this->priceServiceMock
            ->shouldReceive('retrieve')
            ->once()
            ->with('price_a_borrar')
            ->andReturn($mockPrice);

        $this->priceServiceMock
            ->shouldReceive('update')
            ->once()
            ->with('price_a_borrar', ['active' => false]);

        $this->productServiceMock
            ->shouldReceive('update')
            ->once()
            ->with('prod_a_borrar', ['active' => false]);

        $response = $this->delete(route('templates.destroy', $template->id));

        $response->assertRedirect(route('templates.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('templates', [
            'id' => $template->id
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}