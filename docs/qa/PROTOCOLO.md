# Protocolo de verificación de módulos — Lima América Tours

Guárdalo en el repo como `docs/qa/PROTOCOLO.md`. Es la fuente de verdad que el agente
debe leer **antes** de verificar cualquier módulo, hoy y en los proyectos siguientes.

---

## 0. Por qué existe este documento

La verificación de anoche funcionó, pero fue *ad-hoc*: sin criterio fijo de aprobación,
sin evidencia archivada, sin regresión, y mezclando "bug de código" con "falta contenido".
Con 7+ módulos eso se rompe: el módulo 6 rompe el 2 y nadie se entera hasta producción.

Este protocolo fija cuatro cosas: **qué se revisa, en qué orden, cómo se reporta, y cuándo
un módulo puede decirse "verificado"**.

---

## 1. Definición de Hecho (DoD) de un módulo

Un módulo está **VERIFICADO** solo si cumple las 6 capas del §3, tiene su reporte en
`docs/qa/<modulo>.md`, la suite de humo pasa en verde, y no queda ningún hallazgo 🔴 ni 🟠 abierto.

Cualquier otro estado es:

| Estado | Significa |
|---|---|
| `PENDIENTE` | construido, aún sin pasar verificación |
| `EN CORRECCIÓN` | verificado, con hallazgos abiertos |
| `VERIFICADO` | cumple el DoD |
| `VERIFICADO c/ deuda` | cumple, pero con 🟡 registrados y aceptados por el jefe |

Un módulo **nunca** se bloquea por hallazgos 🔵 (contenido). Ver §4.

---

## 2. Preparación del entorno (obligatoria, 1 sola vez por sesión de QA)

1. **Base de datos limpia y aislada.** QA no se corre sobre la BD de trabajo.
   ```
   php artisan migrate:fresh --seed --env=qa
   ```
   Todo registro que el agente cree durante la prueba lleva prefijo `QA_`
   (ej. `QA_Tour prueba`, `QA_Carlos`). Al final: purga por prefijo.
   Esto evita exactamente lo que pasó con `LVT-*` y "Carlos Prueba".
2. **Snapshot previo**: `mysqldump` o copia del `.sqlite` antes de empezar, para poder
   restaurar sin repetir el seed.
3. **Logs en cero**: `> storage/logs/laravel.log` antes de arrancar. Al final, el log debe
   estar vacío de `ERROR`/`CRITICAL`. Un log sucio = módulo no verificado, aunque la pantalla se vea bien.
4. **Consola del navegador abierta** durante todo el recorrido. Errores JS cuentan como hallazgos.
5. **Rama dedicada**: `qa/<modulo>`. Nada se mergea a `main` sin reporte.

---

## 3. Las 6 capas de verificación

Se ejecutan **en orden**. Si una capa tiene 🔴, se detiene y se corrige antes de seguir:
no tiene sentido evaluar CRO de una pantalla que devuelve 500.

### L1 — Render y salud técnica
- Todas las rutas del módulo devuelven 200 (y las protegidas, 302/403 sin sesión).
- Cero excepciones en `laravel.log`, cero errores en consola del navegador.
- Cero assets 404 (imágenes, CSS, JS) — el bug de `APP_URL` de anoche vive aquí.
- Sin `dd()`, `dump()`, `console.log`, ni TODO olvidado en el diff del módulo.

### L2 — CMS ↔ Front (el corazón del proyecto)
Por cada Filament Resource asociado al módulo:
- **Create**: campo por campo. Cada campo se llena y se guarda.
- **Read**: la tabla lista, filtra, ordena y pagina.
- **Update**: se edita cada campo y persiste.
- **Delete**: borra sin dejar huérfanos ni romper el front.
- **Validaciones**: cada `required` dispara, en español, y el mensaje se ve.
- **Sincronía**: se edita en admin → se recarga el front → el cambio aparece.
  Esto se prueba **en ambos sentidos** para todo campo que el front consuma
  (título, precio, badge de oferta, "No incluye", FAQ, imágenes, orden).
