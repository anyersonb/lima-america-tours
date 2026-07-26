# Auditoría de seguridad CONSOLIDADA (F6) — Lima América Tours

- **Proyecto:** `G:\laragon\www\lima-america`
- **Rama auditada:** `qa/paginas`
- **Fecha:** 2026-07-25
- **Auditor:** security-engineer (AnyersonDev)
- **Alcance:** OWASP Top 10 + específicos Laravel/Filament + frontend. Lectura de código + chequeos seguros (`composer audit`, `npm audit`). No se ejecutaron pagos reales, envíos masivos ni borrado de datos. No se tocó código de la app ni datos: solo se creó este reporte.

---

## Veredicto: 🔴 NO APTO PARA PRODUCCIÓN

Bloqueado por dependencias con vulnerabilidades **críticas/altas** (incluye RCE en Livewire) y por un **endpoint de diagnóstico expuesto**. El código propio de la aplicación está, en general, bien construido (precios server-side, webhook con HMAC, sin IDOR, sin SQLi/command injection, buen rate limiting). El bloqueo proviene sobre todo de la **cadena de dependencias desactualizada** y de un **debug endpoint que quedó en `routes/web.php`**.

> **ACTUALIZACIÓN 2026-07-25 (backend-laravel, rama `qa/paginas`):** Ambos hallazgos 🔴 críticos (C-1 y C-2) quedaron **RESUELTOS**. Ver detalle en cada sección y en `docs/qa/FIXES.md` fila #17. Quedan pendientes (no bloqueantes para este lote, documentados abajo): A-1 (Laravel 10 EOL), A-2 (XSS de contenido admin sin escapar — mitigado en parte por el bump de Filament pero sin sanitización explícita del HTML del RichEditor), `phpseclib/phpseclib` desactualizado (transitivo de `culqi/culqi-php`, fuera de alcance por tratarse del árbol de la pasarela de pago) y un hallazgo nuevo menor: `public/opcache-reset.php` reutiliza el mismo token hardcodeado `lvt-mail-diag-2026` que tenía `/_diag/mail` (no formaba parte de este encargo, que era específicamente sobre `routes/web.php`).

### Conteo por nivel

| Nivel | Cantidad |
|-------|----------|
| 🔴 Crítico (bloqueante) | 2 → **0 pendientes** (2 resueltos 2026-07-25) |
| 🟠 Alto | 3 |
| 🟡 Medio | 4 |
| 🟢 Bajo / hardening | 4 |

---

## Hallazgos críticos (BLOQUEANTES)

### ✅ RESUELTO — 🔴 C-1 · Dependencias con CVEs críticas/altas — RCE en Livewire
- **Módulo/superficie:** Dependencias Composer (panel Filament + framework).
- **Descripción:** `composer audit` reporta **38 advisories en 17 paquetes**. La más grave:
  - **`livewire/livewire` v3.6.3 → CVE-2025-54068 (CRÍTICA): Remote Command Execution** durante la hidratación de propiedades de componentes. Filament corre sobre Livewire, por lo que el panel `/admin` (y cualquier componente Livewire público) es superficie de ataque. Corregido en **3.6.4** (la instalada está a **un solo patch** de la corrección).
  - `filament/forms` v3.3.x → **CVE-2026-55409 (ALTA): RichEditor deshabilitado usable para XSS** (afecta el editor del blog/páginas).
  - `filament/filament` → CVE-2026-48500 (media): **subida temporal de archivos sin autenticar** en páginas de auth.
  - `symfony/mime` v6.4.21 → **CVE-2026-45067 (ALTA): inyección CRLF / comandos SMTP** vía `Address` (relevante: la app envía muchos correos con datos de usuario — contacto, reservas, credenciales).
  - `symfony/http-foundation` v6.4.22 → **CVE-2025-64500 (ALTA): bypass de autorización limitado por parseo de PATH_INFO**.
  - `laravel/framework` v10.48.29 → CRLF injection en la regla de email (alta) + Signed URL path confusion (media).
  - `guzzlehttp/guzzle` 7.9.3, `guzzlehttp/psr7`, `league/commonmark`, `phpseclib`, `symfony/*` (routing, process, yaml, polyfill) → varias medias/bajas.
