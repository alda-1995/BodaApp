<?php

namespace App\Http\Requests\Event;

use App\Http\Requests\Concerns\HasEventWizardStrategy;
use Illuminate\Foundation\Http\FormRequest;

class EventSaveStepRequest extends FormRequest
{
    use HasEventWizardStrategy;

    public function authorize(): bool
    {
        $event = $this->getEventModel();

        return $event !== null && (bool) $this->user()?->can('update', $event);
    }

    public function rules(): array
    {
        $rules = $this->getWizardStepRules();

        // // 🔍 DEBUG DIRECTO EN PANTALLA:
        // dd([
        //     'step_key' => $this->getStepKey(),
        //     'generated_rules' => $rules,
        //     'request_payload' => $this->all(), // Para comparar las llaves con el input enviado
        // ]);
        
        return $rules;
    }

    public function attributes(): array
    {
        return $this->getWizardStepAttributes();
    }

    public function messages(): array
    {
        $messages = $this->getWizardMessages();

        // // 🔍 DEBUG DE MENSAJES (Reglas + Específicos de Campos)
        // dd([
        //     'step' => $this->getStepKey(),
        //     'messages' => $messages,
        //     'attributes' => $this->getWizardStepAttributes(),
        // ]);

        return $messages;
    }
}