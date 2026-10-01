<?php

namespace App\FormBuilder;

use App\Contracts\ValidatableFieldInterface;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

abstract class Field implements Arrayable, JsonSerializable, ValidatableFieldInterface
{
    protected string $name;
    protected string $label;
    protected ?string $placeholder = null;
    protected mixed $defaultValue = null;
    protected bool $isRequired = false;
    protected array $options = [];
    protected array|string $customRules = [];
    protected ?array $condition = null;
    protected array $customMessages = [];
    protected ?string $help = null;

    protected ?string $parentOrder = null;

    public function __construct(string $name, string $label)
    {
        $this->name = $name;
        $this->label = $label;
    }

    public static function make(string $name, string $label): static
    {
        return new static($name, $label);
    }

    /**
     * Cada clase hija define su tipo (ej: 'text', 'select', 'color', 'number')
     */
    abstract protected function defineType(): string;

    /**
     * Define mensajes personalizados de validación para este campo
     * Ejemplo: ['required_if' => 'El :attribute es obligatorio para cuentas bancarias.']
     */
    public function messages(array $messages): static // 👈 2. Método fluente
    {
        $this->customMessages = $messages;
        return $this;
    }

    public function getCustomMessages(): array
    {
        return $this->customMessages;
    }

    /**
     * Mapea los mensajes al key de validación correspondiente
     */
    public function toValidationMessages(string $parentKey = ''): array
    {
        $fieldKey = $parentKey ? "{$parentKey}.{$this->name}" : $this->name;
        $mappedMessages = [];

        foreach ($this->customMessages as $rule => $message) {
            $mappedMessages["{$fieldKey}.{$rule}"] = $message;
        }

        return $mappedMessages;
    }

    public function getType(): string
    {
        return $this->defineType();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function required(bool $condition = true): static
    {
        $this->isRequired = $condition;
        return $this;
    }

    public function nullable(bool $condition = true): static
    {
        $this->isRequired = !$condition;
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->defaultValue = $value;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * Texto de ayuda bajo el campo. Admite marcadores (":days") que la vista
     * reemplaza con datos del evento (ver EventWizardService::getStepContext).
     */
    public function help(string $text): static
    {
        $this->help = $text;
        return $this;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function rules(array|string $rules): static
    {
        $this->customRules = $rules;

        // Un 'required' dentro de las reglas expresa la misma intención que ->required().
        // Se normaliza aquí para que la presencia tenga una única fuente de verdad.
        if (in_array('required', array_map(static::ruleName(...), static::toRuleList($rules)), true)) {
            $this->isRequired = true;
        }

        return $this;
    }

    /**
     * Define la condición para mostrar el campo dependiendo del valor de otro
     *
     * @param string $fieldKey Nombre del campo del cual depende (ej: 'type')
     * @param array|string|int|bool $values Valor(es) que activan la visibilidad (ej: ['bank_account'] o 'bank_account')
     */
    public function dependsOn(string $fieldKey, array|string|int|bool $values): static
    {
        $this->condition = [
            'field' => $fieldKey,
            'values' => (array) $values,
        ];

        return $this;
    }

    /**
     * Indica si el control dibuja por su cuenta el chrome de la fila (eliminar,
     * numeración). El repeater lo consulta para no duplicar sus propios botones.
     */
    public function rendersOwnRow(): bool
    {
        return false;
    }

    public function getCondition(): ?array
    {
        return $this->condition;
    }

    public function getDependsOn(): ?array
    {
        return $this->getCondition();
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    /**
     * Indica si un valor guardado cuenta como "respondido" para el progreso del
     * evento. Cada control puede redefinir qué significa estar vacío.
     */
    public function isFilled(mixed $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return !empty(array_filter($value, fn ($v) => $v !== null && $v !== ''));
        }

        // Booleanos, números, fechas: cualquier valor no nulo es una respuesta.
        return true;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getDefault(): mixed
    {
        return $this->defaultValue;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    /**
     * Reglas que expresan *presencia*. Nunca se copian tal cual desde $customRules:
     * se derivan de la intención del campo (isRequired + condition), porque sólo el
     * contenedor conoce el prefijo real ('registries.*') al que deben apuntar.
     */
    protected const PRESENCE_RULES = [
        'required', 'nullable', 'sometimes', 'filled', 'present',
        'required_if', 'required_if_accepted', 'required_if_declined', 'required_unless',
        'required_with', 'required_with_all', 'required_without', 'required_without_all',
    ];

    /**
     * Reglas de formato propias del control (nunca de presencia). Las clases hijas
     * las declaran para no depender de que quien define el campo las escriba.
     */
    protected function typeRules(): array
    {
        return [];
    }

    /** Acepta tanto 'string|max:255' como ['string', 'max:255']. */
    protected static function toRuleList(array|string $rules): array
    {
        return is_string($rules) ? explode('|', $rules) : $rules;
    }

    /** Separa el nombre de la regla de sus parámetros ('max:255' => 'max'). */
    protected static function ruleName(mixed $rule): string
    {
        return is_string($rule) ? strtolower(explode(':', $rule, 2)[0]) : '';
    }

    /**
     * Deriva las reglas de presencia a partir de la intención declarada del campo.
     *
     * Un campo condicional nunca es 'required' a secas: su obligatoriedad depende
     * del valor de un campo hermano, y ese hermano sólo puede nombrarse con el
     * prefijo que aporta el contenedor ($parentKey).
     */
    protected function presenceRules(string $parentKey = ''): array
    {
        if ($this->condition === null) {
            return [$this->isRequired ? 'required' : 'nullable'];
        }

        // Cuando la condición no se cumple el campo llega vacío, y el middleware
        // ConvertEmptyStringsToNull lo convierte en null. Sin 'nullable' las reglas
        // de formato se ejecutarían sobre ese null y fallarían aunque no aplique.
        // 'nullable' no anula a 'required_if': las reglas implícitas se evalúan igual.
        $rules = ['nullable'];

        if ($this->isRequired) {
            $sibling = $parentKey
                ? "{$parentKey}.{$this->condition['field']}"
                : $this->condition['field'];

            $rules[] = 'required_if:' . implode(',', array_merge([$sibling], $this->condition['values']));
        }

        return $rules;
    }

    /**
     * Sólo las reglas de formato definidas para este campo; si no hay, las del control.
     */
    protected function formatRules(): array
    {
        $rules = array_values(array_filter(
            static::toRuleList($this->customRules),
            fn ($rule) => !in_array(static::ruleName($rule), static::PRESENCE_RULES, true)
        ));

        return $rules ?: $this->typeRules();
    }

    public function getRules(string $parentKey = ''): array
    {
        return array_merge($this->presenceRules($parentKey), $this->formatRules());
    }

    /**
     * Implementación de ValidatableFieldInterface para campos simples.
     */
    public function toValidationRules(string $parentKey = ''): array
    {
        $key = $parentKey ? "{$parentKey}.{$this->name}" : $this->name;

        return [
            $key => $this->getRules($parentKey),
        ];
    }

    public function toValidationAttributes(string $parentKey = ''): array
    {
        $key = $parentKey ? "{$parentKey}.{$this->name}" : $this->name;

        return [
            $key => mb_strtolower($this->label),
        ];
    }

    /**
     * Retorna el array completo listo para Blade o API
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->getType(),
            'label' => $this->label,
            'placeholder' => $this->placeholder,
            'help' => $this->help,
            'required' => $this->isRequired,
            'default' => $this->defaultValue,
            'options' => $this->options,
            'rules' => $this->getRules(),
            'condition' => $this->condition,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}