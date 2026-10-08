<?php

namespace App\Services;

use App\DTOs\Template\CreateTemplateDTO;
use App\DTOs\Template\UpdateTemplateDTO;
use App\Models\Template;
use App\Exceptions\TemplateImageException;
use App\Services\FileStorageService;
use Exception;
use Throwable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Stripe\StripeClient;

class TemplateService
{
    protected StripeClient $stripe;

    public function __construct(StripeClient $stripe, private readonly FileStorageService $files)
    {
        $this->stripe = $stripe;
    }

    public function getAll(): Collection
    {
        return Template::all();
    }

    public function getAllActive(): Collection
    {
        return Template::where('is_active', true)->orderBy('name')->get();
    }

    public function getPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Template::orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getActivePaginated(int $perPage = 12): LengthAwarePaginator
    {
        return Template::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function find(int $id): ?Template
    {
        return Template::find($id);
    }

    public function findBySlug(string $slug): ?Template
    {
        return Template::where('slug', $slug)->where('is_active', true)->first();
    }

    public function searchByName(string $term): Collection
    {
        return Template::where('name', 'LIKE', '%' . $term . '%')
            ->where('is_active', true)
            ->limit(10)
            ->get();
    }

    public function create(CreateTemplateDTO $dto): Template
    {
        try {
            $stripeProduct = $this->stripe->products->create([
                'name' => $dto->name,
                'description' => $dto->name,
            ]);

            if (!isset($stripeProduct->id)) {
                throw new Exception('Stripe no devolvió un identificador de producto válido.');
            }

            $stripePrice = $this->stripe->prices->create([
                'product' => $stripeProduct->id,
                'unit_amount' => (int) ($dto->price * 100),
                'currency' => 'mxn'
            ]);

            if (!isset($stripePrice->id)) {
                throw new Exception('Stripe no devolvió un identificador de precio válido.');
            }

            $stripePriceId = $stripePrice->id;

            if (empty($stripePriceId)) {
                throw new Exception('No se pudo determinar el ID de precio para Stripe.');
            }

            $template = Template::create([
                'name' => $dto->name,
                'price' => $dto->price,
                'view_path' => $dto->viewPath,
                'description' => $dto->description,
                'stripe_price_id' => $stripePriceId,
                'is_active' => $dto->isActive,
                'duration_days' => $dto->durationDays,
            ]);

            $this->syncPreviewImage($template, $dto->previewImage, recienCreada: true);

            return $template;

        } catch (TemplateImageException $e) {
            // La plantilla ya existe: su mensaje lo dice y no debe convertirse
            // en el genérico de Stripe, que aquí sería falso.
            throw $e;
        } catch (Exception $e) {
            Log::error('Error en creación de plantilla: ' . $e->getMessage(), [
                'data' => (array) $dto,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("Error al sincronizar con Stripe");
        }
    }

    /**
     * @throws Exception
     */
    public function update(int $id, UpdateTemplateDTO $dto): ?Template
    {
        try {
            $template = Template::find($id);
            if (!$template) {
                throw new Exception('La plantilla no existe.');
            }

            $stripePriceId = $template->stripe_price_id;

            if ((float) $template->price !== (float) $dto->price) {
                if (!empty($template->stripe_price_id)) {
                    $this->stripe->prices->update($template->stripe_price_id, ['active' => false]);
                }

                $currentStripePrice = $this->stripe->prices->retrieve($template->stripe_price_id);
                $productId = $currentStripePrice->product;

                $newStripePrice = $this->stripe->prices->create([
                    'product' => $productId,
                    'unit_amount' => (int) ($dto->price * 100),
                    'currency' => 'mxn'
                ]);

                $stripePriceId = $newStripePrice->id;
            }

            $template->update([
                'name' => $dto->name,
                'price' => $dto->price,
                'is_active' => $dto->isActive,
                'stripe_price_id' => $stripePriceId,
                'view_path' => $dto->viewPath,
                'description' => $dto->description,
                'admin_fields' => $dto->adminFields,
                'duration_days' => $dto->durationDays,
            ]);

            $this->syncPreviewImage($template, $dto->previewImage, $dto->keepPreviewImage);

            return $template->fresh();

        } catch (TemplateImageException $e) {
            // Los demás campos ya se guardaron: su mensaje lo dice.
            throw $e;
        } catch (Exception $e) {
            Log::error('Error al actualizar plantilla con cambio de precio: ' . $e->getMessage(), [
                'id' => $id,
                'data' => (array) $dto,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("No se pudo actualizar la plantilla");
        }
    }

    /**
     * Deja la imagen de presentación como la quiso el superadmin.
     *
     * Tres caminos, y el formulario no distingue solo entre los dos últimos: por
     * eso llega $conservar, que sale de la URL que el control de imagen reenvía.
     *
     *  - Subió una: reemplaza a la anterior, archivo y registro.
     *  - No subió nada y conserva: no se toca.
     *  - No subió nada y la quitó con el bote: se borra.
     */
    private function syncPreviewImage(Template $template, ?UploadedFile $nueva, bool $conservar = true, bool $recienCreada = false): void
    {
        $actual = $template->previewImage();

        try {
            if ($nueva) {
                $actual
                    ? $this->files->replace($template, $nueva, $actual, Template::PRESENTATION_SECTION, Template::PREVIEW_IMAGE_FIELD)
                    : $this->files->store($template, $nueva, Template::PRESENTATION_SECTION, Template::PREVIEW_IMAGE_FIELD);

                return;
            }

            if (!$conservar && $actual) {
                $this->files->deleteFile($actual);
            }
        } catch (Throwable $e) {
            /*
             * El disco falló, no Stripe ni la base: el resto de la plantilla ya
             * quedó guardado. Se avisa de eso en concreto en vez de dar un error
             * genérico que haría pensar que no se guardó nada.
             */
            Log::error('Falló la imagen de presentación de una plantilla: ' . $e->getMessage(), [
                'template_id' => $template->id,
                'operacion' => $nueva ? ($actual ? 'reemplazar' : 'subir') : 'quitar',
                'trace' => $e->getTraceAsString(),
            ]);

            throw match (true) {
                $recienCreada => TemplateImageException::alCrear($e),
                $nueva !== null => TemplateImageException::alGuardar($e),
                default => TemplateImageException::alQuitar($e),
            };
        }
    }

    public function delete(int $id): bool
    {
        try {
            $template = Template::find($id);
            if (!$template) {
                throw new Exception('La plantilla no existe.');
            }

            if (!empty($template->stripe_price_id)) {
                $stripePrice = $this->stripe->prices->retrieve($template->stripe_price_id);
                $productId = $stripePrice->product;

                $this->stripe->prices->update($template->stripe_price_id, ['active' => false]);

                if (!empty($productId)) {
                    $this->stripe->products->update($productId, ['active' => false]);
                }
            }

            return (bool) $template->delete();

        } catch (Exception $e) {
            Log::error('Error al aplicar Soft Delete y desactivación en Stripe: ' . $e->getMessage(), [
                'template_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception("No se pudo procesar la eliminación de la plantilla.");
        }
    }

    public function toggleStatus(int $id): bool
    {
        $template = Template::find($id);
        if ($template) {
            return $template->update([
                'is_active' => !$template->is_active
            ]);
        }
        return false;
    }
}