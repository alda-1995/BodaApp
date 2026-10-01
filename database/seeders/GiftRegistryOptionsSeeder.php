<?php

namespace Database\Seeders;

use App\Models\SystemSection;
use Illuminate\Database\Seeder;

/**
 * El catálogo de mesa de regalos que configura el superadmin.
 *
 * No es contenido de una boda: es la lista de tipos de mesa que existen en el
 * producto y, de cada uno, qué datos se le piden al organizador. De aquí salen
 * los campos del paso "Mesa de regalos" del wizard y las tarjetas que pinta la
 * invitación, así que sin esta fila ese paso queda vacío.
 *
 * Se siembra tal como está configurado hoy, para no tener que volver a
 * capturarlo a mano cada vez que se rehace la base.
 */
class GiftRegistryOptionsSeeder extends Seeder
{
    public function run(): void
    {
        /*
        | firstOrCreate y no updateOrCreate: si el catálogo ya existe, lo que
        | haya cambiado el superadmin desde el panel manda sobre esto. Un
        | seeder no debería pisar lo que alguien configuró.
        */
        SystemSection::firstOrCreate(
            ['key' => 'registries'],
            [
                'title' => 'Opciones de regalo',
                'type' => 'repeater',
                'parent' => 'gift_registry',
                'order' => 1,
                'is_global' => true,
                'is_active' => true,
                'schema' => $this->schema(),
            ],
        );
    }

    /**
     * Los tipos de mesa y los datos de cada uno, en el orden en que se preguntan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function schema(): array
    {
        return [
            [
                'key' => 'type',
                'type' => 'select',
                'label' => 'Tipo de mesa',
                'default' => 'cuenta_bancaria',
                'options' => [
                    ['label' => 'Cuenta Bancaria', 'value' => 'cuenta_bancaria'],
                    ['label' => 'Liverpool', 'value' => 'liverpool'],
                    ['label' => 'Amazon', 'value' => 'amazon'],
                ],
                'is_required' => '1',
                'placeholder' => 'Elige una opción',
            ],

            ...$this->datosDe('cuenta_bancaria', 'Cuenta Bancaria', [
                ['banco', 'text', 'Banco', 'Ej. BBVA', ['string', 'max:255']],
                ['nombre_del_novio', 'text', 'Nombre del novio', 'Ej. Alfonso Perez Perez', ['string', 'max:255']],
                ['clabe_bbva', 'number', 'Clabe BBVA', 'Ej. BBVA', ['numeric', 'max_digits:20']],
            ]),

            ...$this->datosDe('liverpool', 'Liverpool', [
                ['nombre_de_la_tienda', 'text', 'Nombre de la tienda', 'Ej. Liverpool', ['string', 'max:255']],
                ['numero_de_mesa', 'text', 'Número de mesa', 'Ingresa el número de tu mesa de regalos', ['string', 'max:255']],
                ['url', 'url', 'URL', 'Pega aquí el enlace de tu mesa de regalos', ['url:http,https', 'max:255']],
            ]),

            /*
            | 'nombre_de_la_tienda' se repite a propósito: Amazon también la
            | pide. Son dos datos distintos de dos tipos distintos, y el
            | compilador del formulario los funde en un solo campo cuyo
            | required_if nombra a los dos tipos.
            */
            ...$this->datosDe('amazon', 'Amazon', [
                ['nombre_de_la_tienda', 'text', 'Nombre de la tienda', 'Ej. Amazon', ['string', 'max:255']],
                ['url_de_la_lista_de_regalos', 'url', 'URL de la lista de regalos', 'Pega aquí el enlace de tu lista de regalos de Amazon', ['url:http,https', 'max:255']],
                ['texto_del_boton', 'text', 'Texto del bóton', 'Ej. Ingresa aquí', ['string', 'max:255']],
            ]),
        ];
    }

    /**
     * Los datos de un tipo de mesa.
     *
     * Todos se piden igual —obligatorios, visibles sólo cuando se eligió ese
     * tipo y con el mismo aviso cuando faltan—, así que lo único propio de cada
     * uno es su llave, su control, su etiqueta, su ejemplo y sus reglas.
     *
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string, 4: array<int, string>}>  $datos
     * @return array<int, array<string, mixed>>
     */
    private function datosDe(string $tipo, string $etiquetaDelTipo, array $datos): array
    {
        return array_map(fn (array $dato) => [
            'key' => $dato[0],
            'type' => $dato[1],
            'label' => $dato[2],
            'rules' => $dato[4],
            'messages' => [
                'required_if' => "El campo :attribute es obligatorio cuando el tipo de mesa es {$etiquetaDelTipo}",
            ],
            'depends_on' => ['field' => 'type', 'values' => [$tipo]],
            'is_required' => '1',
            'placeholder' => $dato[3],
        ], $datos);
    }
}
