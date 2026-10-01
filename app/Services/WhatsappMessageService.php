<?php

namespace App\Services;

use App\Models\WhatsappMessage;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WhatsappMessageService
{

    public function getPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return WhatsappMessage::orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function createMessage(array $data): ?WhatsappMessage
    {
        try {
            return WhatsappMessage::create($data);
        } catch (Exception $e) {
            Log::error('Error en WhatsappMessageService al crear mensaje:', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}