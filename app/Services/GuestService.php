<?php

namespace App\Services;

use App\DTOs\Guest\GuestDTO;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GuestService
{
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Guest::with('user')->orderBy('name', 'asc')->paginate($perPage);
    }
    
    /**
     * Evento del organizador al que se ligan sus invitados.
     */
    public function eventFor(User $user): ?Event
    {
        return $user->currentEvent();
    }

    public function paginateForUser(User $user, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $event = $this->eventFor($user);

        return $user->guests()
            ->when($search, function ($query, $search) {
                $term = '%' . $search . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->with(['events' => fn ($query) => $query->whereKey($event?->id ?? 0)])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Invitado del organizador; lanza 404 si no existe o es de otro usuario.
     */
    public function findForUser(User $user, int $id): Guest
    {
        $event = $this->eventFor($user);

        return $user->guests()
            ->with(['events' => fn ($query) => $query->whereKey($event?->id ?? 0)])
            ->findOrFail($id);
    }

    public function createForUser(User $user, GuestDTO $dto): Guest
    {
        try {
            return DB::transaction(function () use ($user, $dto) {
                $guest = $user->guests()->create($dto->guestAttributes());
                $this->syncInvitation($user, $guest, $dto->maxPasses);

                return $guest;
            });
        } catch (Exception $e) {
            Log::error('Error al crear invitado: ' . $e->getMessage(), ['user_id' => $user->id]);
            throw new Exception('No se pudo registrar al invitado. Intenta nuevamente.');
        }
    }

    public function updateForUser(User $user, Guest $guest, GuestDTO $dto): Guest
    {
        try {
            return DB::transaction(function () use ($user, $guest, $dto) {
                $guest->update($dto->guestAttributes());
                $this->syncInvitation($user, $guest, $dto->maxPasses);

                return $guest->refresh();
            });
        } catch (Exception $e) {
            Log::error('Error al actualizar invitado: ' . $e->getMessage(), ['guest_id' => $guest->id]);
            throw new Exception('No se pudieron actualizar los datos del invitado.');
        }
    }

    public function deleteGuest(Guest $guest): void
    {
        try {
            DB::transaction(function () use ($guest) {
                $guest->events()->detach();
                $guest->delete();
            });
        } catch (Exception $e) {
            Log::error('Error al eliminar invitado: ' . $e->getMessage(), ['guest_id' => $guest->id]);
            throw new Exception('No se pudo eliminar al invitado.');
        }
    }

    /**
     * Crea o actualiza la invitación del invitado para el evento del organizador.
     * El uuid del enlace se genera una sola vez y no cambia al editar.
     */
    protected function syncInvitation(User $user, Guest $guest, int $maxPasses): void
    {
        $event = $this->eventFor($user);

        if (!$event) {
            return;
        }

        if ($guest->events()->whereKey($event->id)->exists()) {
            $guest->events()->updateExistingPivot($event->id, ['max_passes' => $maxPasses]);
        } else {
            $guest->events()->attach($event->id, [
                'uuid' => (string) Str::uuid(),
                'max_passes' => $maxPasses,
            ]);
        }
    }

    /**
     * Importa invitados desde un CSV: el formato que exportan Excel y Google Sheets.
     * Acepta ',' o ';' como separador y encabezados con o sin acentos. Las filas con
     * error se reportan y no frenan a las válidas.
     *
     * @return array{created: int, errors: array<int, string>}
     */
    public function importCsvForUser(User $user, UploadedFile $file): array
    {
        $rows = $this->readCsv($file->getRealPath());
        $columns = $this->mapImportColumns(array_shift($rows) ?? []);

        if (!isset($columns['name'])) {
            return ['created' => 0, 'errors' => ['El archivo debe tener una columna "nombre".']];
        }

        $created = 0;
        $errors = [];
        $existingEmails = $user->guests()->whereNotNull('email')->pluck('email')
            ->map(fn ($email) => mb_strtolower($email))->flip()->all();

        foreach ($rows as $index => $row) {
            $line = $index + 2; // la fila 1 es el encabezado
            $value = fn (string $key) => isset($columns[$key]) ? trim((string) ($row[$columns[$key]] ?? '')) : '';

            $data = [
                'name' => $value('name'),
                'email' => $value('email') ?: null,
                'phone' => $this->normalizePhone($value('phone')),
                'max_passes' => $value('max_passes') === '' ? 1 : $value('max_passes'),
            ];

            if ($data['name'] === '' && $data['email'] === null && $data['phone'] === null) {
                continue; // fila vacía
            }

            $validator = Validator::make($data, GuestDTO::rules(), GuestDTO::messages());
            if ($validator->fails()) {
                $errors[] = "Fila {$line}: " . $validator->errors()->first();
                continue;
            }

            $emailKey = $data['email'] ? mb_strtolower($data['email']) : null;
            if ($emailKey && isset($existingEmails[$emailKey])) {
                $errors[] = "Fila {$line}: el correo {$data['email']} ya está en tu lista.";
                continue;
            }

            $this->createForUser($user, GuestDTO::fromArray($data));

            if ($emailKey) {
                $existingEmails[$emailKey] = true;
            }
            $created++;
        }

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * Lee el CSV quitando el BOM y convirtiendo desde Windows-1252 si Excel no lo
     * guardó en UTF-8. Detecta el separador por la primera línea.
     */
    protected function readCsv(string $path): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($path));

        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $lines = array_values(array_filter(
            preg_split('/\r\n|\r|\n/', trim($content)),
            fn ($line) => trim($line) !== ''
        ));

        $first = $lines[0] ?? '';
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';

        return array_map(fn ($line) => str_getcsv($line, $delimiter), $lines);
    }

    /**
     * Relaciona cada columna del archivo con un campo, aceptando variantes del encabezado.
     */
    protected function mapImportColumns(array $header): array
    {
        $aliases = [
            'name' => ['nombre', 'name', 'invitado'],
            'email' => ['correo', 'email', 'correo_electronico', 'e-mail'],
            'phone' => ['telefono', 'phone', 'celular', 'whatsapp'],
            'max_passes' => ['acompanantes', 'tope_acompanantes', 'max_passes', 'numeroinvitados', 'invitados_permitidos'],
        ];

        $columns = [];
        foreach ($header as $position => $label) {
            $normalized = Str::of(Str::ascii((string) $label))->lower()->trim()->replace(' ', '_')->toString();

            foreach ($aliases as $key => $names) {
                if (!isset($columns[$key]) && in_array($normalized, $names, true)) {
                    $columns[$key] = $position;
                }
            }
        }

        return $columns;
    }

    /**
     * Acepta el teléfono como se escribe en una hoja de cálculo: con espacios o
     * guiones, con o sin '+', o sólo los 10 dígitos de México (se antepone +52).
     */
    protected function normalizePhone(string $phone): ?string
    {
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with(trim($phone), '+')) {
            return '+' . $digits;
        }

        return strlen($digits) === 10 ? '+52' . $digits : '+' . $digits;
    }
}