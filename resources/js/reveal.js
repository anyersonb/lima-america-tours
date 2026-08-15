// Aparición sutil al hacer scroll ("tipo AOS"), sin librería externa: solo
// este observer + la clase .lat-reveal de resources/scss/pages/_lat-home.scss.
// No se usó AOS a propósito (encargo del jefe, 2026-08-15): es una dependencia
// más que pesar, y el sitio ya vigila su peso (ver HomeImageWeightBudgetTest)
// y su CSP (SecurityHeaders.php) no necesita permitir un CDN nuevo para esto.
//
// Contrato SEO/AEO/GEO — el texto tiene que verse SIN JavaScript. Por eso el
// ocultamiento NUNCA lo decide el CSS solo (ver _lat-home.scss): lo decide
// ESTE script, y solo después de confirmar que puede terminar el trabajo.
// Si `IntersectionObserver` no existe, o no hay ningún `.lat-reveal` en la
// página, el script no toca el DOM y todo se queda visible tal como lo
// pintó el HTML — nunca queda a medias con contenido oculto y nadie que lo revele.
(function () {
    if (!('IntersectionObserver' in window)) return;

    var items = document.querySelectorAll('.lat-reveal');
    if (!items.length) return;

    // Recién AQUÍ se arma el ocultamiento. Antes de esta línea cualquier
    // .lat-reveal de la página es un elemento normal, visible de por sí.
    document.documentElement.classList.add('lat-reveal-armed');

    var reduceMotion = !!(
        window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );

    var io = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-in');
            // Una sola vez: se deja de observar apenas se revela, no se
            // vuelve a ocultar/animar si el usuario sube y baja el scroll.
            observer.unobserve(entry.target);
        });
    }, {
        // Se dispara un poco antes de que el borde inferior del elemento
        // toque el viewport, para que la aparición no se sienta tardía.
        rootMargin: '0px 0px -10% 0px',
        threshold: 0.15,
    });

    items.forEach(function (el) {
        if (reduceMotion) {
            // prefers-reduced-motion: visible de una vez, sin observar ni animar.
            el.classList.add('is-in');
            return;
        }
        io.observe(el);
    });
})();