- Campos que el admin llena pero el front **ignora** = hallazgo 🟠. Fue el patrón de
  los bugs de anoche (badge, FAQ, "No incluye").

### L3 — Estados de datos
La pantalla se prueba con:
- **0 registros** (estado vacío: ¿mensaje decente o layout roto?)
- **1 registro**
- **Muchos** (paginación, scroll, performance)
- **Datos hostiles**: título de 200 caracteres, tour sin imagen, precio 0, texto con
  `<script>`, tildes y ñ, HTML pegado desde Word, fecha pasada.
- **404 y 403** del módulo (la 404 rebrandeada ya existe: confirmar que aplica aquí).

### L4 — Flujos e interacción
- Formularios: camino feliz + cada error de validación + doble submit + campos vacíos.
- Buscador y filtros: con resultados, sin resultados, combinados, y limpiando filtros.
- CTA principal: el flujo reserva → WhatsApp abre con el mensaje correcto y los datos correctos.
- Navegación: cada enlace del módulo lleva a donde dice (sin `#` muertos).

### L5 — UX, CRO y responsive
- Viewports **375 / 768 / 1440**. Sin overlaps (el caso del FAB tapando botones), sin
  scroll horizontal, sin texto cortado.
- CTA visible sin scroll en móvil. Jerarquía clara. Precio y moneda legibles.
- Copy 100 % en español, sin lorem, sin nombres de pasarelas que no se usan (Culqi),
  sin datos del seed presentados como reales.
- Imágenes con `alt`; peso razonable; `loading="lazy"` donde toque.

### L6 — Regresión y seguridad básica
- Se corre la **suite de humo** (§6) completa: el módulo nuevo no rompió los anteriores.
- Rutas de admin inaccesibles sin sesión.
- Ningún campo de texto rico renderiza HTML sin sanitizar en el front.
- Sin N+1 evidente en las listas (activar Debugbar o `DB::listen` un momento).

---

## 4. Clasificación de hallazgos

| Nivel | Criterio | Bloquea |
|---|---|---|
| 🔴 Bloqueante | 500, dato que no guarda, flujo de reserva roto, ruta inexistente | Sí |
| 🟠 Mayor | campo del CMS que el front ignora, validación en inglés, overlap en móvil, texto legal incorrecto | Sí |
| 🟡 Menor | espaciado, microcopy, mejora de CRO opcional | No (se registra) |
| 🔵 Contenido | fotos equivocadas, teléfono del seed, itinerarios "próximamente", datos de prueba | **Nunca** |

Regla dura: **el agente no inventa contenido para tapar un 🔵.** Lo registra en
`docs/qa/BACKLOG-CONTENIDO.md` y sigue. Los 🔵 son decisión tuya, no defectos.

---

## 5. Ciclo por módulo

```
construir → autochequeo del constructor → QA independiente → clasificar
   → corregir 🔴/🟠 → re-verificar SOLO lo fallado + humo → reporte → commit → merge
```

Dos reglas que evitan el 90 % del retrabajo:

1. **El que construye no aprueba.** Aunque sea el mismo agente, la pasada de QA se hace en
   sesión/contexto aparte, leyendo este protocolo desde cero, sin ver su propio razonamiento previo.
2. **Re-verificación acotada**: al corregir, se re-prueba el hallazgo *y* la suite de humo.
   No se repite el recorrido completo (eso es lo que convirtió anoche en 2h51).

Commit por módulo verificado:
`qa(<modulo>): verificado — N hallazgos corregidos, M en backlog de contenido`

---

## 6. Suite de humo (se corre en CADA verificación, de cualquier módulo)

Empieza manual, pero **conviértela en tests** cuanto antes — es lo que hace que esto escale
a 7 módulos sin que cada pasada cueste horas.

