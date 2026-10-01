<?php

namespace App\Services;

use App\DTOs\SystemSection\SystemSectionDTO;
use App\Models\SystemSection;
use Exception;
use Illuminate\Support\Facades\Log;


class SystemSectionService
{
    public function findByKey(string $key): ?SystemSection
    {
        return SystemSection::where('key', $key)
            ->first();
    }

    public function updateConfig(SystemSectionDTO $dto): SystemSection
    {
        try {
            return SystemSection::updateOrCreate(
                [
                    'key' => $dto->key,
                ],
                [
                    'title' => $dto->title,
                    'order' => $dto->order,
                    'is_global' => $dto->isGlobal,
                    'is_active' => $dto->isActive,
                    'schema' => $dto->schema,
                    'parent' => $dto->parent,
                    'type' => $dto->type
                ]
            );
        } catch (Exception $e) {
            Log::error(
                'Error al actualizar la configuración de mesas de regalo: ' . $e->getMessage(),
                [
                    'dto' => (array) $dto,
                ]
            );

            throw new Exception('No se pudo guardar la configuración de tipos de mesa.');
        }
    }
}