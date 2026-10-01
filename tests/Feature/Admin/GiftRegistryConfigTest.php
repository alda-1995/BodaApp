<?php

namespace Tests\Feature\Admin;

use App\FormBuilder\FormSchemaCompiler;
use App\Models\Role;
use App\Models\SystemSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Configuración de tipos de mesa de regalo (super admin).
 */
class GiftRegistryConfigTest extends TestCase
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

    public function test_los_tipos_de_mesa_conservan_el_orden_en_que_se_crearon(): void
    {
        // La columna JSON de MySQL reordena las claves de un objeto por longitud; con
        // options como objeto, este orden volvía como amazon, liverpool, cuenta_bancaria.
        $orden = ['cuenta_bancaria', 'amazon', 'liverpool'];

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::firstOrCreate(['name' => 'superadmin'], ['display_name' => 'SuperAdmin']));

        $schema = [
            ['key' => 'type', 'type' => 'select', 'label' => 'Tipo de mesa', 'default' => 'cuenta_bancaria', 'is_required' => '1',
                'options' => [
                    ['value' => 'cuenta_bancaria', 'label' => 'Cuenta bancaria'],
                    ['value' => 'amazon', 'label' => 'Amazon'],
                    ['value' => 'liverpool', 'label' => 'Liverpool'],
                ]],
            ['key' => 'banco', 'type' => 'text', 'label' => 'Banco', 'is_required' => '1',
                'rules' => ['string', 'max:255'],
                'depends_on' => ['field' => 'type', 'values' => ['cuenta_bancaria']]],
        ];

        $this->actingAs($admin)
            ->put(route('gift-registry.update', 'gift_registry'), [
                'key' => 'registries', 'parent' => 'gift_registry', 'title' => 'Opciones de regalo',
                'order' => 1, 'is_global' => 1, 'type' => 'repeater', 'is_active' => 1,
                'schema' => json_encode($schema),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        // 1. Lo que quedó en la columna JSON
        $section = SystemSection::where('key', 'registries')->sole();
        $this->assertSame($orden, array_column($section->schema[0]['options'], 'value'));

        // 2. Lo que ve el organizador en el selector "Tipo de mesa" del wizard
        $select = collect((new FormSchemaCompiler($section))->build()->getSchema())
            ->first(fn ($field) => $field->getName() === 'type');
        $this->assertSame($orden, array_keys($select->getOptions()));
        $this->assertSame('cuenta_bancaria', $select->getDefault(), 'El tipo por defecto debe ser el primero creado.');
    }
}