```php
// tests/Feature/SmokeTest.php  — Pest
it('carga las rutas públicas', function (string $ruta) {
    $this->get($ruta)->assertOk();
})->with(['/', '/tours', '/blog', '/nosotros', '/contacto', '/reserva']);

it('protege el admin', fn () => $this->get('/admin')->assertRedirect());
it('muestra 404 rebrandeada', fn () => $this->get('/no-existe')->assertNotFound());
```

Más una prueba por módulo que valide la **sincronía CMS→front** (editar modelo por factory,
pegarle a la ruta, `assertSee`). Meta realista: `php artisan test` en verde antes de cada merge.
Todo lo que quede fuera de tests (visual, CRO, responsive) es lo único que justifica Chrome MCP.

---

## 7. Matriz de estado (mantener actualizada en `docs/qa/ESTADO.md`)

| Módulo | L1 | L2 | L3 | L4 | L5 | L6 | Estado | Reporte |
|---|---|---|---|---|---|---|---|---|
| Home | | | | | | | PENDIENTE | |
| Tours (listado) | | | | | | | | |
| Ficha de tour | | | | | | | | |
| Blog | | | | | | | | |
| Nosotros | | | | | | | | |
| Reserva | | | | | | | | |
| Contacto | | | | | | | | |
| Panel Filament | | | | | | | | |

---

## 8. Formato del reporte — `docs/qa/<modulo>.md`

```markdown
# QA — <Módulo>
Fecha · Commit verificado · Duración

## Resultado: VERIFICADO / EN CORRECCIÓN
| # | Capa | Nivel | Hallazgo | Evidencia | Estado |
|---|------|-------|----------|-----------|--------|
| 1 | L2   | 🟠    | El badge de oferta no se pinta en el front | captura + ruta | corregido en abc1234 |

## Cubierto
- Rutas probadas: ...
- Campos del CMS probados: ... (lista explícita, no "todos")
- Viewports: 375 / 768 / 1440

## No cubierto (y por qué)
- ...

## Backlog de contenido (🔵) — no bloquea
- ...
```

"Probé todos los campos" sin lista **no es evidencia**. La lista explícita es lo que
permite auditar después qué se miró de verdad.

---

## 9. Prompt listo para pegarle al agente

> Vas a verificar **un solo módulo**: `<NOMBRE>`. No construyas nada nuevo, no inventes
> contenido, no toques otros módulos.
>
> 1. Lee `docs/qa/PROTOCOLO.md` completo antes de tocar nada.
> 2. Prepara el entorno según §2 (BD limpia, prefijo `QA_`, log vaciado, consola abierta).
> 3. Ejecuta las 6 capas del §3 **en orden**. Si aparece un 🔴, detente, corrígelo, y retoma
>    desde esa capa.
> 4. Clasifica cada hallazgo con la tabla del §4. Los 🔵 van al backlog de contenido: no los
>    resuelvas ni los inventes.
> 5. Corre la suite de humo del §6.
> 6. Escribe `docs/qa/<modulo>.md` con el formato del §8, listando **explícitamente** cada
>    ruta y cada campo que probaste.
> 7. Actualiza `docs/qa/ESTADO.md`.
> 8. Commit en la rama `qa/<modulo>`. No mergees a `main` sin mi visto bueno.
>
> Restricciones: no borres datos fuera del prefijo `QA_`; no hagas `git push`;
> no declares "verificado" con hallazgos 🔴 o 🟠 abiertos; si algo no lo pudiste probar,
> dilo en "No cubierto" en vez de omitirlo.

---

## 10. Orden sugerido de verificación

De abajo hacia arriba en dependencias, para no re-verificar lo mismo tres veces:

1. **Panel Filament** (si el CMS falla, todo lo demás hereda el bug)
2. **Ficha de tour** (el modelo más rico; destapa fallos de L2 que afectan a Home y Tours)
3. **Tours (listado)** · 4. **Reserva** (el flujo que genera dinero) · 5. **Blog**
6. **Home** (es agregador: se verifica cuando sus fuentes ya están sanas)
7. **Contacto** · 8. **Nosotros**
