<?php

namespace App\Services;

use App\Models\ColorPalette;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Configuración general del evento: datos de la cuenta y la pareja, colores y
 * opciones del formulario de confirmación.
 */
class EventSettingsService
{
    public const DEFAULT_OPEN_LINK_MAX_PASSES = 3;

    public function __construct(private readonly EventService $eventService)
    {
    }

    /** Paletas que el superadmin dejó disponibles. */
    public function palettes(): Collection
    {
        return ColorPalette::where('is_active', true)->orderBy('id')->get();
    }

    /**
     * Valores actuales para el formulario.
     *
     * @return array<string, mixed>
     */
    public function currentValues(Event $event, User $user): array
    {
        [$partner1, $partner2] = $event->urlNames();
        $theme = $event->getFeature('theme', []);
        $rsvp = $event->getFeature('rsvp', []);

        return [
            'name' => $user->name,
            'partner_1_name' => $partner1,
            'partner_2_name' => $partner2,
            'custom_url' => $event->custom_url,
            'palette_id' => $theme['palette_id'] ?? null,
            'custom_colors' => (bool) ($theme['is_custom'] ?? false),
            'primary_color' => $event->theme_colors['primary'],
            'secondary_color' => $event->theme_colors['secondary'],
            'allow_children' => (bool) ($rsvp['allow_children'] ?? true),
            'open_link_max_passes' => (int) ($rsvp['open_link_max_passes'] ?? self::DEFAULT_OPEN_LINK_MAX_PASSES),
        ];
    }

    /**
     * @param array<string, mixed> $data datos validados por UpdateEventSettingsRequest
     */
    public function update(Event $event, User $user, array $data): void
    {
        DB::transaction(function () use ($event, $user, $data) {
            $user->update(['name' => $data['name']]);

            $this->eventService->updateUrlNames(
                $event,
                $data['partner_1_name'],
                $data['partner_2_name'],
                $data['custom_url'] ?? null,
            );

            $features = $event->fresh()->features ?? [];
            $features['theme'] = $this->theme($features['theme'] ?? [], $data);
            // Se mezcla con lo que guarda el wizard en el mismo paso.
            $features['rsvp'] = array_merge($features['rsvp'] ?? [], [
                'allow_children' => $data['allow_children'],
                'open_link_max_passes' => $data['open_link_max_passes'],
            ]);

            $event->update(['features' => $features]);
        });
    }

    private function theme(array $current, array $data): array
    {
        if ($data['custom_colors']) {
            return array_merge($current, [
                'palette_id' => null,
                'primary_color' => mb_strtoupper($data['primary_color']),
                'secondary_color' => mb_strtoupper($data['secondary_color']),
                'is_custom' => true,
            ]);
        }

        $palette = ColorPalette::findOrFail($data['palette_id']);

        return [
            'palette_id' => $palette->id,
            'primary_color' => $palette->primary_color,
            'secondary_color' => $palette->secondary_color,
            'accent_color' => $palette->accent_color,
            'is_custom' => false,
        ];
    }
}
