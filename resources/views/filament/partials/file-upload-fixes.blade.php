{{--
    docs/qa/F7-personas.md §g #3 (bloqueante) y §labels #5 / §g #8.

    Contexto — por qué esto vive aquí y no en un test PHPUnit:
    Filament renderiza el campo de imágenes con FilePond (JS de terceros,
    vendor/filament/forms/resources/js/components/file-upload.js). Ese
    componente marca el <form> como "procesando" en el evento `addfilestart`
    y solo lo libera en `processfile` / `processfileabort` / `processfilerevert`
    (ver ese archivo, líneas ~296-328). Cuando FilePond RECHAZA un archivo por
    tipo inválido (ej. un PDF en un campo de imagen), ninguno de esos tres
    eventos de "terminado" se dispara — el archivo nunca llega a procesarse,
    solo queda marcado con una X roja. El resultado: el <form> se queda con
    `isProcessing = true` para siempre (vendor/filament/filament/resources/
    views/components/form/index.blade.php, `x-on:submit="if (isProcessing)
    $event.preventDefault()"`), y "Crear"/"Guardar" deja de responder sin
    ningún mensaje — exactamente el defecto reportado por Doña Rosa.

    No se parchea el vendor (rompe en cada `composer update`). En su lugar:

    1) Watchdog: si un formulario queda "procesando" más tiempo del que
       tarda una subida real (imágenes de pocos MB, optimizadas localmente),
       se fuerza `isProcessing = false` usando la API pública de Alpine
       (`Alpine.$data`), liberando el botón de envío.
    2) Mensajes de FilePond en español llano (docs/qa/F7-personas.md §e):
       "Archivo de tipo inválido... Expects image/*" → mensaje sin jerga.

    Verificación manual (no automatizable vía PHPUnit — requiere un
    navegador real): en http://127.0.0.1:8002/admin, Tours → Crear → pestaña
    Imágenes → Galería → subir un PDF → confirmar que el mensaje ya no dice
    "image/*" y que, aun con el PDF rechazado en la lista, "Crear" vuelve a
    responder pasados unos segundos.
--}}
<script>
    (function () {
        var WATCHDOG_MS = 10000; // margen generoso: una subida real (imágenes optimizadas) termina muy por debajo de esto

        function attachWatchdog(form) {
            if (form.__limaUploadWatchdogBound) return;
            form.__limaUploadWatchdogBound = true;

            var timer = null;

            var clear = function () {
                if (timer) {
                    clearTimeout(timer);
                    timer = null;
                }
            };

            form.addEventListener('form-processing-started', function () {
                clear();
                timer = setTimeout(function () {
                    var data = window.Alpine && window.Alpine.$data ? window.Alpine.$data(form) : null;
                    if (data && data.isProcessing) {
                        data.isProcessing = false;
                    }
                }, WATCHDOG_MS);
            });

            form.addEventListener('form-processing-finished', clear);
        }

        function scan(root) {
            if (!root || !root.querySelectorAll) return;
            root.querySelectorAll('form.fi-form').forEach(attachWatchdog);
        }

        document.addEventListener('DOMContentLoaded', function () {
            scan(document);
        });
        document.addEventListener('livewire:navigated', function () {
            scan(document);
        });

        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.matches && node.matches('form.fi-form')) attachWatchdog(node);
                    scan(node);
                });
            });
        }).observe(document.documentElement, {childList: true, subtree: true});

        // Mensajes de FilePond en español llano (defecto §labels #5 / §g #8).
        // window.FilePond lo expone el propio JS de Filament (línea 24 de
        // file-upload.js); se espera a que exista por si este script corre
        // antes de que cargue el bundle.
        var attempts = 0;
        (function setFilePondLabels() {
            attempts++;
            if (window.FilePond) {
                window.FilePond.setOptions({
                    labelFileTypeNotAllowed: 'Tipo de archivo no permitido',
                    fileValidateTypeLabelExpectedTypes: 'Solo se permiten imágenes (JPG, PNG o WEBP)',
                });
            } else if (attempts < 60) {
                setTimeout(setFilePondLabels, 50);
            }
        })();
    })();
</script>