- **Vector:** RCE explotable contra el endpoint Livewire (message/update) sin necesidad de credenciales válidas en escenarios conocidos del CVE; el resto: XSS almacenado vía editor, inyección de cabeceras en correos, etc.
- **Remediación:**
  1. `composer update` para subir dentro de los constraints actuales: **Livewire ≥ 3.6.4**, **Filament ≥ 3.3.53**, **Symfony 6.4 ≥ 6.4.41**, **Guzzle ≥ 7.15.1**, `league/commonmark ≥ 2.8.2`, `phpseclib ≥ 3.0.54`. La mayoría son patches dentro del mismo major → bajo riesgo de romper.
  2. Correr `php artisan test` tras actualizar (GATE de humo).
  3. Volver a ejecutar `composer audit` hasta dejar 0 críticas/altas.
- **Deriva a:** backend-laravel.
- **Resolución (2026-07-25, rama `qa/paginas`):** `composer update livewire/livewire symfony/mime symfony/http-foundation guzzlehttp/guzzle league/commonmark --with-dependencies` (Livewire 3.6.3→**3.8.2**, symfony/mime 6.4.21→6.4.41, symfony/http-foundation 6.4.22→6.4.42, guzzle 7.9.3→7.15.1, commonmark 2.7.0→2.8.3) + `composer update "filament/*"` (3.3.26→**3.3.54**, dentro del constraint `^3.2` ya existente, sin editar `composer.json`). El CVE-2025-54068 (RCE) ya no aparece en `composer audit`; tampoco los CVEs de Filament RichEditor XSS (2026-55409), upload sin auth (2026-48500) ni bypass de scope Attach/Associate (2026-48067). `composer audit` bajó de **38 advisories/17 paquetes** a **11 advisories/6 paquetes**. Verificado: `php artisan test` sin regresiones (baseline 4 failed de `CheckoutTest` intacta, `SmokeTest` verde), `curl` a `:8002/admin` y `:8002/es` (+ tours/blog) responden 200 tras `php artisan filament:clear-cached-components`. Detalle completo en `docs/qa/FIXES.md` #17.
- **Pendiente (no crítico, no bloqueante):** los 11 advisories restantes son `laravel/framework` 10.x (requieren Laravel ≥12.61.1/13.12.0 — major upgrade, ver A-1) + sus transitivos `symfony/mailer`/`process`/`routing`/`yaml` (mismo motivo) + `phpseclib/phpseclib` 3.0.52→3.0.54 (transitivo de `culqi/culqi-php`, no tocado en este lote por estar en el árbol de la pasarela de pago — fuera del alcance autorizado).

### ✅ RESUELTO — 🔴 C-2 · Endpoint de diagnóstico de correo expuesto en producción
- **Módulo/superficie:** `routes/web.php:65-113` — ruta `GET /_diag/mail`.
- **Descripción:** Ruta de diagnóstico protegida solo por un **token hardcodeado y commiteado**: `key=lvt-mail-diag-2026`. Con esa clave (visible en el repo y adivinable) cualquiera puede:
  - **Enviar correos a CUALQUIER dirección** (`?to=` arbitrario) usando el SMTP del cliente → abuso / mail-bombing / riesgo de blacklist del dominio.
  - **Filtrar configuración de correo:** host SMTP, puerto, encriptación, `from`, `app_env`, usuario (parcialmente enmascarado) y si hay password.
  - No tiene `throttle`.
