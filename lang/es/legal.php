<?php

return [
    'terms_title' => 'Términos y Condiciones',
    'privacy_title' => 'Política de Privacidad',
    'last_updated' => 'Última actualización: 7 de mayo de 2026',

    // Terms sections
    'terms_s1_title' => 'Aceptación de los Términos',
    'terms_s1_body' => 'Al acceder y utilizar los servicios de Lima América Tours, usted acepta quedar vinculado por los presentes términos y condiciones. Si no está de acuerdo con alguna parte de estos términos, le rogamos que no utilice nuestros servicios. Lima América Tours se reserva el derecho de actualizar estos términos en cualquier momento, notificando a los usuarios mediante la publicación de la nueva versión en este sitio.',

    'terms_s2_title' => 'Descripción del Servicio',
    'terms_s2_body' => 'Lima América Tours es una empresa dedicada a la organización y comercialización de tours y experiencias turísticas en Lima, Ica, Cusco y otras regiones del Perú. Nuestros servicios incluyen la planificación de itinerarios, transporte, guías turísticos certificados y actividades culturales. Los precios, disponibilidad e itinerarios pueden cambiar sin previo aviso por motivos operativos o de fuerza mayor.',

    'terms_s3_title' => 'Reservas y Pagos',
    'terms_s3_body' => 'Al completar el proceso de reserva, usted puede elegir pagar de inmediato mediante PayPal (tarjeta de crédito/débito o saldo PayPal) o reservar ahora y coordinar el pago después. En ambos casos, la reserva se confirma por correo electrónico y, de ser necesario, por WhatsApp, donde nuestro equipo verifica los datos del tour y coordina la forma de pago. Lima América Tours no almacena datos de tarjetas: cuando el pago es inmediato, el procesamiento es gestionado íntegramente por PayPal, un proveedor de pagos certificado.',

    'terms_s4_title' => 'Cancelaciones y Reembolsos',
    'terms_s4_body' => 'Si su reserva quedó pendiente de pago (opción "reservar ahora, pagar después"), puede cancelarla sin costo en cualquier momento antes de la fecha del tour, ya que no se le ha cobrado nada. Para reservas ya pagadas mediante PayPal: las cancelaciones realizadas con más de 48 horas de antelación a la fecha del tour recibirán un reembolso del 80 % del importe pagado; las efectuadas entre 24 y 48 horas antes tendrán derecho a un reembolso del 50 %. No se realizarán reembolsos por cancelaciones con menos de 24 horas de anticipación ni por ausencias en el punto de salida. En casos de fuerza mayor (desastres naturales, huelgas, restricciones gubernamentales) se ofrecerá reprogramación sin cargo adicional.',

    'terms_s5_title' => 'Contacto',
    // Restructurado 2026-08-11: el párrafo único traía un teléfono
    // (+51 935 542 384, de NINGÚN cliente de esta agencia), un horario y una
    // dirección hardcodeados, publicados en TRES idiomas — ver
    // App\Models\Setting::contactPhone()/contactHours()/contactAddress(),
    // fuente única ahora. Cada línea se imprime solo si el dato existe en el
    // panel (Configuración → Contacto); ninguna imprime "llamando al ."
    // cuando el dato falta.
    'terms_s5_intro' => 'Para cualquier consulta relacionada con estos términos, puede contactarnos a través del formulario en nuestra página de contacto o por correo electrónico.',
    'terms_s5_phone_line' => 'También puede llamarnos al :phone.',
    'terms_s5_hours_line' => 'Nuestro equipo atiende :hours.',
    'terms_s5_address_line' => 'Lima América Tours — :address.',

    // Privacy sections
    'privacy_s1_title' => 'Datos que Recopilamos',
    'privacy_s1_body' => 'Lima América Tours recopila los datos estrictamente necesarios para prestar nuestros servicios. Esto incluye: nombre completo, correo electrónico y número de teléfono al realizar una reserva o suscribirse al boletín; datos de navegación anónimos (cookies de análisis) para mejorar la experiencia de usuario; y, únicamente cuando elige pagar de inmediato, información de pago que es procesada directamente por PayPal y no es almacenada en nuestros servidores.',

    'privacy_s2_title' => 'Uso de los Datos',
    'privacy_s2_body' => 'Utilizamos su información personal exclusivamente para: confirmar y gestionar reservas, enviar comunicaciones relacionadas con su tour, mejorar nuestros servicios mediante análisis agregado y, con su consentimiento expreso, enviarle boletines con ofertas y novedades. No utilizaremos sus datos para toma de decisiones automatizadas ni perfilado sin su consentimiento.',

    'privacy_s3_title' => 'Compartir Información',
    'privacy_s3_body' => 'Lima América Tours no vende, arrienda ni comercializa su información personal a terceros. Únicamente compartimos datos con proveedores operativos indispensables (la plataforma de pago PayPal cuando elige pagar de inmediato, servicio de envío de correos transaccionales) quienes se comprometen contractualmente a tratar los datos con el mismo nivel de protección. Podemos divulgar información cuando la ley lo exija o para proteger nuestros derechos legales.',

    'privacy_s4_title' => 'Cookies',
    'privacy_s4_body' => 'Utilizamos cookies propias para el funcionamiento del carrito de compras y la gestión de sesiones, así como cookies de terceros de Google Analytics (anónimas) para análisis de tráfico. Puede configurar su navegador para rechazar cookies; tenga en cuenta que esto puede afectar la funcionalidad del sitio. Al continuar navegando acepta nuestra política de cookies.',

    'privacy_s5_title' => 'Derechos del Usuario',
    'privacy_s5_body' => 'De conformidad con la Ley N.° 29733 de Protección de Datos Personales del Perú, usted tiene derecho a acceder, rectificar, cancelar u oponerse al tratamiento de sus datos personales.',
    // La mención a "Jr. Lampa 209, Lima Center" se retiró 2026-08-11: no es
    // una dirección confirmada (ver App\Models\Setting::contactAddress()).
    // Dos variantes según haya o no dirección real cargada en el panel.
    'privacy_s5_exercise_email' => 'Para ejercer estos derechos, envíe una solicitud por escrito a nuestro correo electrónico.',
    'privacy_s5_exercise_email_and_address' => 'Para ejercer estos derechos, envíe una solicitud por escrito a nuestro correo electrónico o a nuestra dirección física (:address).',
    'privacy_s5_response_time' => 'Responderemos en un plazo máximo de 20 días hábiles.',

    // ── ESNNA — Código de conducta ────────────────────────────────────────
    // Pedido del jefe el 2026-08-21. Solo se citan normas verificables: la
    // Ley N.° 28251 y la Ley N.° 29408 (Ley General de Turismo). Los canales
    // de denuncia son los oficiales del Estado peruano (Línea 100 del MIMP y
    // el 105 de la PNP). No se inventa ningún número de resolución.
    'esnna_last_updated' => 'Última actualización: 24 de agosto de 2026',
    'esnna_title' => 'Código de conducta contra la ESNNA',
    'esnna_intro' => 'Lima América Tours rechaza de forma absoluta la explotación sexual de niñas, niños y adolescentes (ESNNA) en el ámbito del turismo, y asume el compromiso público de prevenirla y denunciarla.',

    // Afiche oficial de MINCETUR. El alt describe lo que el afiche DICE:
    // quien no puede verlo tiene que recibir el mensaje, no el nombre del
    // archivo.
    'esnna_poster_alt' => 'Afiche oficial de MINCETUR: en esta agencia no promovemos ni permitimos la explotación sexual de niñas, niños y adolescentes, conforme a la Ley N.º 29408. Denuncia a la línea gratuita 1818 o a la Línea 100.',
    'esnna_poster_caption' => 'Afiche oficial del Ministerio de Comercio Exterior y Turismo. Haz clic para verlo en tamaño completo.',

    'esnna_s1_title' => 'Nuestro compromiso',
    'esnna_s1_body' => 'Como agencia de viajes peruana asumimos que el turismo no puede ser una vía para el abuso. Nos comprometemos a no facilitar, promover, tolerar ni encubrir ninguna forma de explotación sexual de niñas, niños y adolescentes, ni por acción propia ni a través de terceros que trabajen con nosotros. Este compromiso alcanza a todo nuestro personal, a nuestros guías y a cada proveedor de transporte, alojamiento y actividades con el que operamos.',

    'esnna_s2_title' => 'Qué es la ESNNA',
    'esnna_s2_body' => 'La explotación sexual de niñas, niños y adolescentes es cualquier situación en la que una persona menor de 18 años es utilizada para actividades sexuales a cambio de dinero, bienes, favores o cualquier otra ventaja, para quien la explota o para un tercero. No es un trabajo, no es una elección y no deja de ser un delito porque haya un pago, un intermediario o el consentimiento aparente de la víctima o de su familia.',

    'esnna_s3_title' => 'Qué hacemos en la práctica',
    'esnna_s3_body' => 'Informamos y capacitamos a nuestro equipo para reconocer señales de riesgo; incluimos este compromiso en los acuerdos con nuestros proveedores; rechazamos cualquier solicitud de servicios que pueda tener como fin la explotación de menores, cancelando la reserva sin reembolso; y ponemos en conocimiento de las autoridades competentes cualquier hecho o indicio que detectemos, resguardando la identidad de la víctima y de quien informa.',

    'esnna_s4_title' => 'Cómo denunciar',
    'esnna_s4_intro' => 'Si conoces o sospechas un caso, la denuncia es gratuita, puede ser anónima y no necesita pruebas: basta la sospecha razonable.',
    'esnna_s4_line100' => 'Línea 100 (Ministerio de la Mujer y Poblaciones Vulnerables): marca 100, gratuito, las 24 horas, desde cualquier teléfono del Perú.',
    'esnna_s4_police' => 'Policía Nacional del Perú: marca 105 en caso de emergencia, o acude a la comisaría más cercana.',
    'esnna_s4_us' => 'También puedes escribirnos a nosotros: si algo de lo que viste ocurrió durante uno de nuestros tours o con alguno de nuestros proveedores, queremos saberlo para actuar y denunciarlo.',

    'esnna_s5_title' => 'Marco legal',
    'esnna_s5_body' => 'En el Perú, la explotación sexual comercial de niñas, niños y adolescentes en el ámbito del turismo es un delito tipificado en el Código Penal a partir de la Ley N.° 28251. La Ley N.° 29408, Ley General de Turismo, obliga a los prestadores de servicios turísticos a colaborar en su prevención. Este código de conducta es nuestra aplicación de esas obligaciones y se revisa cada vez que la normativa cambia.',
];
