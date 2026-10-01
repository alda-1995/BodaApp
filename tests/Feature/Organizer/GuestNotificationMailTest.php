<?php

namespace Tests\Feature\Organizer;

use App\Mail\GuestNotificationMail;
use App\Notifications\RenderedMessage;
use Tests\TestCase;

/**
 * El correo que le llega al invitado.
 *
 * Se arma de verdad (no con Mail::fake) porque los errores de la plantilla sólo
 * salen al renderizarla: con el mailer falso el envío "pasa" aunque la vista
 * esté rota.
 */
class GuestNotificationMailTest extends TestCase
{
    public function test_el_correo_se_arma_con_los_datos_del_invitado(): void
    {
        $mail = new GuestNotificationMail(new RenderedMessage(
            subject: 'Estás invitado a nuestra boda',
            body: "Hola Betsy Torres,\nnos encantará verte.",
            url: 'https://tamira.app/invitacion/harry-y-zoe/abc',
            guestName: 'Betsy Torres',
        ));

        $html = $mail->render();

        $this->assertStringContainsString('Estás invitado a nuestra boda', $html);
        $this->assertStringContainsString('Betsy Torres', $html);
        $this->assertStringContainsString('https://tamira.app/invitacion/harry-y-zoe/abc', $html);
        $this->assertSame('Estás invitado a nuestra boda', $mail->envelope()->subject);
    }
}
