<?php

namespace App\Http\Controllers\Template;

use App\DTOs\Template\CreateTemplateDTO;
use App\DTOs\Template\UpdateTemplateDTO;
use App\Exceptions\TemplateImageException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Template\StoreTemplateRequest;
use App\Http\Requests\Template\UpdateTemplateRequest;
use App\Services\Template\TemplateDiscoveryService;
use App\Services\TemplateService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Exception;

class TemplateController extends Controller
{
    public function __construct(
        protected TemplateService $templateService,
        protected TemplateDiscoveryService $discoveryService
    ) {}

    public function index(): View
    {
        $templates = $this->templateService->getPaginated();
        // dd($templates);
        return view('templates.index', compact('templates'));
    }

    public function showBySlug(string $slug): View
    {
        $template = $this->templateService->findBySlug($slug);
        
        if (!$template) {
            abort(404, 'Plantilla no encontrada');
        }
        return view('templates.show', compact('template'));
    }

    public function create(): View
    {
        $availableViews = $this->discoveryService->getAvailableViews();
        return view('templates.create', compact('availableViews'));
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $dto = new CreateTemplateDTO(
            name: $data['name'],
            price: (float) $data['price'],
            viewPath: $data['view_path'],
            isActive: $data['is_active'] ?? true,
            durationDays: $data['duration_days'] ?? null,
            previewImage: $request->file('preview_image'),
            description: $data['description'] ?? null
        );

        try {
            $this->templateService->create($dto);

            return redirect()->route('templates.index')
                ->with('success', 'Plantilla creada correctamente y sincronizada con Stripe.');

        } catch (TemplateImageException $e) {
            // La plantilla sí se creó: se va a la lista y se avisa de la imagen,
            // en vez de devolverlo al formulario como si nada se hubiera guardado.
            return redirect()->route('templates.index')
                ->with('warning', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function edit(int $id): View
    {
        $template = $this->templateService->find($id);
        
        if (!$template) {
            abort(404, 'Plantilla no encontrada');
        }

        $availableViews = $this->discoveryService->getAvailableViews();
        
        $strategy = $this->discoveryService->resolveStrategy($template->view_path);

        $adminFieldsGroups = $strategy->getAdminFields();

        return view('templates.edit', compact('template', 'availableViews', 'adminFieldsGroups'));
    }

    public function update(UpdateTemplateRequest $request, int $id): RedirectResponse
    {
        $data = $request->validated();
        $dto = new UpdateTemplateDTO(
            name: $data['name'],
            price: (float) $data['price'],
            viewPath: $data['view_path'],
            isActive: $data['is_active'] ?? true,
            adminFields: $data['admin_fields'] ?? [], // Pasamos los valores dinámicos
            durationDays: $data['duration_days'] ?? null,
            previewImage: $request->file('preview_image'),
            description: $data['description'] ?? null,
            // El control de imagen reenvía la URL de la que ya había; vacía
            // significa que la quitaron con el bote de basura.
            keepPreviewImage: filled($data['preview_image_url'] ?? null)
        );

        try {
            $this->templateService->update($id, $dto);

            return redirect()->route('templates.index')
                ->with('success', 'Plantilla actualizada con éxito en el catálogo y Stripe.');

        } catch (TemplateImageException $e) {
            // El resto de los campos ya se guardó: se avisa sólo de la imagen.
            return redirect()->route('templates.index')
                ->with('warning', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->templateService->delete($id);

            return redirect()->route('templates.index')
                ->with('success', 'La plantilla fue archivada y dada de baja en Stripe correctamente.');

        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $updated = $this->templateService->toggleStatus($id);

        if (!$updated) {
            return redirect()->back()->with('error', 'No se pudo modificar el estado.');
        }

        return redirect()->back()->with('success', 'Estado modificado con éxito.');
    }
}