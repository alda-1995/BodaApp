<?php

namespace Database\Seeders;

use App\Models\ColorPalette;
use Illuminate\Database\Seeder;

/**
 * Las paletas que puede elegir el organizador en Configuración.
 *
 * Son configuración del producto, no de una boda: de aquí sale la lista que se
 * le ofrece, y la elegida entra a la invitación como --color-brand. Sin ninguna
 * paleta esa pantalla queda sin opciones.
 *
 * Se siembra tal como está configurada hoy, para no tener que volver a
 * capturarla cada vez que se rehace la base.
 */
class ColorPalettesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->palettes() as $palette) {
            /*
            | firstOrCreate y no updateOrCreate: si la paleta ya existe, lo que
            | haya cambiado el superadmin desde el panel manda sobre esto. Un
            | seeder no debería pisar lo que alguien configuró.
            */
            ColorPalette::firstOrCreate(['name' => $palette['name']], $palette);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function palettes(): array
    {
        return [
            /*
            | Estos son los mismos colores que Event::getThemeColorsAttribute()
            | usa cuando una boda todavía no eligió paleta. Si se cambian aquí,
            | conviene cambiarlos allá: si no, una invitación sin paleta se
            | vería distinta a la misma invitación con "Paleta elegante".
            */
            [
                'name' => 'Paleta elegante',
                'primary_color' => '#1E1E1E',
                'secondary_color' => '#F5E9DC',
                'accent_color' => '#FFFFFF',
                'is_active' => true,
            ],
        ];
    }
}
