<?php

namespace Tests\Unit\FormBuilder;

use App\FormBuilder\Controls\ImageUploadField;
use App\FormBuilder\Controls\InlineTextareaField;
use App\FormBuilder\Controls\InlineTextField;
use App\FormBuilder\Controls\RepeaterField;
use App\FormBuilder\Controls\SelectField;
use App\FormBuilder\Controls\TextField;
use App\FormBuilder\Controls\UrlField;
use App\FormBuilder\FormSchemaCompiler;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Reglas de validación que genera el FormBuilder. Son la primera línea del
 * guardado: si una regla sale mal, el paso no se guarda o se guarda basura.
 */
class FieldRulesTest extends TestCase
{
    /* ---------------------------------------------------------------------
     | Presencia derivada de la intención del campo
     * -------------------------------------------------------------------*/

    public function test_campo_obligatorio_genera_required(): void
    {
        $rules = TextField::make('name_event', 'Nombre')->required()->toValidationRules();

        $this->assertSame(['name_event' => ['required']], $rules);
    }

    public function test_campo_opcional_genera_nullable(): void
    {
        $rules = TextField::make('color', 'Color')->nullable()->toValidationRules();

        $this->assertSame(['color' => ['nullable']], $rules);
    }

    public function test_required_dentro_de_rules_se_promueve_sin_duplicarse(): void
    {
        // Así está definido 'events.*.name' en DefaultTemplateStrategy.
        $field = TextField::make('name', 'Nombre')->rules(['required', 'string', 'max:255']);

        $this->assertTrue($field->isRequired());
        $this->assertSame(['name' => ['required', 'string', 'max:255']], $field->toValidationRules());
    }

    public function test_reglas_en_formato_string_con_pipes(): void
    {
        $rules = TextField::make('titulo', 'Título')->rules('string|max:100')->toValidationRules();

        $this->assertSame(['titulo' => ['nullable', 'string', 'max:100']], $rules);
    }

    /* ---------------------------------------------------------------------
     | Campos condicionales (dependsOn)
     * -------------------------------------------------------------------*/

    public function test_condicional_obligatorio_usa_el_prefijo_del_contenedor(): void
    {
        $rules = TextField::make('banco', 'Banco')
            ->required()
            ->rules(['string', 'max:255'])
            ->dependsOn('type', ['cuenta_bancaria'])
            ->toValidationRules('registries.*');

        $this->assertSame([
            'registries.*.banco' => ['nullable', 'required_if:registries.*.type,cuenta_bancaria', 'string', 'max:255'],
        ], $rules);
    }

    public function test_condicional_con_varios_valores(): void
    {
        $rules = TextField::make('sucursal', 'Sucursal')
            ->required()
            ->dependsOn('type', ['liverpool', 'palacio'])
            ->toValidationRules('registries.*');

        $this->assertSame(['nullable', 'required_if:registries.*.type,liverpool,palacio'], $rules['registries.*.sucursal']);
    }

    public function test_condicional_opcional_no_genera_required_if(): void
    {
        $rules = TextField::make('referencia', 'Referencia')
            ->rules(['string'])
            ->dependsOn('type', ['cuenta_bancaria'])
            ->toValidationRules('registries.*');

        $this->assertSame(['nullable', 'string'], $rules['registries.*.referencia']);
    }

    public function test_presencia_hardcodeada_legacy_se_descarta_y_se_rederiva(): void
    {
        // Schemas guardados en BD antes del refactor traían el prefijo hardcodeado.
        $rules = TextField::make('banco', 'Banco')
            ->required()
            ->rules(['required_if:registries.*.type,cuenta_bancaria', 'string', 'max:255'])
            ->dependsOn('type', ['cuenta_bancaria'])
            ->toValidationRules('mesas.*');

        $this->assertSame(
            ['nullable', 'required_if:mesas.*.type,cuenta_bancaria', 'string', 'max:255'],
            $rules['mesas.*.banco'],
            'La regla debe apuntar al prefijo real, no al hardcodeado.'
        );
    }

