<?php

namespace Tests\Feature\Template;

use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Services\FileStorageService;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Stripe\Price;
use Stripe\Product;
use Stripe\Service\PriceService;
use Stripe\Service\ProductService;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * Con qué se presenta una plantilla antes de comprarla: su foto y su texto.
 *
 * Los dos salen de la base y los pintan la landing y el resumen de compra. Antes
 * ese párrafo estaba escrito en el Blade y era el mismo para todas, comprara
 * quien comprara lo que comprara.
 *
 * La foto se guarda como AppFile, igual que las del wizard, para que el borrado
 * y el reemplazo los lleve el mismo servicio de archivos.
 */
class TemplatePresentationTest extends TestCase
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

        Storage::fake('public');
        $this->seed(RolesAndAdminSeeder::class);
    }

    /* ---------------------------------------------------------------------
     | Lo que ve quien todavía no compra
     * -------------------------------------------------------------------*/

    public function test_la_landing_pinta_la_foto_y_el_texto_de_cada_plantilla(): void
    {
        $template = $this->conImagen($this->template([
            'description' => 'Para bodas en la playa, con cuenta regresiva y vuelos.',
        ]));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Para bodas en la playa, con cuenta regresiva y vuelos.')
            ->assertSee($template->previewImageUrl(), false);
    }

    public function test_sin_foto_ni_texto_la_landing_no_se_rompe(): void
    {
        $this->template(['description' => null]);

        $this->get(route('home'))
            ->assertOk()
            // El párrafo de siempre, para que la tarjeta no quede muda.
            ->assertSee('Portada, itinerario, mesa de regalos');
    }

    public function test_el_resumen_de_compra_pinta_los_de_su_plantilla(): void
    {
        $template = $this->conImagen($this->template([
            'description' => 'Para bodas en la playa, con cuenta regresiva y vuelos.',
        ]));

        $this->get(route('checkout.checkout-preview', $template->slug))
            ->assertOk()
            ->assertSee('Para bodas en la playa, con cuenta regresiva y vuelos.')
            ->assertSee($template->previewImageUrl(), false);
    }

    /* ---------------------------------------------------------------------
     | El formulario del superadmin
     * -------------------------------------------------------------------*/

    public function test_usa_el_mismo_control_de_imagen_que_el_wizard(): void
    {
        $template = $this->template();

        $html = $this->actingAs($this->superadmin())
            ->get(route('templates.edit', $template->id))
            ->assertOk()
            ->getContent();

        // El control del wizard: arrastrar y soltar, con su vista previa.
        $this->assertStringContainsString('imageUploader({', $html);
        $this->assertStringContainsString('name="preview_image_url"', $html);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
    }

    public function test_el_superadmin_sube_la_foto_y_escribe_el_texto(): void
    {
        $this->mockStripeCreate();

        $this->actingAs($this->superadmin())
            ->post(route('templates.store'), [
                'name' => 'Boda Destino',
                'price' => 1500,
                'view_path' => 'build-templates.template-editorial.index',
                'description' => 'Para bodas en la playa.',
                'preview_image' => UploadedFile::fake()->image('portada.jpg'),
            ])
            ->assertRedirect(route('templates.index'));

        // Por nombre y no con sole(): otra prueba de la suite deja plantillas
        // escritas fuera de su transacción y la tabla no siempre llega vacía.
        $template = Template::where('name', 'Boda Destino')->sole();

        $this->assertSame('Para bodas en la playa.', $template->description);
        $this->assertNotNull($template->previewImage());
        Storage::disk('public')->assertExists($template->previewImage()->file_path);
    }

    /**
     * El campo de archivo llega vacío cada vez que se abre el formulario, así
     * que no mandar foto significa "deja la que estaba", no "bórrala". Eso lo
     * distingue la URL que el control reenvía.
     */
    public function test_editar_sin_subir_foto_conserva_la_que_tenia(): void
    {
        $template = $this->conImagen($this->template());
        $ruta = $template->previewImage()->file_path;

        $this->actingAs($this->superadmin())
            ->put(route('templates.update', $template->id), [
                'name' => $template->name,
                'price' => $template->price,
                'description' => 'Texto nuevo',
                'preview_image_url' => $template->previewImageUrl(),
            ])
            ->assertRedirect(route('templates.index'));

        $template = $template->fresh();

        $this->assertSame($ruta, $template->previewImage()?->file_path);
        $this->assertSame('Texto nuevo', $template->description);
        Storage::disk('public')->assertExists($ruta);
    }

    public function test_subir_otra_foto_borra_la_anterior(): void
    {
        $template = $this->conImagen($this->template());
        $anterior = $template->previewImage()->file_path;

        $this->actingAs($this->superadmin())
            ->put(route('templates.update', $template->id), [
                'name' => $template->name,
                'price' => $template->price,
                'preview_image' => UploadedFile::fake()->image('nueva.jpg'),
                'preview_image_url' => $template->previewImageUrl(),
            ])
            ->assertRedirect(route('templates.index'));

        $template = $template->fresh();

        $this->assertNotSame($anterior, $template->previewImage()?->file_path);
        Storage::disk('public')->assertExists($template->previewImage()->file_path);
        // No se deja basura, ni en el disco ni en la tabla de archivos.
        Storage::disk('public')->assertMissing($anterior);
        $this->assertCount(1, $template->files);
    }

    /** El bote de basura del control: manda la URL vacía y sin archivo nuevo. */
    public function test_quitar_la_foto_la_borra(): void
    {
        $template = $this->conImagen($this->template());
        $ruta = $template->previewImage()->file_path;

        $this->actingAs($this->superadmin())
            ->put(route('templates.update', $template->id), [
                'name' => $template->name,
                'price' => $template->price,
                'preview_image_url' => '',
            ])
            ->assertRedirect(route('templates.index'));

        $this->assertNull($template->fresh()->previewImage());
        Storage::disk('public')->assertMissing($ruta);
    }

    /* ---------------------------------------------------------------------
     | Lo que se le dice al superadmin cuando algo está mal
     * -------------------------------------------------------------------*/

    public function test_un_archivo_que_no_es_imagen_se_rechaza_en_espanol(): void
    {
        $template = $this->template();

        $this->actingAs($this->superadmin())
            ->put(route('templates.update', $template->id), [
                'name' => $template->name,
                'price' => $template->price,
                'preview_image' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors([
                'preview_image' => 'La imagen de presentación debe ser un archivo de imagen.',
            ]);
    }

    public function test_una_imagen_demasiado_pesada_se_rechaza_en_espanol(): void
    {
        $template = $this->template();

        $this->actingAs($this->superadmin())
            ->put(route('templates.update', $template->id), [
                'name' => $template->name,
                'price' => $template->price,
                'preview_image' => UploadedFile::fake()->image('enorme.jpg')->size(6000),
            ])
            ->assertSessionHasErrors([
                'preview_image' => 'La imagen de presentación no puede pesar más de 5 MB.',
            ]);
    }

    public function test_el_texto_tiene_un_tope_y_lo_dice_en_espanol(): void
    {
        $template = $this->template();

        $this->actingAs($this->superadmin())
            ->put(route('templates.update', $template->id), [
                'name' => $template->name,
                'price' => $template->price,
                'description' => str_repeat('a', 501),
            ])
            ->assertSessionHasErrors([
                'description' => 'La descripción no puede superar los 500 caracteres.',
            ]);
    }

    /** Un envío rechazado no debe dejar a medias lo que ya estaba guardado. */
    public function test_un_envio_rechazado_no_toca_la_foto_que_ya_habia(): void
    {
        $template = $this->conImagen($this->template());
        $ruta = $template->previewImage()->file_path;

        $this->actingAs($this->superadmin())
            ->put(route('templates.update', $template->id), [
                'name' => $template->name,
                'price' => $template->price,
                'description' => str_repeat('a', 501),
                'preview_image_url' => $template->previewImageUrl(),
            ])
            ->assertSessionHasErrors('description');

        $this->assertSame($ruta, $template->fresh()->previewImage()?->file_path);
        Storage::disk('public')->assertExists($ruta);
    }

    /* ---------------------------------------------------------------------
     | Cuando el disco falla
     |
     | Lo demás ya se guardó, así que decírselo con un error genérico le haría
     | creer que no se guardó nada —y antes además culpaba a Stripe.
     * -------------------------------------------------------------------*/

    public function test_si_falla_al_subir_la_imagen_lo_dice_sin_perder_lo_demas(): void
    {
        $template = $this->template(['description' => 'Texto viejo']);
        $this->discoQueFalla('store');

        $this->actingAs($this->superadmin())
            ->put(route('templates.update', $template->id), [
                'name' => $template->name,
                'price' => $template->price,
                'description' => 'Texto nuevo',
                'preview_image' => UploadedFile::fake()->image('portada.jpg'),
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHas('warning', 'Se guardaron los cambios de la plantilla, pero no pudimos subir la imagen. Vuelve a intentarlo.')
            ->assertSessionMissing('error');

        // Lo que sí se guardó, se guardó: el aviso no miente.
        $this->assertSame('Texto nuevo', $template->fresh()->description);
    }

    public function test_si_falla_al_crear_avisa_que_la_plantilla_si_existe(): void
    {
        $this->mockStripeCreate();
        $this->discoQueFalla('store');

        $this->actingAs($this->superadmin())
            ->post(route('templates.store'), [
                'name' => 'Boda Destino',
                'price' => 1500,
                'view_path' => 'build-templates.template-editorial.index',
                'preview_image' => UploadedFile::fake()->image('portada.jpg'),
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHas('warning', 'La plantilla se creó, pero no pudimos subir su imagen. Edítala para intentarlo de nuevo.');

        // Antes decía "Error al sincronizar con Stripe", que era falso: Stripe
        // funcionó y la plantilla quedó creada.
        $this->assertTrue(Template::where('name', 'Boda Destino')->exists());
    }

    public function test_si_falla_al_quitar_la_imagen_tambien_lo_dice(): void
    {
        $template = $this->conImagen($this->template());
        $this->discoQueFalla('delete');

        $this->actingAs($this->superadmin())
            ->put(route('templates.update', $template->id), [
                'name' => $template->name,
                'price' => $template->price,
                'preview_image_url' => '',
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHas('warning', 'Se guardaron los cambios de la plantilla, pero no pudimos quitar la imagen. Vuelve a intentarlo.');
    }

    /* ------------------------------------------------------------------ */

    /** Un disco que revienta en la operación indicada, como uno lleno o sin permisos. */
    private function discoQueFalla(string $operacion): void
    {
        $this->mock(FileStorageService::class, function ($mock) use ($operacion) {
            $metodos = $operacion === 'delete'
                ? ['deleteFile']
                : ['store', 'replace'];

            foreach ($metodos as $metodo) {
                $mock->shouldReceive($metodo)->andThrow(new RuntimeException('Disco lleno'));
            }

            $mock->shouldReceive('getFilesBySection')->andReturn(collect());
        });
    }

    private function template(array $attributes = []): Template
    {
        return Template::factory()->create(array_merge([
            // Nombre propio: la factory usa una palabra de un vocabulario corto y
            // el slug choca con las plantillas que otras pruebas dejan escritas.
            'name' => 'Plantilla ' . Str::random(8),
            'view_path' => 'build-templates.template-editorial.index',
            'is_active' => true,
            'price' => 1500,
        ], $attributes));
    }

    /** Le cuelga su imagen de presentación, por donde la cuelga la aplicación. */
    private function conImagen(Template $template): Template
    {
        app(FileStorageService::class)->store(
            $template,
            UploadedFile::fake()->image('portada.jpg'),
            Template::PRESENTATION_SECTION,
            Template::PREVIEW_IMAGE_FIELD,
        );

        return $template->fresh();
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::named('superadmin')->id);

        return $user;
    }

    /** Crear una plantilla da de alta su producto y su precio en Stripe. */
    private function mockStripeCreate(): void
    {
        $products = Mockery::mock(ProductService::class);
        $products->shouldReceive('create')->once()->andReturn(new Product('prod_test'));

        $prices = Mockery::mock(PriceService::class);
        $prices->shouldReceive('create')->once()->andReturn(new Price('price_test'));

        $stripe = Mockery::mock(StripeClient::class);
        $stripe->products = $products;
        $stripe->prices = $prices;

        $this->app->instance(StripeClient::class, $stripe);
    }
}
