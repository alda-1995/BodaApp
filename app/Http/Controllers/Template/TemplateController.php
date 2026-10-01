<?php

namespace App\Http\Controllers\Template;

use App\DTOs\Template\CreateTemplateDTO;
use App\DTOs\Template\UpdateTemplateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Template\StoreTemplateRequest;
use App\Http\Requests\Template\UpdateTemplateRequest;
use App\Services\Template\TemplateDiscoveryService;
use App\Services\TemplateService;
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
            durationDays: $data['duration_days'] ?? null
        );

        try {
            $this->templateService->create($dto);

            return redirect()->route('templates.index')
                ->with('success', 'Plantilla creada correctamente y sincronizada con Stripe.');

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
            durationDays: $data['duration_days'] ?? null
        );
        
        try {
            $this->templateService->update($id, $dto);

            return redirect()->route('templates.index')
                ->with('success', 'Plantilla actualizada con éxito en el catálogo y Stripe.');

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