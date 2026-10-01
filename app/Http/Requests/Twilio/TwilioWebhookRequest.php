<?php

namespace App\Http\Requests\Twilio;

use Illuminate\Foundation\Http\FormRequest;

class TwilioWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'From' => ['required', 'string', 'starts_with:whatsapp:'],
            'To' => ['required', 'string', 'starts_with:whatsapp:'],
            'MessageSid' => ['required', 'string'],
            'WaId' => ['nullable', 'string'],
            'ProfileName' => ['nullable', 'string'],
            'Body' => ['nullable', 'string'],
        ];
    }
}
