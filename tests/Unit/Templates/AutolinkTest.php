<?php

namespace Tests\Unit\Templates;

use App\Templates\Support\Autolink;
use Tests\TestCase;

/**
 * El organizador escribe texto normal; la invitación lo vuelve tocable.
 */
class AutolinkTest extends TestCase
{
    public function test_convierte_correo_telefono_y_liga_en_enlaces(): void
    {
        $html = Autolink::toHtml(
            "Reservaciones vía e-mail: mexicosantafe.res@barcelo.com\n"
            . "Reservaciones vía telefónica: 55 5004 1616 Ext. 2812\n"
            . 'Más informes en www.barcelo.com'
        );

        $this->assertStringContainsString('href="mailto:mexicosantafe.res@barcelo.com"', $html);
        $this->assertStringContainsString('href="tel:5550041616"', $html);
        $this->assertStringContainsString('href="https://www.barcelo.com" target="_blank"', $html);
        // La extensión queda como texto, fuera del enlace.
        $this->assertStringContainsString('</a> Ext. 2812', $html);
    }

    public function test_respeta_las_cifras_que_no_son_telefonos(): void
    {
        $html = Autolink::toHtml('Somos 120 invitados y la cita es a las 15:30.');

        $this->assertStringNotContainsString('<a ', $html);
    }

    public function test_no_deja_pasar_html(): void
    {
        $html = Autolink::toHtml('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
