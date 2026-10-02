<?php

namespace App\Services;

use App\Mail\CoadminInvitationMail;
use App\Models\Event;
use App\Models\EventCoadmin;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

/**
 * Coadministradores: el dueño invita por correo; quien acepta puede editar la
 * información del evento (no su configuración, invitados ni notificaciones).
 */
class CoadminService
{
    public const MAX_PER_EVENT = 5;
    public const INVITATION_DAYS = 7;

    public function canInviteMore(Event $event): bool
    {
        return $event->coadmins()->count() < self::MAX_PER_EVENT;
    }

    public function invite(Event $event, User $inviter, string $email): EventCoadmin
    {
        $invitation = $event->coadmins()->create([
            'email' => $email,
            'invited_by' => $inviter->id,
        ]);

        Mail::to($invitation->email)->queue(new CoadminInvitationMail(
            inviterName: $inviter->name ?: $inviter->email,
            coupleNames: $this->coupleLabel($event),
            acceptUrl: $this->acceptUrl($invitation),
            expiresInDays: self::INVITATION_DAYS,
        ));

        return $invitation;
    }

    /**
     * Liga firmada y con caducidad: sólo el correo invitado la recibe.
     */
    public function acceptUrl(EventCoadmin $invitation): string
    {
        return URL::temporarySignedRoute(
            'coadmin.invitation.show',
            now()->addDays(self::INVITATION_DAYS),
            ['invitation' => $invitation->id],
        );
    }

    /**
     * Liga la invitación a la cuenta. El correo de la cuenta debe ser el invitado.
     */
    public function accept(EventCoadmin $invitation, User $user): void
    {
        if (mb_strtolower($user->email) !== $invitation->email) {
            throw new InvalidArgumentException('La invitación es para otro correo.');
        }

        DB::transaction(function () use ($invitation, $user) {
            $invitation->update([
                'user_id' => $user->id,
                'accepted_at' => now(),
            ]);

            $user->roles()->syncWithoutDetaching([Role::named(EventCoadmin::ROLE)->id]);
        });
    }

    /**
     * Crea la cuenta de quien no tenía una y acepta la invitación. El correo sale
     * de la invitación, nunca del formulario.
     *
     * La cuenta nace también como organizadora: así puede comprar su propia
     * invitación y, si después le quitan el acceso a la boda ajena, no se queda
     * sin ningún rol (y sin poder entrar a nada).
     */
    public function registerAndAccept(EventCoadmin $invitation, string $name, string $password): User
    {
        return DB::transaction(function () use ($invitation, $name, $password) {
            $user = User::create([
                'name' => $name,
                'email' => $invitation->email,
                'password' => Hash::make($password),
            ]);

            // La contraseña la escribió la persona, no es una generada por la
            // compra: si después compra su propia boda no hay que pedírsela.
            $user->forceFill(['password_changed_at' => now()])->save();

            $user->roles()->syncWithoutDetaching([Role::named('organizer')->id]);
            $this->accept($invitation, $user);

            return $user;
        });
    }

    /**
     * Quita el acceso (o cancela la invitación pendiente). Si la persona ya no
     * administra ningún evento, pierde también el rol.
     */
    public function remove(EventCoadmin $invitation): void
    {
        DB::transaction(function () use ($invitation) {
            $user = $invitation->user;
            $invitation->delete();

            if ($user && !$user->coadminships()->exists()) {
                $role = Role::where('name', EventCoadmin::ROLE)->first();
                $role && $user->roles()->detach($role->id);
            }
        });
    }

    private function coupleLabel(Event $event): string
    {
        $names = $event->displayNames();

        return $names ? implode(' y ', $names) : ($event->title ?: 'la boda');
    }
}
