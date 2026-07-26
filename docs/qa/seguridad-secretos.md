# Auditoría de secretos y credenciales — Lima América Tours

**Fecha:** 2026-07-25
**Auditor:** security-engineer (AnyersonDev)
**Repo:** `https://github.com/anyersonb/lima-america-tours.git`
**Rama auditada:** `design/rojo-acento` (working tree limpio) — HEAD `8345298`
**Alcance:** working tree actual + historial completo (`--all`) + los 4 commits públicos de `origin/main`
**Modo de operación:** solo lectura. No se rotó, borró ni inventó ningún secreto. No se modificó código de la app.

---

## Metodología (comandos exactos ejecutados)

```bash
# Estado y ramas
git branch --show-current            # design/rojo-acento
git status --short                   # (vacío = working tree limpio)
git log --oneline origin/main        # los 4 commits públicos
git log --all --oneline              # historial completo

# Archivos .env trackeados / ignorados
git ls-files | grep -i env           # -> solo .env.example
git check-ignore .env .env.qa .env.prod
git show HEAD:.gitignore | grep -i env

# .env colados en el historial (alta de archivo en cualquier commit)
git log --all --oneline -- '.env' '.env.*' '*.env'
git log --all --diff-filter=A --name-only -- '*.env*' '.env*'

# Barrido de valores secretos en TODAS las revisiones
git grep -nI -E "pk_live_|sk_live_|pk_test_|sk_test_|PAYPAL_CLIENT_SECRET=.+|CULQI_[A-Z_]*=.+|access_token|BEGIN .*PRIVATE KEY" $(git rev-list --all)

# Barrido de valores reales (no placeholders) en el árbol trackeado
git grep -nI -E "pk_live_[0-9a-zA-Z]|sk_live_[0-9a-zA-Z]|pk_test_[0-9a-zA-Z]{10}|sk_test_[0-9a-zA-Z]{10}|AIza[0-9A-Za-z_-]{20}|BEGIN .*PRIVATE KEY|AKIA[0-9A-Z]{16}" HEAD

# Valores reales de secretos de infra en todo el historial
git grep -hI -E "APP_KEY=base64:[A-Za-z0-9+/=]{20,}|DB_PASSWORD=.+|MAIL_PASSWORD=[A-Za-z0-9]{3,}" $(git rev-list --all)

# Config de pasarelas
git show HEAD:.env.example | grep -iE "PAYPAL|CULQI|APP_KEY|APP_ENV|DB_PASSWORD|MAIL_PASSWORD|_MODE|_KEY|_SECRET"
git show HEAD:config/services.php   # bloques paypal/culqi/google
git show HEAD:app/Services/PayPalService.php  # lógica de baseUrl / mode
```

---

## Qué se escaneó

- **Working tree actual** (`design/rojo-acento` @ `8345298`): `config/services.php`, `.env.example`, `app/Services/*` (incluye `PayPalService.php`, `PaymentService.php`, `CartService.php`, etc.), `docs/**`, y todo `git ls-files`.
- **Historial completo** de todas las refs (`git rev-list --all`, 22 commits).
- **Los 4 commits públicos de `origin/main`** (auditados uno a uno):
  1. `9be6bcc` — Lima América Tours — motor forkeado + frontend rediseñado (v1)
  2. `c530a51` — docs: preservar maqueta visual en docs/propuesta
  3. `81ba722` — fix: correcciones CRO + QA (rebrand, CMS y front)
  4. `f2ac0f1` — docs(qa): protocolo y delegación de QA + ESTADO y backlog

---

## Hallazgos

### ¿Algún secreto real commiteado? **NO.**

| Verificación | Resultado |
|---|---|
| Único archivo de entorno trackeado | `.env.example` (plantilla con placeholders, sin valores reales) |
| `.env`, `.env.qa`, `.env.prod` trackeados | **No** — `git ls-files` no los lista |
| `git check-ignore .env .env.qa .env.prod` | Los tres están **ignorados** (confirmado) |
| `.env*` colado alguna vez en el historial | **No** — el único alta que matchea `*.env*` en toda la historia es `.env.example` (commit `9be6bcc`) |
| Claves live/test reales (`pk_/sk_/AIza/AKIA/PRIVATE KEY`) en árbol trackeado | **Ninguna** (grep vacío) |
| Claves live/test reales en cualquier revisión del historial | **Ninguna** — los hits de `access_token` son nombres de variable/labels de log/tabla `personal_access_tokens`, no valores |
| `APP_KEY` / `DB_PASSWORD` / `MAIL_PASSWORD` con valor real en historial | **Ninguno** — en `.env.example`: `APP_KEY=` vacío, `DB_PASSWORD=` vacío, `MAIL_PASSWORD=null` |

**Valores en `.env.example` (plantilla pública, correcto):**
```
APP_KEY=            DB_PASSWORD=            MAIL_PASSWORD=null
CULQI_PUBLIC_KEY=pk_test_REPLACE_ME
CULQI_SECRET_KEY=sk_test_REPLACE_ME
CULQI_WEBHOOK_SECRET=whsec_REPLACE_ME
CULQI_ENV=sandbox
PAYPAL_CLIENT_ID=   PAYPAL_SECRET=   PAYPAL_MODE=sandbox   PAYPAL_WEBHOOK_ID=
```
Todos son placeholders (`REPLACE_ME`) o vacíos. **No hay secretos reales.**

