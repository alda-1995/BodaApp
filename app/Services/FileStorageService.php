<?php

namespace App\Services;

use App\Models\AppFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Exception;

class FileStorageService
{
    /**
     * Obtiene todos los archivos asociados a una sección específica de un modelo,
     * indexados por su 'field_name' (dot-notation).
     */
    public function getFilesBySection(Model $model, string $stepKey): Collection
    {
        return $model->files()
            ->where('section', $stepKey)
            ->get()
            ->keyBy('uuid');
    }

    /**
     * Busca un registro de archivo por su UUID.
     */
    public function findByUuid(string $uuid): ?AppFile
    {
        return AppFile::where('uuid', $uuid)->first();
    }

    /**
     * Verifica si existe un archivo en BD registrado con el UUID especificado.
     */
    public function existFileByUuid(string $uuid): bool
    {
        return AppFile::where('uuid', $uuid)->exists();
    }

    /**
     * Guarda un nuevo archivo físico en Storage y crea su registro correspondiente en BD.
     */
    public function store(
        Model $model,
        UploadedFile $file,
        string $section,
        string $fieldName,
        string $disk = 'public'
    ): AppFile {
        if (!$file->isValid()) {
            throw new Exception("El archivo enviado para '{$fieldName}' no es válido.");
        }

        $directory = $this->buildDirectoryPath($model, $section);
        $path = $file->store($directory, $disk);

        if (!$path) {
            throw new Exception("Error al guardar el archivo '{$fieldName}' en el almacenamiento.");
        }

        $mimeType = $file->getClientMimeType() ?? $file->getMimeType();

        return $model->files()->create([
            'section' => $section,
            'field_name' => $fieldName,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'disk' => $disk,
            'mime_type' => $mimeType,
            'file_type' => AppFile::detectFileType($mimeType),
            'file_size' => $file->getSize(),
        ]);
    }

    /**
     * Reemplaza un archivo existente: borra el archivo físico anterior de Storage,
     * elimina su registro previo en BD y almacena el nuevo.
     */
    public function replace(
        Model $model,
        UploadedFile $newFile,
        AppFile $existingFile,
        string $section,
        string $fieldName,
        string $disk = 'public'
    ): AppFile {
        // 1. Elimina el archivo anterior (físico y registro BD)
        $this->deleteFile($existingFile);

        // 2. Registra y almacena el nuevo archivo
        return $this->store($model, $newFile, $section, $fieldName, $disk);
    }

    /**
     * Elimina el archivo físico del disco Storage y borra el registro de la base de datos.
     */
    public function deleteFile(AppFile $file): void
    {
        if (Storage::disk($file->disk)->exists($file->file_path)) {
            Storage::disk($file->disk)->delete($file->file_path);
        }

        $file->delete();
    }

    /**
     * Elimina un listado de rutas de archivos en Storage.
     * Útil para ejecutar rollback físico dentro del bloque catch en caso de error.
     */
    public function cleanupFiles(array $filePaths, string $disk = 'public'): void
    {
        foreach ($filePaths as $filePath) {
            if (is_string($filePath) && Storage::disk($disk)->exists($filePath)) {
                Storage::disk($disk)->delete($filePath);
            }
        }
    }

    /**
     * Encapsula la regla de negocio para construir las rutas de los directorios en Storage.
     *
     * La carpeta sale de la tabla del modelo: para un evento da 'events/…', que
     * es donde ya están los archivos de siempre, y para cualquier otro modelo
     * con archivos —una plantilla, por ejemplo— da la suya sin pisarlos.
     */
    private function buildDirectoryPath(Model $model, string $section): string
    {
        return "{$model->getTable()}/{$model->id}/{$section}";
    }
}