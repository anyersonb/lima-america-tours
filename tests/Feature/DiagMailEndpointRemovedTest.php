<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Hallazgo crítico C-2 (docs/qa/seguridad-consolidada.md): la ruta de
 * diagnóstico /_diag/mail quedó en routes/web.php protegida solo por un
 * token hardcodeado y commiteado ("lvt-mail-diag-2026"), permitiendo
 * enviar correo arbitrario con el SMTP del cliente y filtrar su config.
 *
 * El propio comentario en el código decía "QUITAR después de resolver el
 * problema de envío de reservas". Este test fija el comportamiento
 * esperado: el endpoint no debe existir en absoluto, con o sin token.
 */
class DiagMailEndpointRemovedTest extends TestCase
{
    public function test_diag_mail_without_token_returns_not_found(): void
    {
        $response = $this->get('/_diag/mail');

        $response->assertNotFound();
    }

    public function test_diag_mail_with_valid_token_returns_not_found(): void
    {
        $response = $this->get('/_diag/mail?key=lvt-mail-diag-2026&to=victima@example.com');

        $response->assertNotFound();
    }
}
