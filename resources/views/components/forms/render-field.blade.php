@props([
    'field'      => null,
    'name'   => '',
    'value'      => null,
    'isRepeater' => false,
    'prefix'     => null,
    // Valores para los marcadores del texto de ayuda, ej. [':days' => 21].
    'helpReplacements' => [],
])

@php
    $isObject = is_object($field);

    $type = $isObject ? $field->getType() : ($field['type'] ?? 'text');
    $normalizedType = str_replace(['field', '_field'], '', strtolower($type));

    $label = $isObject ? $field->getLabel() : ($field['label'] ?? '');
    $help = $isObject ? $field->getHelp() : ($field['help'] ?? null);
    $placeholder = $isObject ? ($field->getPlaceholder() ?? '') : ($field['placeholder'] ?? '');
    $required = $isObject ? $field->isRequired() : !empty($field['required']);
    
    $options = [];
    if (in_array($normalizedType, ['select', 'dropdown'])) {
        $options = $isObject ? $field->getOptions() : ($field['options'] ?? []);
    }

    // Extracción de props para ImageUploadField
    $maxSize = 5120;
    $allowedMimes = ['jpg', 'jpeg', 'png', 'webp'];
    if ($normalizedType === 'image') {
        if ($isObject) {
            $arrayData = $field->toArray();
            $maxSize = $arrayData['max_size'] ?? 5120;
            $allowedMimes = $arrayData['allowed_mimes'] ?? $allowedMimes;
        } else {
            $maxSize = $field['max_size'] ?? 5120;
            $allowedMimes = $field['allowed_mimes'] ?? $allowedMimes;
        }
    }

    $defaultValue = $isObject ? $field->getDefault() : ($field['default'] ?? null);
    $finalValue   = $value ?? $defaultValue;

    // Dependencias condicionales (dependsOn)
    $dependsOn = null;
    if ($isObject && method_exists($field, 'getDependsOn')) {
        $dependsOn = $field->getDependsOn();
    } elseif (is_array($field) && isset($field['dependsOn'])) {
        $dependsOn = $field['dependsOn'];
    }

    $dependsOnField  = $dependsOn['field'] ?? null;
    $dependsOnValues = isset($dependsOn['values']) ? (array) $dependsOn['values'] : [];
    $hasDependency   = !empty($dependsOnField) && !empty($dependsOnValues);
    $jsValuesArray   = json_encode(array_map('strval', array_values($dependsOnValues)));

    // Nombre en notación de puntos: old()/@error usan 'registries.0.type', no 'registries[0][type]'
    $dotName = trim(str_replace(['[', ']'], ['.', ''], $name), '.');

    // Dentro de un repeater, updateField debe recibir sólo la subclave ('type'),
    // no el name completo del input.
    $repeaterKey = $name;
    if (preg_match('/\[([^\]]+)\]$/', $name, $matches)) {
        $repeaterKey = $matches[1];
    }

    // Expresión Alpine que decide si el campo es visible según su dependencia.
    $visibleExpr = "typeof item !== 'undefined' && {$jsValuesArray}.includes(String(item['{$dependsOnField}'] ?? ''))";

    /*
    | La expresión de arriba mira 'item', que es la fila de un repetidor, así
    | que sólo sirve para un campo que depende de otro de SU MISMA fila.
    |
    | Un campo de primer nivel no tiene fila: depende de otro campo del paso, y
    | ése vive suelto en el formulario. Para esos se mira el control hermano.
    */
    $dependeDeOtroCampoDelPaso = $hasDependency && !$isRepeater;
@endphp

