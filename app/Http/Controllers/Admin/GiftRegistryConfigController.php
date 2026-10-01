<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\SystemSection\SystemSectionDTO;
use App\FormBuilder\FormSchemaCompiler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\UpdateGiftRegistrySectionRequest;
use App\Services\SystemSectionService;
use Illuminate\Http\Request;
use Exception;

class GiftRegistryConfigController extends Controller
{
    public function __construct(
        protected SystemSectionService $systemSectionService
    ) {
    }

    public function edit()
    {
        $section = $this->systemSectionService->findByKey('registries');
        // dd($section);
        $fieldsBuild = new FormSchemaCompiler($section);
        $fieldsMap = $fieldsBuild->build();
        // dd($fieldsMap);


        return view('admin.gift-registry.edit', compact('section'));
    }

    public function update(UpdateGiftRegistrySectionRequest $request)
    {
        try {
            // dd($request->all());
            // dd($request->validated());
            $dto = SystemSectionDTO::fromRequest($request);
            // dd($dto);
            $this->systemSectionService->updateConfig($dto);
            return redirect()->back()->with('success', 'Configuración de tipos de mesa actualizada correctamente.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
