<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\EventSaveStepRequest;
use App\Models\Event;
use App\Services\EventWizardService;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventSetupController extends Controller
{
    public function __construct(
        protected EventWizardService $wizardService
    ) {}

    public function edit(Request $request, Event $event, ?string $step = null): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($event->isManagedBy($user), 403);

        // Vencida: el dueño ve la invitación a comprar otra; un coadministrador
        // regresa a su lista de invitaciones compartidas.
        if (!$event->isAvailable()) {
            return $event->isOwnedBy($user)
                ? redirect()->route('events.info')
                : redirect()->route('shared-events.index')
                    ->with('error', 'Esa invitación ya venció y no se puede editar.');
        }

        $this->authorize('update', $event);

        if (is_null($step) || $step === '') {
            $lastStep = $this->wizardService->resolveCurrentStep($event);

            return redirect()->route('events.wizard.edit', [
                'event' => $event->slug,
                'step'  => $lastStep,
            ]);
        }

        $context = $this->wizardService->getStepContext($event, $step);

        // dd($context);

        return view('events.config-step', $context);
    }

    public function update(EventSaveStepRequest $request, Event $event, string $step): RedirectResponse
    {
        abort_unless($this->wizardService->hasStep($event, $step), 404);

        try {
            // Guarda los datos validados y procesa archivos polimórficos
            $this->wizardService->saveStep($event, $step, $request->validated());

            // Resolver cuál es el siguiente paso a mostrar
            $nextStep = $request->input('next_step') ?? $this->wizardService->getNextStepKey($event, $step);

            return redirect()
                ->route('events.wizard.edit', ['event' => $event->slug, 'step' => $nextStep])
                ->with('success', 'Cambios guardados correctamente.');

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}