**`config/services.php`** — todas las credenciales se leen por `env()`, nada hardcodeado:
```php
'culqi'  => ['public_key' => env('CULQI_PUBLIC_KEY'), 'secret_key' => env('CULQI_SECRET_KEY'),
             'webhook_secret' => env('CULQI_WEBHOOK_SECRET'), 'env' => env('CULQI_ENV','sandbox')],
'paypal' => ['client_id' => env('PAYPAL_CLIENT_ID'), 'secret' => env('PAYPAL_SECRET'),
             'mode' => env('PAYPAL_MODE','sandbox'), 'webhook_id' => env('PAYPAL_WEBHOOK_ID')],
'google' => ['maps_api_key' => env('GOOGLE_MAPS_API_KEY'), 'place_id' => env('GOOGLE_PLACE_ID')],
```

**`docs/qa/**` y `docs/pagos/PLAN-PASARELAS.md`:** solo referencias documentales al *formato* de las llaves (`pk_...`, `sk_...`, `whsec_...`). No contienen valores.

> Nota: en disco existen `.env` y `.env.qa` (no trackeados, correctamente ignorados). El `.env` local está en `APP_ENV=local`, `PAYPAL_MODE=sandbox`, `CULQI_ENV=sandbox`, con llaves Culqi `pk_test_`/`sk_test_`. **No forman parte de git**, por lo que no representan exposición en el repositorio.

---

## PayPal: sandbox o live

**Modo actual = SANDBOX.** Evidencia:

- `config/services.php`: `'mode' => env('PAYPAL_MODE', 'sandbox')` → default **sandbox**.
- `.env.example`: `PAYPAL_MODE=sandbox`.
- `.env` local (no trackeado): `PAYPAL_MODE=sandbox`.
- `app/Services/PayPalService.php` (líneas 27-31) selecciona la URL base según el modo:
  ```php
  return $this->mode === 'live'
      ? 'https://api-m.paypal.com'          // live
      : 'https://api-m.sandbox.paypal.com'; // sandbox (default)
  ```
- El modo/credenciales pueden sobreescribirse en runtime vía `Setting::get('paypal_mode')` (BD, panel admin), no vía git. En git el default es **sandbox** y no hay credenciales.

## Culqi: sandbox/test o live

**Modo actual = SANDBOX / TEST.** Evidencia:

- `config/services.php`: `'env' => env('CULQI_ENV', 'sandbox')` → default **sandbox**.
- `.env.example`: `CULQI_ENV=sandbox`, `CULQI_PUBLIC_KEY=pk_test_REPLACE_ME`, `CULQI_SECRET_KEY=sk_test_REPLACE_ME` (prefijos `pk_test_`/`sk_test_` = test).
- `.env` local (no trackeado): `CULQI_ENV=sandbox`, llaves `pk_test_`/`sk_test_`.
- Culqi aún está en fase de plan/documentación (`docs/pagos/PLAN-PASARELAS.md`); no hay llaves `pk_live_`/`sk_live_` en ninguna parte del repo ni del historial.

---

## Veredicto: **GO para `git push`**

Se puede pushear el estado actual de `design/rojo-acento` **sin exponer secretos**:

- El historial completo (incluidos los 4 commits ya públicos en `origin/main`) **no contiene ninguna credencial real** de PayPal, Culqi, Google, AWS, mail ni base de datos.
- El único archivo de entorno versionado es `.env.example`, con placeholders.
- `.env`, `.env.qa`, `.env.prod` están correctamente en `.gitignore` y **no trackeados**.
- No hay claves privadas, tokens ni API keys hardcodeadas en código o docs.

**No se requiere remediación de historial** (no hay nada que purgar con `filter-repo`/BFG).

---

## Recomendaciones de hardening (no bloqueantes)

1. **Credenciales productivas solo por variables de entorno / panel admin**, nunca en commits. Antes de pasar PayPal/Culqi a `live`, cargar `PAYPAL_MODE=live` y las llaves reales únicamente en el `.env` del servidor (no versionado) o en `Setting` de BD.
2. **Rotar cualquier llave de prueba** que se haya compartido por canales inseguros al migrar a producción; usar exclusivamente `pk_live_`/`sk_live_` en el `.env` de prod.
3. Considerar un **pre-commit hook** (gitleaks / git-secrets) para bloquear futuros commits con patrones `pk_live_`, `sk_live_`, `base64:`, `PRIVATE KEY`.
4. Verificar en el servidor que `APP_ENV=production` y `APP_DEBUG=false` (fuera del alcance de esta auditoría de git: el `.env` local está en `local`/`debug=true`, lo cual es correcto para desarrollo pero **no debe** replicarse en prod).

---

## No cubierto

- **`.env` reales de servidor (prod/qa remotos):** no auditados; están fuera del repositorio y no accesibles desde este entorno. Esta auditoría cubre únicamente lo que vive en git.
- **Validez/estado de las llaves de prueba locales:** no se verificó contra la API de PayPal/Culqi si son válidas o revocadas (fuera de alcance; no se ejecutan llamadas externas).
- **`composer audit` / `npm audit`** (CVEs de dependencias): no forma parte de esta auditoría de secretos; se cubre en la auditoría de seguridad consolidada.