- **Vector:** `GET /_diag/mail?key=lvt-mail-diag-2026&to=victima@dominio.com` — no requiere sesión.
- **Remediación:** **Eliminar la ruta** antes de producción (el propio comentario dice "QUITAR después de resolver"). Si se necesita diagnóstico, condicionarla a `app()->environment('local')` y protegerla tras `auth` del panel, nunca por token en código.
- **Deriva a:** backend-laravel.
- **Resolución (2026-07-25, rama `qa/paginas`):** Ruta y closure completos eliminados de `routes/web.php` (líneas 54-107 del archivo original). `php artisan route:list` ya no muestra ninguna ruta `diag.*`. Verificado con `tests/Feature/DiagMailEndpointRemovedTest.php`: antes del fix, `GET /_diag/mail?key=lvt-mail-diag-2026` devolvía 200 (confirmando el hallazgo); tras el fix, tanto con token válido como sin él devuelve 404.
- **Hallazgo relacionado sin resolver (fuera de este encargo):** `public/opcache-reset.php` reutiliza el **mismo token hardcodeado** `lvt-mail-diag-2026` para autorizar un reset de OPcache vía `?key=`. No se tocó porque el encargo era específicamente sobre `routes/web.php`, pero es el mismo patrón de riesgo (token commiteado y adivinable) y debería revisarse en un próximo lote — idealmente moverlo a un comando artisan protegido por `auth` o rotarlo a un secreto en `.env`.

---

## Hallazgos altos

### 🟠 A-1 · Laravel 10 fuera de soporte de seguridad (EOL)
- **Módulo:** `laravel/framework ^10.10` (instalado 10.48.29).
- **Descripción:** El soporte de seguridad de Laravel 10 finalizó (~feb 2026). Aunque 10.48.29 es el último 10.x, futuros CVEs (como los ya listados de la rama 10) no recibirán parche garantizado. Es deuda de seguridad estructural, no un exploit puntual.
- **Remediación:** Planificar upgrade a Laravel 11/12 (Filament v3 soporta ambos). Mientras tanto, aplicar C-1 al máximo posible dentro de 10.x.
- **Deriva a:** backend-laravel (planificación).

### 🟠 A-2 · XSS almacenado vía contenido de admin renderizado sin escapar
- **Módulo:** `resources/views/blog/show.blade.php:146` (`{!! $post->body !!}`), `resources/views/home.blade.php:83` (`{!! $heroTitleRaw !!}`).
- **Descripción:** El cuerpo del blog (RichEditor de Filament) y el título hero (Setting) se imprimen sin escapar. Combinado con **CVE-2026-55409 (RichEditor XSS)** de C-1, un usuario del panel con permisos limitados —o un admin comprometido— puede inyectar JS que se ejecuta en el sitio público. Nota positiva: las reseñas/testimonios enviados por usuarios **sí se escapan** (`{{ $quote }}` en `reviews.blade.php:163`), y el `<script>` en título de tour se escapa (`tours/show.blade.php` usa `JSON_HEX_TAG|JSON_HEX_AMP`).
- **Remediación:** Sanitizar el HTML del RichEditor con una allowlist (p.ej. `mews/purifier` o `Str::sanitizeHtml()` de Filament ya parchado) antes de renderizar; para el hero, restringir a un subconjunto conocido o escapar. Actualizar Filament (C-1) es prerequisito.
- **Deriva a:** backend-laravel.

### 🟠 A-3 · Inyección de cabeceras en correo (dependiente de C-1)
- **Módulo:** flujos de correo con input de usuario — `ContactController` (email/mensaje), `CheckoutController` (customer_email → AccountCredentials/BookingNotifier), newsletter.
- **Descripción:** Con `symfony/mime`/`symfony/mailer` vulnerables (CVE-2026-45067/45068/45070), datos de usuario que llegan a direcciones/cabeceras pueden derivar en inyección CRLF/SMTP. La app valida `email` con la regla de Laravel (que también tuvo CVE de CRLF), pero el mensaje/asunto libre aumenta la superficie.
- **Remediación:** Se cubre al aplicar C-1 (bump de Symfony y Laravel). Mantener validación estricta de asunto/nombre (ya hay `max` y tipos).
- **Deriva a:** backend-laravel (resuelto por C-1).

---

## Hallazgos medios

