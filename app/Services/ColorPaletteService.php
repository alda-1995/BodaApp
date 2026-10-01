<?php

namespace App\Services;

use App\DTOs\ColorPalette\CreateColorPaletteDTO;
use App\DTOs\ColorPalette\UpdateColorPaletteDTO;
use App\Models\ColorPalette;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ColorPaletteService
{
    public function getAll(): Collection
    {
        return ColorPalette::orderBy('id', 'asc')->get();
    }

    public function find(int $id): ?ColorPalette
    {
        return ColorPalette::find($id);
    }

    public function create(CreateColorPaletteDTO $dto): ColorPalette
    {
        try {
            return ColorPalette::create([
                'name' => $dto->name,
                'primary_color' => $dto->primaryColor,
                'secondary_color' => $dto->secondaryColor,
                'accent_color' => $dto->accentColor,
                'is_active' => $dto->isActive,
            ]);
        } catch (Exception $e) {
            Log::error('Error al crear paleta de color: ' . $e->getMessage(), [
                'dto' => (array) $dto
            ]);
            throw new Exception('No se pudo registrar la paleta de color.');
        }
    }

    public function update(int $id, UpdateColorPaletteDTO $dto): ColorPalette
    {
        try {
            $palette = ColorPalette::find($id);
            if (!$palette) {
                throw new Exception('La paleta de color solicitada no existe.');
            }

            $palette->update([
                'name' => $dto->name,
                'primary_color' => $dto->primaryColor,
                'secondary_color' => $dto->secondaryColor,
                'accent_color' => $dto->accentColor,
                'is_active' => $dto->isActive,
            ]);

            return $palette;
        } catch (Exception $e) {
            Log::error('Error al actualizar paleta de color ID ' . $id . ': ' . $e->getMessage(), [
                'dto' => (array) $dto
            ]);
            throw new Exception('No se pudieron actualizar los datos de la paleta.');
        }
    }

    public function delete(int $id): bool
    {
        try {
            $palette = ColorPalette::find($id);
            if (!$palette) {
                throw new Exception('La paleta de color no existe.');
            }

            return (bool) $palette->delete();
        } catch (Exception $e) {
            Log::error('Error al eliminar paleta de color ID ' . $id . ': ' . $e->getMessage());
            throw new Exception('Ocurrió un error al eliminar la paleta de color.');
        }
    }
}