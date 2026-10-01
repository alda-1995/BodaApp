<?php

namespace App\Services;

use App\DTOs\User\UserFilterDTO;
use App\Models\Event;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
class UserService
{

    public function getPaginatedUsers(UserFilterDTO $dto): LengthAwarePaginator
    {
        $query = User::query()
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'superadmin');
            })
            ->with(['latestEvent.template', 'latestEvent.order'])
            ->latest();

        if ($dto->search) {
            $query->where(function ($q) use ($dto) {
                $q->where('name', 'like', "%{$dto->search}%")
                    ->orWhere('email', 'like', "%{$dto->search}%");
            });
        }

        if ($dto->status) {
            $soon = now()->addDays(Event::EXPIRING_SOON_DAYS);

            match ($dto->status) {
                // Nunca compró una invitación.
                'no_template' => $query->whereDoesntHave('events'),
                Event::STATUS_ACTIVE => $query->whereHas('events', function ($q) use ($soon) {
                        $q->where('is_active', true)
                            ->whereNotNull('event_date')
                            ->where('expires_at', '>', $soon);
                    }),
                Event::STATUS_EXPIRING => $query->whereHas('events', function ($q) use ($soon) {
                        $q->where('is_active', true)
                            ->whereBetween('expires_at', [now(), $soon]);
                    }),
                Event::STATUS_EXPIRED => $query->whereHas('events', function ($q) {
                        $q->where('is_active', true)->where('expires_at', '<', now());
                    }),
                // Compró pero aún no captura la fecha de su boda.
                Event::STATUS_PENDING_DATE => $query->whereHas('events', function ($q) {
                        $q->where('is_active', true)->whereNull('event_date');
                    }),
                Event::STATUS_SUSPENDED => $query->whereHas('events', function ($q) {
                        $q->where('is_active', false);
                    }),
                default => null,
            };
        }

        return $query->paginate($dto->perPage)->withQueryString();
    }

    public function getUserByRole($role, int $perPage = 10): LengthAwarePaginator
    {
        return User::whereHas("roles", function ($query) use ($role) {
            $query->where("name", $role);
        })->paginate($perPage);
    }

    public function getUserById($id): ?User
    {
        try {
            $user = User::find($id);
            return $user ?? null;
        } catch (Exception $e) {
            Log::error('No se pudo encontrar el usuario: ' . $e->getMessage(), [
                'data' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $user = User::find($id);
            return $user ? $user->delete() : false;
        } catch (Exception $e) {
            Log::error('Error al elimnar el usuario: ' . $e->getMessage(), [
                'data' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}