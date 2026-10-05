<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

/**
 * Las plantillas que se ofrecen a la venta.
 *
 * Cada una apunta a su vista (view_path), que es lo que
 * TemplateDiscoveryService usa para resolver su estrategia. Sin su fila aquí la
 * plantilla existe en el código pero nadie puede comprarla ni verla.
 *
 * Se siembran sin stripe_price_id: ése lo genera el panel del superadmin al
 * ponerle precio, y un id inventado rompería el cobro.
 */
class TemplatesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            /*
            | firstOrCreate y no updateOrCreate: si la plantilla ya existe, lo
            | que haya cambiado el superadmin desde el panel —su precio, su
            | vigencia— manda sobre esto. Un seeder no debería pisarlo.
            */
            Template::firstOrCreate(['view_path' => $template['view_path']], $template);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function templates(): array
    {
        return [
            [
                'name' => 'Boda de ensueño',
                'slug' => 'boda-de-ensueno',
                'view_path' => 'build-templates.template-editorial.index',
                'price' => 100.00,
                'is_active' => true,
                'duration_days' => 21,
            ],
            [
                'name' => 'Boda Destino',
                'slug' => 'boda-destino',
                'view_path' => 'build-templates.template-destino.index',
                'price' => 100.00,
                'is_active' => true,
                'duration_days' => 21,
            ],
        ];
    }
}