    public function test_condicional_vacio_no_falla_por_reglas_de_formato(): void
    {
        // ConvertEmptyStringsToNull convierte los campos no aplicables en null.
        $rules = $this->registriesRepeater()->toValidationRules();

        $validator = Validator::make(['registries' => [[
            'type' => 'cuenta_bancaria',
            'banco' => 'BBVA',
            'lista' => null,        // url: no aplica a cuenta_bancaria
            'numero_de_mesa' => null, // numeric: no aplica
        ]]], $rules);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray()));
    }

    public function test_condicional_obligatorio_falla_cuando_aplica(): void
    {
        $rules = $this->registriesRepeater()->toValidationRules();

        $validator = Validator::make(['registries' => [['type' => 'cuenta_bancaria', 'banco' => null]]], $rules);

        $this->assertTrue($validator->errors()->has('registries.0.banco'));
    }

    /* ---------------------------------------------------------------------
     | Repeaters
     * -------------------------------------------------------------------*/

    public function test_repeater_obligatorio(): void
    {
        $rules = RepeaterField::make('faqs', 'FAQ')->required()->schema([
            InlineTextField::make('question', 'Pregunta')->required(),
        ])->toValidationRules();

        $this->assertSame(['required', 'array'], $rules['faqs']);
        $this->assertSame(['required', 'string', 'max:255'], $rules['faqs.*.question']);
    }

    public function test_repeater_opcional(): void
    {
        $rules = RepeaterField::make('events', 'Momentos')->schema([])->toValidationRules();

        $this->assertSame(['nullable', 'array'], $rules['events']);
    }

    public function test_repeater_anidado_propaga_el_prefijo(): void
    {
        $rules = RepeaterField::make('faqs', 'FAQ')->schema([
            InlineTextareaField::make('content', 'Respuesta')->required(),
        ])->toValidationRules('config');

        $this->assertArrayHasKey('config.faqs.*.content', $rules);
    }

    /* ---------------------------------------------------------------------
     | Reglas de formato por tipo de control
     * -------------------------------------------------------------------*/

    public function test_url_field_valida_url_por_defecto(): void
    {
        $this->assertSame(['nullable', 'url:http,https'], UrlField::make('web', 'Web')->toValidationRules()['web']);
    }

    public function test_inline_textarea_permite_textos_largos(): void
    {
        $rules = InlineTextareaField::make('content', 'Respuesta')->required()->toValidationRules();

        $this->assertSame(['required', 'string', 'max:2000'], $rules['content']);
    }

    public function test_un_dato_declarado_para_dos_tipos_es_un_solo_campo(): void
    {
        // Así lo guarda el admin: "nombre de la tienda" declarado dos veces,
        // una por cada tipo de mesa. Es el mismo dato, no dos.
        $field = (new FormSchemaCompiler())->compileField([
            'key' => 'registries',
            'type' => 'repeater',
            'label' => 'Opciones de regalo',
            'schema' => [
                ['key' => 'type', 'type' => 'select', 'label' => 'Tipo'],
                ['key' => 'tienda', 'type' => 'text', 'label' => 'Tienda', 'is_required' => '1',
                 'depends_on' => ['field' => 'type', 'values' => ['liverpool']]],
                ['key' => 'tienda', 'type' => 'text', 'label' => 'Tienda', 'is_required' => '1',
                 'depends_on' => ['field' => 'type', 'values' => ['amazon']]],
            ],
        ]);

        // Un solo subcampo: si fueran dos, el formulario pintaría dos inputs
        // con el mismo name y el oculto le ganaría al que llenó el organizador.
        $nombres = array_map(fn ($sub) => $sub->getName(), $field->getSchema());
        $this->assertSame(['type', 'tienda'], $nombres);

        // Y aplica a los dos tipos, no sólo al último declarado.
        $this->assertSame(
            ['nullable', 'required_if:registries.*.type,liverpool,amazon'],
            $field->toValidationRules()['registries.*.tienda']
        );
    }

    public function test_schema_compilado_desde_bd_mantiene_las_reglas(): void
    {
        // Así llega un campo del admin de mesa de regalos (formato nuevo).
        $field = (new FormSchemaCompiler())->compileField([
            'key' => 'banco', 'type' => 'text', 'label' => 'Banco', 'is_required' => '1',
            'rules' => ['string', 'max:255'],
            'depends_on' => ['field' => 'type', 'values' => ['cuenta_bancaria']],
        ]);

        $this->assertSame(
            ['nullable', 'required_if:registries.*.type,cuenta_bancaria', 'string', 'max:255'],
            $field->toValidationRules('registries.*')['registries.*.banco']
        );
    }

    public function test_opciones_en_lista_conservan_el_orden_de_creacion(): void
    {
        // Formato del admin de mesa de regalos: lista, porque MySQL reordena objetos JSON.
        $field = (new FormSchemaCompiler())->compileField([
            'key' => 'type', 'type' => 'select', 'label' => 'Tipo de mesa',
            'options' => [
                ['value' => 'cuenta_bancaria', 'label' => 'Cuenta bancaria'],
                ['value' => 'amazon', 'label' => 'Amazon'],
                ['value' => 'liverpool', 'label' => 'Liverpool'],
            ],
        ]);

        $this->assertSame(
            ['cuenta_bancaria' => 'Cuenta bancaria', 'amazon' => 'Amazon', 'liverpool' => 'Liverpool'],
            $field->getOptions()
        );
    }

    public function test_opciones_en_formato_legacy_siguen_funcionando(): void
    {
        $field = (new FormSchemaCompiler())->compileField([
            'key' => 'type', 'type' => 'select', 'label' => 'Tipo de mesa',
            'options' => ['amazon' => 'Amazon', 'liverpool' => 'Liverpool'],
        ]);

        $this->assertSame(['amazon' => 'Amazon', 'liverpool' => 'Liverpool'], $field->getOptions());
    }

    /* ---------------------------------------------------------------------
     | isFilled: base del porcentaje de avance
     * -------------------------------------------------------------------*/

    public function test_is_filled_campo_simple(): void
    {
        $field = TextField::make('x', 'X');

        $this->assertFalse($field->isFilled(null));
        $this->assertFalse($field->isFilled('   '));
        $this->assertTrue($field->isFilled('hola'));
        $this->assertTrue($field->isFilled(false), 'Un booleano en false es una respuesta.');
        $this->assertTrue($field->isFilled('0'));
    }

    public function test_is_filled_repeater(): void
    {
        $field = RepeaterField::make('faqs', 'FAQ');

        $this->assertFalse($field->isFilled([]));
        $this->assertFalse($field->isFilled([['question' => '', 'content' => null]]));
        $this->assertTrue($field->isFilled([['question' => '¿Niños?', 'content' => null]]));
    }

    public function test_is_filled_imagen(): void
    {
        $field = ImageUploadField::make('reference_image', 'Imagen');

        $this->assertFalse($field->isFilled(['uuid' => null, 'url' => '']));
        $this->assertTrue($field->isFilled(['uuid' => 'abc', 'url' => null]));
        $this->assertTrue($field->isFilled(['uuid' => null, 'url' => 'https://x/y.jpg']));
    }

    /* ------------------------------------------------------------------ */

    private function registriesRepeater(): RepeaterField
    {
        return RepeaterField::make('registries', 'Mesas')->schema([
            SelectField::make('type', 'Tipo')->required(),
            UrlField::make('lista', 'Lista')->required()->rules(['url', 'max:255'])->dependsOn('type', ['amazon']),
            TextField::make('banco', 'Banco')->required()->rules(['string', 'max:255'])->dependsOn('type', ['cuenta_bancaria']),
            TextField::make('numero_de_mesa', 'Mesa')->required()->rules(['numeric'])->dependsOn('type', ['liverpool']),
        ]);
    }
}
