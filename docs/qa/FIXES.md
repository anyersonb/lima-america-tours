# Fixes aplicados durante QA (modo autonomía) — Lima América Tours

Cada fix cumple la verificación de 3 partes: (a) test que falla→pasa o evidencia en navegador; (b) suite de humo verde; (c) esta línea.

| # | Rama / commit | Qué estaba roto | Causa raíz | Cambio | Prueba |
|---|---|---|---|---|---|
| 1 | `qa/setup` | 5 tests daban 500 (CartTest ×2, CheckoutTest·thanks, NewsletterTest ×2). Además **fragilidad en TODAS las páginas** (layout) si falta cualquier `setting` de red social. | `resources/views/components/jsonld.blade.php:34-37` accedía `$settings['social_x']` por corchete → `Undefined array key` cuando `settings` está vacío/incompleto. Las líneas 38-40 ya usaban `?? null`. | `?:` → `?? null` en las 4 líneas de redes sociales. | `php artisan test`: **9→4 failed, 38 passed (133 assertions)**; `SmokeTest` verde; CartTest/NewsletterTest/CheckoutTest·thanks pasan. Baseline actualizada a 4. |
