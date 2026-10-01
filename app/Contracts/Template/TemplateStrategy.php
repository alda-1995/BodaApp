<?php
namespace App\Contracts\Template;

interface TemplateStrategy
{
    public function getName(): string;

    /**
     * Campos que el cliente ve y edita para personalizar su evento.
     * Retorna una estructura descriptiva para generar el formulario dinámicamente.
     */
    public function getClientFields(): array;

    /**
     * Campos dinámicos adicionales que SOLO el administrador puede configurar
     * al crear o actualizar la plantilla (ej: variables CSS, assets base, etc.).
     */
    public function getAdminFields(): array;

    /**
     * Reglas de validación combinadas (cliente + admin).
     */
    public function getValidationRules(string $role = 'client'): array;

    /**
     * Nombres amigables de los campos para los mensajes de error.
     */
    public function getValidationAttributes(string $role = 'client'): array;

    /**
     * Retorna la estructura de pasos (Wizard) para la edición del cliente.
     */
    public function getClientWizardSteps(): array;

    /**
     * Hojas de fuentes web propias de la plantilla (Google Fonts u otras).
     *
     * @return array<int, string>
     */
    public function fonts(): array;

    /**
     * Mapeo de campos del formulario que corresponden a columnas nativas de la tabla events.
     * @return array<string, string>
     */
    public function getModelAttributesMap(): array;
}