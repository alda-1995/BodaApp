<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Deshabilita las invitaciones cuya vigencia terminó (fecha del evento + días de
 * la plantilla). La app ya las trata como vencidas aunque este comando no haya
 * corrido (Event::isAvailable); esto deja el estado guardado en la BD.
 */
class DeactivateExpiredEvents extends Command
{
    protected $signature = 'events:deactivate-expired';

    protected $description = 'Deshabilita las invitaciones digitales vencidas';

    public function handle(): int
    {
        $count = Event::query()
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['is_active' => false, 'updated_at' => now()]);

        if ($count > 0) {
            Log::info("Invitaciones vencidas deshabilitadas: {$count}");
        }

        $this->info("Invitaciones deshabilitadas: {$count}");

        return self::SUCCESS;
    }
}
