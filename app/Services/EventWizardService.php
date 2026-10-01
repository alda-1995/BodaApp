<?php

namespace App\Services;

use App\FormBuilder\Controls\ImageUploadField;
use App\FormBuilder\Controls\RepeaterField;
use App\Models\Event;
use App\Services\Template\TemplateDiscoveryService;
use App\Support\MediaArrayHelper;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EventWizardService
{
    public function __construct(
        protected TemplateDiscoveryService $discoveryService,
        protected FileStorageService $fileStorageService
    ) {
    }

    /**
     * Resuelve cuál es el paso actual del evento basándose en lo guardado
     * o devuelve el primer paso disponible si es la primera vez.
     */
    public function resolveCurrentStep(Event $event): string
    {
        try {
            $strategy = $this->discoveryService->resolveStrategy($event->template->view_path);
            $steps = $strategy->getClientWizardSteps();

            if (empty($steps)) {
                throw new Exception("No se encontraron pasos configurados para la plantilla.");
            }

            $firstStepKey = (string) array_key_first($steps);
            $features = is_array($event->features) ? $event->features : [];
            $savedStep = $features['meta']['current_step'] ?? null;

            return ($savedStep && array_key_exists($savedStep, $steps))
                ? $savedStep
                : $firstStepKey;
        } catch (Exception $e) {
            Log::error("Error al resolver el paso actual del wizard [Event ID: {$event->id}]: " . $e->getMessage());
            return 'general';
        }
    }

    /**
     * Calcula la clave del siguiente paso disponible en la plantilla.
     */
    public function getNextStepKey(Event $event, string $currentStepKey): string
    {
        try {
            $strategy = $this->discoveryService->resolveStrategy($event->template->view_path);
            $steps = $strategy->getClientWizardSteps();

            $keys = array_keys($steps);
            $currentIndex = array_search($currentStepKey, $keys, true);

            if ($currentIndex !== false && isset($keys[$currentIndex + 1])) {
                return $keys[$currentIndex + 1];
            }
        } catch (Exception $e) {
            Log::error("Error al obtener el siguiente paso [Event ID: {$event->id}]: " . $e->getMessage());
        }

        return $currentStepKey;
    }

    /**
     * Resuelve los datos necesarios para renderizar el paso activo.
     */
    public function getStepContext(Event $event, string $currentStepKey = 'general'): array
    {
        $strategy = $this->discoveryService->resolveStrategy($event->template->view_path);
        $steps = $strategy->getClientWizardSteps();
        if (!array_key_exists($currentStepKey, $steps)) {
            $currentStepKey = (string) array_key_first($steps);
        }

        $activeStep = $steps[$currentStepKey] ?? [];
        $savedValues = $this->resolveSavedValues($event, $strategy, $currentStepKey);

        $stepKeys = array_keys($steps);
        $currentStepIndex = array_search($currentStepKey, $stepKeys, true);
        $currentStepIndex = $currentStepIndex !== false ? $currentStepIndex + 1 : 1;

        return [
            'event' => $event,
            'steps' => $steps,
            'currentStepKey' => $currentStepKey,
            'currentStepIndex' => $currentStepIndex,
            'activeStep' => $activeStep,
            'savedValues' => $savedValues,
            'nextStepKey' => $this->getNextStepKey($event, $currentStepKey),
            'progress' => $this->getProgress($event),
            // Marcadores de los textos de ayuda (vigencia de la invitación).
            'helpReplacements' => [
                ':days' => $event->durationDays(),
                ':until' => $event->expires_at ? ' (hasta el ' . $event->expires_at->format('d/m/Y') . ')' : '',
            ],
        ];
    }

    /**
     * Reúne los valores guardados de un paso desde sus tres orígenes: el JSON
     * 'features', las columnas nativas del evento y los archivos de app_files.
     *
     * @param \Illuminate\Support\Collection|null $files Archivos del evento ya
     *        cargados (para no consultar la BD una vez por paso).
     */
    public function resolveSavedValues(Event $event, $strategy, string $stepKey, $files = null): array
    {
        // 1. Datos guardados en la columna JSON 'features' para este paso
        $savedValues = (array) ($event->getFeature($stepKey) ?? []);

        // 2. Columnas nativas del modelo que corresponden a este paso
        foreach ($strategy->getModelAttributesMap() as $inputDotKey => $modelColumn) {
            if (!str_contains($inputDotKey, '.')) {
                continue;
            }
            [$group, $fieldKey] = explode('.', $inputDotKey, 2);

            if ($group === $stepKey) {
                // Si no existe en el JSON o viene nulo, tomar el valor de la columna nativa
                $savedValues[$fieldKey] = $savedValues[$fieldKey] ?? $event->getAttribute($modelColumn);
            }
        }

        // 3. Archivos guardados en app_files para esta sección
        $stepFiles = $files
            ? $files->where('section', $stepKey)
            : $event->files()->where('section', $stepKey)->get();

        foreach ($stepFiles as $file) {
            Arr::set($savedValues, $file->field_name, [
                'uuid' => $file->uuid,
                'url' => $file->url ?? Storage::disk($file->disk)->url($file->file_path),
            ]);
        }

        return $savedValues;
    }

    /**
     * Calcula qué tanto ha completado el organizador la configuración del evento.
     *
     * Se deriva de los pasos de la estrategia, así que una sección nueva entra
     * sola al cálculo. Cada paso pesa lo mismo:
     *  - Con campos obligatorios: obligatorios llenos / total de obligatorios.
     *  - Sin obligatorios: completo en cuanto se guardó al menos una vez.
     *
     * @return array{percentage:int, next_step:?string, steps:array<string, array{title:string, filled:int, total:int, completed:bool}>}
     */
    public function getProgress(Event $event): array
    {
        $strategy = $this->discoveryService->resolveStrategy($event->template->view_path);
        $steps = $strategy->getClientWizardSteps();
        $features = is_array($event->features) ? $event->features : [];
        $files = $event->files()->get();

        $summary = [];
        $fractions = [];
        $nextStep = null;

        foreach ($steps as $stepKey => $step) {
            $values = $this->resolveSavedValues($event, $strategy, (string) $stepKey, $files);

            $required = array_filter(
                $step['fields'] ?? [],
                fn ($field) => $field instanceof \App\FormBuilder\Field && $field->isRequired()
            );

            if (empty($required)) {
                $total = 1;
                $filled = array_key_exists($stepKey, $features) ? 1 : 0;
            } else {
                $total = count($required);
                $filled = 0;
                foreach ($required as $fieldKey => $field) {
                    if ($field->isFilled($values[$fieldKey] ?? null)) {
                        $filled++;
                    }
                }
            }

            $completed = $filled >= $total;
            $fractions[] = $filled / $total;
            $nextStep ??= $completed ? null : (string) $stepKey;

            $summary[$stepKey] = [
                'title' => $step['title'] ?? (string) $stepKey,
                'filled' => $filled,
                'total' => $total,
                'completed' => $completed,
            ];
        }

        $percentage = $fractions ? (int) round(array_sum($fractions) / count($fractions) * 100) : 0;

        return [
            'percentage' => $percentage,
            'next_step' => $nextStep,
            'steps' => $summary,
        ];
    }

    /**
     * Guarda la información ya validada del paso actual.
     */
    public function saveStep(Event $event, string $stepKey, array $validatedData): void
    {
        // dd($validatedData);
        $uploadedFilesPaths = [];

        // Extraer medios y valores planos
        $mediaFiles = MediaArrayHelper::extractFilesAndMedia($validatedData);
        // dd($mediaFiles);
        //extraer valores de formulario no archivos
        $formValues = MediaArrayHelper::extractFormValues($validatedData);
        // dd($formValues);
        try {
            DB::transaction(function () use ($event, $stepKey, $formValues, $mediaFiles, &$uploadedFilesPaths) {

                // Procesar subidas, reemplazos y eliminación de omitidos
                $uploadedFilesPaths = $this->processStepFiles($event, $stepKey, $mediaFiles, $uploadedFilesPaths);

                //  Mapear datos a la entidad y guardar
                [$modelUpdates, $featuresData] = $this->extractModelAttributes($event, $stepKey, $formValues);
                $featuresData = $this->completeStepData($event, $stepKey, $featuresData);

                $currentFeatures = is_array($event->features) ? $event->features : [];
                $currentFeatures[$stepKey] = array_merge($currentFeatures[$stepKey] ?? [], $featuresData);
                $currentFeatures['meta']['current_step'] = $this->getNextStepKey($event, $stepKey);

                $event->fill($modelUpdates);
                $event->features = $currentFeatures;

                if (!$event->save()) {
                    throw new Exception("No se pudieron guardar los cambios en la base de datos.");
                }
            });

        } catch (Exception $e) {
            // Rollback de archivos físicos subidos si falla la transacción
            $this->fileStorageService->cleanupFiles($uploadedFilesPaths);

            Log::error("Error procesando wizard [Event ID: {$event->id}, Step: {$stepKey}]: {$e->getMessage()}", [
                'exception' => $e
            ]);

            throw new Exception("Ocurrió un problema al guardar la información. Por favor inténtalo de nuevo.");
        }
    }

    protected function processStepFiles(
        Event $event,
        string $stepKey,
        array $mediaFilesMap,
        array &$uploadedFilesPaths
    ): array {

        // Obtener archivos registrados en BD para esta sección indexados por uuid (dotPath)
        $existingFilesByUuid = $this->fileStorageService->getFilesBySection($event, $stepKey);
        // Colección de UUIDs que permanecen válidos en la petición
        $activeUuidsInRequest = [];

        foreach ($mediaFilesMap as $dotPath => $fileValue) {
            $urlKey = str_contains($dotPath, ']') ? preg_replace('/\]$/', '_url]', $dotPath) : "{$dotPath}_url";
            $uuidKey = str_contains($dotPath, ']') ? preg_replace('/\]$/', '_uuid]', $dotPath) : "{$dotPath}_uuid";
            // Obtener valores del request
            $fileUrl = request()->input($urlKey);
            $fileUuid = request()->input($uuidKey);

            $existingFile = $fileUuid ? $existingFilesByUuid->get($fileUuid) : null;

            if ($fileValue instanceof UploadedFile) {
                if ($existingFile) {
                    // Si traía un UUID previo, reemplazamos sobre ese registro
                    $appFile = $this->fileStorageService->replace(
                        model: $event,
                        newFile: $fileValue,
                        existingFile: $existingFile,
                        section: $stepKey,
                        fieldName: $dotPath
                    );
                } else {
                    // Si no hay UUID previo, es un archivo completamente nuevo
                    $appFile = $this->fileStorageService->store(
                        model: $event,
                        file: $fileValue,
                        section: $stepKey,
                        fieldName: $dotPath
                    );
                }

                $uploadedFilesPaths[] = $appFile->file_path;
                $activeUuidsInRequest[] = $appFile->uuid;

                continue;
            }

            if (!empty($fileUrl) && !empty($fileUuid)) {
                if ($existingFile && $existingFile->field_name !== $dotPath) {
                    $existingFile->update([
                        'field_name' => $dotPath
                    ]);
                }
                $activeUuidsInRequest[] = $fileUuid;

                continue;
            }
        }
        // Cualquier archivo en BD para esta sección cuyo UUID no esté en $activeUuidsInRequest
        // se elimina físicamente y de la base de datos.
        foreach ($existingFilesByUuid as $uuid => $existingFile) {
            if (!in_array($uuid, $activeUuidsInRequest, true)) {
                $this->fileStorageService->deleteFile($existingFile);
            }
        }

        return $uploadedFilesPaths;
    }

    /**
     * Indica si la plantilla del evento define el paso indicado.
     */
    public function hasStep(Event $event, string $stepKey): bool
    {
        $strategy = $this->discoveryService->resolveStrategy($event->template->view_path);

        return array_key_exists($stepKey, $strategy->getClientWizardSteps());
    }

    /**
     * Completa los datos del paso con los campos que el navegador no envió y
     * normaliza los repeaters antes de fusionarlos con lo guardado.
     *
     * - Un repeater sin filas no llega en la petición: sin esto, el array_merge
     *   con lo guardado resucitaría las filas viejas y sería imposible vaciarlo.
     * - Las filas se reindexan para guardarse como lista JSON y no como objeto
     *   ({"0":…,"2":…}) si llegan índices salteados.
     */
    protected function completeStepData(Event $event, string $stepKey, array $featuresData): array
    {
        $strategy = $this->discoveryService->resolveStrategy($event->template->view_path);
        $fields = $strategy->getClientWizardSteps()[$stepKey]['fields'] ?? [];

        // Campos de este paso que viven en columnas nativas del evento, no en features.
        $mappedKeys = [];
        foreach (array_keys($strategy->getModelAttributesMap()) as $inputDotKey) {
            if (str_starts_with($inputDotKey, $stepKey . '.')) {
                $mappedKeys[] = substr($inputDotKey, strlen($stepKey) + 1);
            }
        }

        foreach ($fields as $fieldKey => $field) {
            // Las imágenes se guardan en app_files, no en features.
            if ($field instanceof ImageUploadField || in_array($fieldKey, $mappedKeys, true)) {
                continue;
            }

            if ($field instanceof RepeaterField) {
                $featuresData[$fieldKey] = array_values((array) ($featuresData[$fieldKey] ?? []));
            } elseif (!array_key_exists($fieldKey, $featuresData)) {
                $featuresData[$fieldKey] = null;
            }
        }

        return $featuresData;
    }

    /**
     * Separa los atributos que se mapean a columnas nativas de la tabla `events`
     * de los que pertenecen únicamente al JSON `features`.
     */
    protected function extractModelAttributes(Event $event, string $stepKey, array $featuresData): array
    {
        $strategy = $this->discoveryService->resolveStrategy($event->template->view_path);
        $attributeMap = $strategy->getModelAttributesMap();
        $modelUpdates = [];

        foreach ($attributeMap as $inputDotKey => $modelColumn) {
            if (!str_contains($inputDotKey, '.')) {
                continue;
            }

            [$group, $fieldKey] = explode('.', $inputDotKey, 2);

            if ($group === $stepKey && array_key_exists($fieldKey, $featuresData)) {
                $modelUpdates[$modelColumn] = $featuresData[$fieldKey];
                unset($featuresData[$fieldKey]);
            }
        }

        return [$modelUpdates, $featuresData];
    }
}