### 🟡 M-1 · `.env.example` ships con `APP_DEBUG=true` / `APP_ENV=local` activos
- **Módulo:** `.env.example`.
- **Descripción:** Los valores de producción (`APP_DEBUG=false`, `APP_ENV=production`, `APP_FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, `SESSION_DRIVER=database`) están **comentados**; los activos son de desarrollo. Dado el historial de deploys del equipo, hay riesgo real de publicar con debug activo → stack traces con datos sensibles.
- **Remediación (checklist de despliegue, no código):** Verificar en el `.env` del servidor de producción: `APP_DEBUG=false`, `APP_ENV=production`, `APP_FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`. Confirmar que la página de error no muestra traza.
- **Deriva a:** deploy / backend-laravel.

### 🟡 M-2 · Mass assignment amplio (`$guarded = ['id']`) en modelos sensibles
- **Módulo:** `Booking`, `Testimonial`, `ContactLead`, `NewsletterSubscriber`, `Offer`, `Setting`, etc. (`app/Models/*`).
- **Descripción:** Casi todos los modelos usan `$guarded = ['id']`, lo que deja mass-assignable columnas sensibles (`payment_status`, `status`, `is_active`, `is_read`, `unit_price`, `total_price`…). **Hoy NO es explotable**: todos los `create()/update()` de los controladores públicos usan arrays explícitos, nunca `$request->all()` (verificado: 0 coincidencias de `->all()`/`fill($request…)`). Es un riesgo **latente**: cualquier futuro `Model::create($request->all())` inyectaría esas columnas (p.ej. marcar una reserva como `paid` sin pagar).
- **Remediación (defense-in-depth):** definir `$fillable` explícito en `Booking`/`Testimonial`/`ContactLead`, o `$guarded` que incluya las columnas sensibles. Añadir un test que falle si un endpoint público acepta `payment_status`.
- **Deriva a:** backend-laravel.

### 🟡 M-3 · reCAPTCHA con comportamiento fail-open cuando está deshabilitado
- **Módulo:** `app/Services/RecaptchaService.php` (`verify()` retorna `true` si el módulo está `disabled`).
- **Descripción:** Si `recaptcha_enabled` está en `false` en producción, los formularios (contacto, newsletter, reseñas, registro, login admin) quedan **sin captcha**; solo los protegen honeypot + rate limiting. El diseño es correcto (fail-closed cuando está habilitado pero falta secret/red), pero depende de configuración operativa.
- **Remediación:** Confirmar en el panel (Configuración → reCAPTCHA) que está **habilitado en producción** con claves válidas y versión correcta (v2/v3 según las claves — ver antecedente Lima View Tours donde v2/v3 quedó mal configurado y rompió los forms).
- **Deriva a:** deploy / cliente (config del panel).

### 🟡 M-4 · `filament/filament` — subida temporal de archivos sin autenticar (CVE-2026-48500)
- **Módulo:** dependencia Filament (subconjunto de C-1, se destaca por su naturaleza no autenticada).
- **Descripción:** Permite subida de archivos temporales sin sesión en las páginas de autenticación del panel. Las subidas propias de la app son admin-only y usan `->image()`/`acceptedFileTypes`/`maxSize` (correcto), pero este CVE elude esa protección a nivel del componente base.
- **Remediación:** Cubierto por C-1 (Filament ≥ 3.3.53).
- **Deriva a:** backend-laravel.

---

## Hallazgos bajos / hardening

### 🟢 B-1 · CSP con `'unsafe-inline'` y `'unsafe-eval'` en `script-src`
- **Módulo:** `app/Http/Middleware/SecurityHeaders.php:20`.
- **Descripción:** Necesarios por scripts inline/GTM/PayPal actuales, pero debilitan la mitigación de XSS. Hardening: migrar a CSP con `nonce` por request y remover `unsafe-inline`/`unsafe-eval`.

### 🟢 B-2 · JSON-LD sin `JSON_HEX_TAG`
- **Módulo:** `resources/views/components/jsonld.blade.php:105-106`.
- **Descripción:** `json_encode()` sin `JSON_HEX_TAG|JSON_HEX_AMP` (a diferencia de `tours/show.blade.php` que sí lo hace). Datos vienen de Settings/config (admin), riesgo bajo, pero por consistencia conviene añadir las flags para evitar breakout `</script>`.

### 🟢 B-3 · `canAccessPanel()` solo valida dominio de email
- **Módulo:** `app/Models/User.php:32`.
- **Descripción:** Cualquier `User` con email `@webtilia.com` o `@limaamericatours.com` accede al panel completo, sin flag `is_active` ni roles. Aceptable en el modelo actual (usuarios creados por seeder/artisan, no hay registro público a `/admin`). Hardening: añadir `is_active` y/o roles (Filament Shield) si crecen los usuarios del panel.

### 🟢 B-4 · Token de recuperación de carrito abandonado
- **Módulo:** `app/Models/AbandonedCart.php:27` (`Str::uuid()`).
- **Descripción:** El link `/carrito/recuperar/{token}` usa UUID v4 (no adivinable) y solo restaura contenido de carrito (baja sensibilidad). Sin throttle, pero el impacto es mínimo. OK; se documenta como aceptable.

---

## Verificaciones realizadas

- [✓] **OWASP A01 Broken Access Control** — `/admin` protegido (302 sin sesión); rutas de cliente tras `auth:customer`. **Sin IDOR**: `AccountController::dashboard` filtra reservas por `customer_id`/`customer_email` del usuario autenticado (`AccountController.php:19-25`); `updateProfile` usa datos validados con verificación de password actual. Webhook exento de CSRF pero con HMAC.
- [✓] **OWASP A02 Cryptographic Failures** — passwords con cast `hashed` (bcrypt); sin secretos hardcodeados (`sk_live`/`pk_live`/API keys: 0 hits); `.env` fuera de git (`.gitignore` cubre `.env*`); solo `.env.example` trackeado. Logs no vuelcan passwords en claro.
- [✓] **OWASP A03 Injection** — Eloquent con bindings; `orderByRaw` parametrizado (`BlogController.php:54`, `HomeController.php:51` con strings estáticos/bindings); **0** `DB::raw`/`whereRaw` con input; **0** `shell_exec`/`exec`/`system`/`eval`/`unserialize`. XSS: entradas de usuario escapadas; contenido admin sin escapar → ver A-2.
- [✓] **OWASP A04 Insecure Design** — checkout calcula precios **server-side** (`CheckoutController::paypalCreateOrder` usa `$this->cart->total()`, nunca el cliente); re-verificación de fechas bloqueadas defense-in-depth; cupones vía servicio.
- [✓] **OWASP A05 Security Misconfiguration** — headers presentes (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, CSP, HSTS condicional). Ver M-1 (debug) y B-1 (CSP).
- [✓] **OWASP A06 Vulnerable Components** — `composer audit`: 38 advisories/17 paquetes → C-1 (bloqueante). `npm audit --omit=dev`: **0 vulnerabilidades**.
- [✓] **OWASP A07 Identification & Auth Failures** — throttle en login cliente (5/min), registro (10/min), recuperar (5/min), contacto (3/min), newsletter (5/min), checkout (5/min), reseñas (6/min), **login Filament (5/min + email+ip)**; `session()->regenerate()` en login cliente (`LoginController.php:26`); recuperación con `Password::broker` (tokens hasheados, sin enumeración — respuesta neutral en `ForgotPasswordController`); reCAPTCHA en login admin + forms (ver M-3).
- [✓] **OWASP A08 Software & Data Integrity** — webhook Culqi con verificación **HMAC** (`WebhookController::culqi`) + idempotencia; sin deserialización insegura; uploads admin-only con validación de tipo/tamaño (`->image()`, `acceptedFileTypes`, `maxSize`).
- [✓] **OWASP A09 Logging Failures** — eventos críticos logueados (login honeypot, webhook, bookings, contacto); no se detectó volcado de passwords. El endpoint C-2 loguea config de correo (parte del problema).
- [✓] **OWASP A10 SSRF** — `Http::` solo hacia Culqi/PayPal (URLs no controladas por usuario); `file_get_contents` solo sobre el path temporal del archivo subido, no URLs remotas. Sin allowlist necesaria porque no hay fetch de URL de usuario.
- [✓] **CSRF Laravel** — solo `webhooks/*` exento (justificado por HMAC); resto bajo middleware `web`.
- [✓] **Mass assignment** — ningún `create/update` con `$request->all()`; riesgo latente documentado en M-2.
- [✓] **Uploads / Storage** — Filament FileUpload admin-only, discos `public`/`media`, con `->image()`/tipos/tamaño; sin `file_put_contents` con path de usuario (no path traversal).
- [✓] **Frontend** — reseñas/testimonios de usuario escapados (`{{ }}`); tokens de sesión en cookie `httpOnly` (`config/session.php:184 http_only=true`, `same_site=lax`), no en localStorage; `npm audit` limpio.

---

## Recomendaciones de hardening (opcionales)

1. Migrar CSP a `nonce` por request y eliminar `unsafe-inline`/`unsafe-eval` (B-1).
2. Añadir `JSON_HEX_TAG|JSON_HEX_AMP` a todos los `json_encode` de JSON-LD (B-2).
3. Definir `$fillable` explícito en modelos con columnas sensibles (M-2).
4. Añadir `is_active`/roles (Filament Shield) a usuarios del panel (B-3).
5. Programar `composer audit` en CI para no volver a acumular deuda.
6. Planificar upgrade a Laravel 11/12 (A-1).

---

## Top hallazgos accionables (orden de prioridad)

| # | Hallazgo | Nivel | Acción | Responsable | Estado |
|---|----------|-------|--------|-------------|--------|
| 1 | Livewire RCE + CVEs de dependencias | 🔴 | `composer update` (Livewire ≥3.6.4, Filament ≥3.3.53, Symfony ≥6.4.41, Guzzle ≥7.15.1) + `php artisan test` | backend-laravel | ✅ Resuelto 2026-07-25 (Livewire 3.8.2, Filament 3.3.54) |
| 2 | `/_diag/mail` expuesto | 🔴 | Eliminar la ruta de `routes/web.php` | backend-laravel | ✅ Resuelto 2026-07-25 |
| 3 | Laravel 10 EOL | 🟠 | Planificar upgrade a 11/12 | backend-laravel | 🟠 Pendiente (major upgrade, requiere lote dedicado) |
| 4 | XSS RichEditor (blog/hero) | 🟠 | Sanitizar HTML + actualizar Filament | backend-laravel | 🟡 Parcial: Filament actualizado (cierra los CVEs conocidos de RichEditor/upload/scope); falta sanitización explícita de `{!! !!}` en `blog/show.blade.php`/`home.blade.php` (defense-in-depth) |
| 5 | Verificar `.env` prod (debug/https/cookies) | 🟡 | Checklist de deploy | deploy | Pendiente (operativo, no de código) |
| 6 | reCAPTCHA habilitado en prod | 🟡 | Config panel | deploy/cliente | Pendiente (operativo) |
| 7 | `phpseclib/phpseclib` desactualizado (transitivo de `culqi/culqi-php`) | 🟡 | `composer update phpseclib/phpseclib` (3.0.52→3.0.54) | backend-laravel | 🟡 Pendiente — no tocado en este lote por estar en el árbol de dependencias de la pasarela de pago (fuera del alcance autorizado) |
| 8 | `public/opcache-reset.php` con el mismo token hardcodeado que tenía `/_diag/mail` | 🟡 | Mover a comando artisan con `auth` o rotar a secreto `.env` | backend-laravel | 🟡 Pendiente — detectado como efecto colateral de C-2, no formaba parte de este encargo |

**Re-auditado 2026-07-25** tras aplicar C-1 y C-2: `composer audit` bajó de 38 advisories/17 paquetes a 11 advisories/6 paquetes (los restantes atados a Laravel 10 EOL y phpseclib, ver arriba). Con C-1 y C-2 resueltos, el veredicto de bloqueo por críticos queda levantado; persisten los 🟠/🟡/🟢 documentados como deuda a planificar antes de producción.