{{--
    'campo-wizard' es lo que separa un campo del siguiente. El espacio vive ahí
    y no dentro de cada control: antes lo ponía quien lo trajera —el párrafo de
    ayuda, la raíz del repetidor, la caja de la imagen— y un campo sin ayuda
    quedaba pegado al de abajo.
--}}
<div
    class="flex flex-col campo-wizard"
    @if($dependeDeOtroCampoDelPaso)
        {{--
            Depende de otro campo del paso. Se lee el control hermano dentro del
            mismo formulario y se escucha su cambio, para que aparezca y
            desaparezca sin recargar.

            El selector pide el checkbox a propósito: los interruptores llevan
            además un <input type="hidden"> con el mismo nombre, para que al
            desmarcarlos viaje un 0, y ése es el que saldría primero.
        --}}
        {{--
            Va en el init() de x-data y no en x-init: Alpine sólo trata un
            x-init como cuerpo de función si la expresión EMPIEZA con 'const',
            y aquí empezaría con el salto de línea de la indentación, así que lo
            descartaba sin avisar y el campo se quedaba siempre visible.
        --}}
        x-data="{
            aplica: true,
            init() {
                const origen = this.$el.closest('form')?.querySelector(
                    'input[type=checkbox][name=&quot;{{ $dependsOnField }}&quot;], select[name=&quot;{{ $dependsOnField }}&quot;], input[type=radio][name=&quot;{{ $dependsOnField }}&quot;]:checked'
                );

                if (!origen) return;

                const leer = () => {
                    const valor = origen.type === 'checkbox' ? (origen.checked ? origen.value : '0') : origen.value;
                    this.aplica = {{ $jsValuesArray }}.includes(String(valor));
                };

                leer();
                origen.addEventListener('change', leer);
            }
        }"
        x-show="aplica"
    @elseif($hasDependency)
        x-show="{{ $visibleExpr }}"
        {{--
            x-show sólo esconde: el campo sigue en el DOM y seguiría viajando en
            la petición. Por eso aquí se apaga de verdad.

            - required: un campo oculto obligatorio bloquea el submit nativo.
            - disabled: un campo que no aplica a este tipo no debe enviarse. Si
              se envía se guarda vacío y, cuando dos tipos comparten el nombre
              de un dato, el oculto le gana al que sí llenaron.
        --}}
        x-effect="$el.querySelectorAll('input, select, textarea').forEach(el => {
            const aplica = {{ $visibleExpr }};
            if (el.dataset.baseRequired === undefined) el.dataset.baseRequired = el.required ? '1' : '0';
            el.required = el.dataset.baseRequired === '1' && aplica;
            el.disabled = !aplica;
        })"
        x-cloak
    @endif
    @if($isRepeater)
        @change="updateField('{{ $repeaterKey }}', $event.target.value)"
        @input="updateField('{{ $repeaterKey }}', $event.target.value)"
    @endif
>
    @switch($normalizedType)
        @case('image')
            @php
                $imageUuid = is_array($finalValue) ? ($finalValue['uuid'] ?? null) : $finalValue;
                $imageUrl  = is_array($finalValue) ? ($finalValue['url'] ?? '') : (is_string($finalValue) ? $finalValue : '');
            @endphp
            <x-controls.inputs.image-upload 
                :dot-name="$dotName"
                :label="$label"
                :name="$name"
                :uuid="old($dotName . '_uuid', $imageUuid)"
                :value="old($dotName . '_url', $imageUrl)"
                :required="$required"
                :max-size="$maxSize"
                :allowed-mimes="$allowedMimes"
            />
            @break

        @case('inline-text')
        @case('inline-textarea')
            <x-controls.inputs.inline-edit
                :dot-name="$dotName"
                :label="$label"
                :name="$name"
                :item-key="$repeaterKey"
                :placeholder="$placeholder"
                :required="$required"
                :multiline="$normalizedType === 'inline-textarea'"
            />
            @break

        @case('repeater')
            <x-forms.repeater 
                :field="$field" 
                :name="$name" 
                :label="$label" 
                :value="$finalValue"
            />
            @break
        @case('select')
            <x-controls.selects.select 
                :dot-name="$dotName"
                :label="$label"
                :name="$name"
                :options="$options"
                :selected="old($dotName, $finalValue)"
                :placeholder="$placeholder"
                :required="$required"
                class="w-full"
            />
            @break

        @case('textarea')
            <x-controls.inputs.textarea 
                :dot-name="$dotName"
                :label="$label"
                :name="$name"
                :value="old($dotName, $finalValue)"
                :placeholder="$placeholder"
                :required="$required"
                class="w-full"
            />
            @break

        @case('boolean')
        @case('checkbox')
            <x-controls.check-label
                :name="$name" 
                :label="$label"
                :checked="$finalValue"
                class="w-full justify-between"
            />
            @break

        @case('color')
            <x-controls.inputs.input-color 
                :dot-name="$dotName"
                :label="$label"
                :name="$name"
                :value="old($dotName, $finalValue ?? '#000000')"
                :required="$required"
                class="w-full"
            />
            @break

        @case('number')
            <x-controls.inputs.input-number 
                :dot-name="$dotName"
                :label="$label"
                :name="$name"
                :value="old($dotName, $finalValue)"
                :placeholder="$placeholder"
                :required="$required"
                :min="$isObject ? $field->getMin() : ($field['min'] ?? null)"
                :max="$isObject ? $field->getMax() : ($field['max'] ?? null)"
                :step="$isObject ? $field->getStep() : ($field['step'] ?? 'any')"
                class="w-full"
            />
            @break

        @case('datetime')
            <x-controls.inputs.flatpickr-date-time
                :label="$label"
                :name="$name"
                :value="old($dotName, $finalValue)"
                :placeholder="$placeholder"
                :required="$required"
                class="w-full"
            />
            @break

        @default
            <x-controls.inputs.input
                :type="$normalizedType"
                :label="$label"
                :name="$name"
                :placeholder="$placeholder"
                :value="old($dotName, $finalValue)"
                :required="$required"
                class="w-full"
            />
            @break
    @endswitch

    @if(filled($help))
        <p class="-mt-2 mb-4 font-inter text-size-small-heading text-[#8C8C8C]">{{ strtr($help, $helpReplacements) }}</p>
    @endif
</div>