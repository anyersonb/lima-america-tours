{{-- ============================================================
     Modal de video reutilizable.

     El mismo modal estaba copiado dos veces (home.blade.php:878-890 +
     :1395-1456 y about.blade.php:374-384 + :996-1062). El artículo de blog
     sería la tercera copia, así que el mecanismo vive acá una sola vez.
     Home y Nosotros siguen con su copia propia a propósito: cambiarlas ahora
     tocaría dos pantallas ya validadas en staging por un refactor que no
     pidió nadie — se unifican cuando alguna de las dos se vuelva a abrir.

     Props:
       id      → id del <div> del modal. El disparador se ata solo con
                 aria-controls="{{ $id }}" (no hace falta pasarle el id del botón).
       url     → URL YA normalizada por App\Support\VideoEmbed::normalize().
                 Si viene vacía/null, no se imprime nada: el llamador no
                 necesita envolver el componente en un @if.
       label   → texto accesible del diálogo y title del <iframe>.

     El <iframe> se crea al abrir, nunca al cargar la página: el video no
     aparece en la red de nadie que no haya hecho clic.
     ============================================================ --}}
@props([
    'id',
    'url' => null,
    'label' => 'Video',
])

@if (filled($url))
    <div class="lat-video-modal" id="{{ $id }}" role="dialog" aria-modal="true"
         aria-label="{{ $label }}"
         data-video-modal
         data-video-src="{{ $url }}"
         data-video-title="{{ $label }}" hidden>
        <div class="lat-video-modal__backdrop" data-video-close></div>
        <div class="lat-video-modal__panel">
            <button type="button" class="lat-video-modal__close" data-video-close
                    aria-label="{{ __('ui.close_video') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
            <div class="lat-video-modal__frame" data-video-frame></div>
        </div>
    </div>

    {{-- Un solo script para todos los modales de la página: recorre
         [data-video-modal] y ata cada uno a su(s) disparador(es) por
         aria-controls. Con @once, dos modales en la misma pantalla no
         duplican el listener. --}}
    @once
        @push('scripts')
        <script>
        (function () {
            var modals = Array.prototype.slice.call(document.querySelectorAll('[data-video-modal]'));

            modals.forEach(function (modal) {
                var triggers = Array.prototype.slice.call(
                    document.querySelectorAll('[aria-controls="' + modal.id + '"]')
                );
                if (!triggers.length) return;

                var frame = modal.querySelector('[data-video-frame]');
                var closers = Array.prototype.slice.call(modal.querySelectorAll('[data-video-close]'));
                var videoUrl = modal.getAttribute('data-video-src');
                var videoTitle = modal.getAttribute('data-video-title') || '';
                var lastFocused = null;

                function focusableEls() {
                    return Array.prototype.slice
                        .call(modal.querySelectorAll('button, [href], iframe, [tabindex]:not([tabindex="-1"])'))
                        .filter(function (el) { return el.offsetParent !== null; });
                }

                function onKeydown(e) {
                    if (e.key === 'Escape' || e.key === 'Esc') { close(); return; }
                    if (e.key !== 'Tab') return;
                    var els = focusableEls();
                    if (!els.length) return;
                    var first = els[0], last = els[els.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                }

                function open() {
                    lastFocused = document.activeElement;

                    var iframe = document.createElement('iframe');
                    iframe.src = videoUrl;
                    iframe.title = videoTitle;
                    iframe.allow = 'autoplay; fullscreen; picture-in-picture';
                    iframe.allowFullscreen = true;
                    iframe.setAttribute('frameborder', '0');
                    frame.innerHTML = '';
                    frame.appendChild(iframe);

                    modal.hidden = false;
                    document.body.style.overflow = 'hidden';
                    document.addEventListener('keydown', onKeydown);

                    var els = focusableEls();
                    (els[0] || modal).focus();
                }

                function close() {
                    modal.hidden = true;
                    document.body.style.overflow = '';
                    frame.innerHTML = ''; // corta el video al cerrar, no solo lo oculta
                    document.removeEventListener('keydown', onKeydown);
                    if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
                }

                triggers.forEach(function (el) { el.addEventListener('click', open); });
                closers.forEach(function (el) { el.addEventListener('click', close); });
            });
        })();
        </script>
        @endpush
    @endonce
@endif
