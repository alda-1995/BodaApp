<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\DTOs\ColorPalette\CreateColorPaletteDTO;
use App\DTOs\ColorPalette\UpdateColorPaletteDTO;
use App\Http\Requests\ColorPalette\StoreColorPaletteRequest;
use App\Http\Requests\ColorPalette\UpdateColorPaletteRequest;
use App\Services\ColorPaletteService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class ColorPaletteController extends Controller
{
    public function __construct(
        protected ColorPaletteService $paletteService
    ) {
    }

    public function index(): View
    {
        $palettes = $this->paletteService->getAll();
        return view('admin.color-palettes.index', compact('palettes'));
    }

    public function store(StoreColorPaletteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $dto = new CreateColorPaletteDTO(
            name: $data['name'],
            primaryColor: $data['primary_color'],
            secondaryColor: $data['secondary_color'],
            accentColor: $data['accent_color'],
            isActive: true
        );

        try {
            $this->paletteService->create($dto);

            return redirect()->route('color-palettes.index')
                ->with('success', 'Paleta creada con éxito.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function update(UpdateColorPaletteRequest $request, int $id): RedirectResponse
    {
        $data = $request->validated();

        $dto = new UpdateColorPaletteDTO(
            name: $data['name'],
            primaryColor: $data['primary_color'],
            secondaryColor: $data['secondary_color'],
            accentColor: $data['accent_color'],
            isActive: true
        );

        try {
            $this->paletteService->update($id, $dto);

            return redirect()->route('color-palettes.index')
                ->with('success', 'Paleta actualizada correctamente.');
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
            $this->paletteService->delete($id);

            return redirect()->route('color-palettes.index')
                ->with('success', 'Paleta eliminada con éxito.');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
