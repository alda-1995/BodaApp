<?php

namespace Tests\Unit\Support;

use App\Support\MediaArrayHelper;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * MediaArrayHelper separa, antes de guardar, lo que va al JSON 'features' de lo
 * que va a app_files. Un error aquí pierde datos en silencio.
 */
class MediaArrayHelperTest extends TestCase
{
    /* ---------------------------------------------------------------------
     | extractFormValues: lo que termina en features
     * -------------------------------------------------------------------*/

    public function test_omite_archivos_y_sus_campos_auxiliares(): void
    {
        $values = MediaArrayHelper::extractFormValues([
            'dress_code_type' => 'Formal',
            'reference_image' => UploadedFile::fake()->image('ref.jpg'),
            'reference_image_url' => 'https://x/ref.jpg',
            'reference_image_uuid' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        ]);

        $this->assertSame(['dress_code_type' => 'Formal'], $values);
    }

    public function test_conserva_nulls_de_campos_que_el_usuario_vacio(): void
    {
        // Vaciar un campo opcional debe persistir como null, no ignorarse.
        $values = MediaArrayHelper::extractFormValues(['color_or_theme' => null]);

        $this->assertArrayHasKey('color_or_theme', $values);
        $this->assertNull($values['color_or_theme']);
    }

    public function test_conserva_textos_normales_que_contienen_urls(): void
    {
        $values = MediaArrayHelper::extractFormValues(['events' => [[
            'name' => 'Ceremonia',
            'location_maps' => 'https://maps.app.goo.gl/abc',
        ]]]);

        $this->assertSame('https://maps.app.goo.gl/abc', $values['events'][0]['location_maps']);
    }

    public function test_limpia_filas_de_repeater_que_solo_tenian_imagen(): void
    {
        $values = MediaArrayHelper::extractFormValues(['photos' => [[
            'image' => UploadedFile::fake()->image('a.jpg'),
            'image_url' => null,
            'image_uuid' => null,
        ]]]);

        $this->assertSame([], $values, 'Una fila sólo de imagen no debe dejar basura en features.');
    }

    public function test_campo_de_texto_cuyo_nombre_termina_en_url_se_guarda(): void
    {
        // El admin de mesa de regalos genera las claves con slugify(label): una etiqueta
        // como "Lista URL" produce 'lista_url', que es un texto normal, no un auxiliar
        // de imagen. Hoy se descarta por el sufijo y el dato se pierde en silencio.
        $values = MediaArrayHelper::extractFormValues(['registries' => [[
            'type' => 'amazon',
            'lista_url' => 'https://amazon.com/lista',
        ]]]);

        $this->assertSame(
            'https://amazon.com/lista',
            $values['registries'][0]['lista_url'] ?? null,
            'BUG: un campo de texto con sufijo _url se descarta al guardar.'
        );
    }

    public function test_omite_auxiliares_aunque_no_llegue_el_input_de_archivo(): void
    {
        // Imagen conservada: sólo viajan url + uuid; siguen siendo auxiliares por ir en pareja.
        $values = MediaArrayHelper::extractFormValues([
            'dress_code_type' => 'Formal',
            'reference_image_url' => 'https://x/ref.jpg',
            'reference_image_uuid' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        ]);

        $this->assertSame(['dress_code_type' => 'Formal'], $values);
    }

    /* ---------------------------------------------------------------------
     | extractFilesAndMedia: lo que procesa app_files
     * -------------------------------------------------------------------*/

    public function test_un_texto_con_sufijo_url_no_se_toma_por_imagen(): void
    {
        $media = MediaArrayHelper::extractFilesAndMedia(['registries' => [[
            'type' => 'amazon',
            'lista_url' => 'https://amazon.com/lista',
        ]]]);

        $this->assertArrayNotHasKey('registries.0.lista', $media, "'lista_url' no es el auxiliar de una imagen 'lista'.");
    }

    public function test_detecta_archivo_de_nivel_superior(): void
    {
        $file = UploadedFile::fake()->image('ref.jpg');

        $media = MediaArrayHelper::extractFilesAndMedia(['reference_image' => $file]);

        $this->assertSame($file, $media['reference_image']);
    }

    public function test_detecta_imagen_existente_en_repeater_por_sus_auxiliares(): void
    {
        // Sin archivo nuevo: sólo url + uuid. Debe llegar como 'photos.0.image' => null
        // para que processStepFiles lo conserve en vez de borrarlo.
        $media = MediaArrayHelper::extractFilesAndMedia(['photos' => [[
            'image_url' => 'https://x/a.jpg',
            'image_uuid' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        ]]]);

        $this->assertArrayHasKey('photos.0.image', $media);
        $this->assertNull($media['photos.0.image']);
    }

    public function test_detecta_archivo_nuevo_en_repeater(): void
    {
        $file = UploadedFile::fake()->image('b.jpg');

        $media = MediaArrayHelper::extractFilesAndMedia(['photos' => [
            ['image_url' => 'https://x/a.jpg', 'image_uuid' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d'],
            ['image' => $file, 'image_url' => null, 'image_uuid' => null],
        ]]);

        $this->assertSame($file, $media['photos.1.image']);
        $this->assertArrayHasKey('photos.0.image', $media);
    }
}
