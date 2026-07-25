# Delegación del protocolo QA — quién hace qué

Complemento de `docs/qa/PROTOCOLO.md`. Guardar como `docs/qa/DELEGACION.md`.
Aquí se define **qué agente ejecuta cada capa**, en qué orden, y qué archivo entrega.

---

## 1. Reglas de reparto (las tres que evitan el caos)

**R1 — El que construye no aprueba.** `backend-laravel` y `maquetador-frontend` construyen
y corrigen. `cro-validator`, `security-engineer` y `client-validator` verifican. Nunca al revés:
un validador que encuentra un bug lo **reporta**, no lo arregla.

**R2 — Token del navegador.** Chrome MCP es uno solo. Solo un agente lo tiene a la vez y el
orden es fijo: `cro-validator` → `maquetador-frontend` → `client-validator`.
`backend-laravel`, `security-engineer` y `Explore` trabajan en consola/código, sin navegador.

**R3 — Carlos orquesta, no verifica.** `project-manager` reparte, cobra entregables, actualiza
`ESTADO.md` y hace el commit. No emite veredictos de calidad.

---

## 2. Matriz capa → agente

| Capa | Qué se revisa | Agente responsable | Navegador | Bloqueante |
|---|---|---|---|---|
| **L0** | Inventario del módulo (rutas, Resource, campos, relaciones) | `Explore` | No | No |
| **L1** | Render, 500s, logs, assets 404, código sucio | `backend-laravel` | No | Sí |
| **L2** | CMS ↔ front, CRUD campo por campo, sincronía | `cro-validator` | **Sí** | Sí |
| **L3** | Estados de datos (0/1/muchos/hostiles/404) | `cro-validator` | **Sí** | Sí |
| **L4** | Flujos: formularios, filtros, reserva → WhatsApp | `cro-validator` | **Sí** | Sí |
| **L5a** | CRO: jerarquía, CTA, copy, precios | `cro-validator` | **Sí** | Sí |
| **L5b** | Responsive 375/768/1440, overlaps, pixel-perfect | `maquetador-frontend` | **Sí** | Sí |
| **L6a** | Suite de humo / regresión (Pest) | `backend-laravel` | No | Sí |
| **L6b** | OWASP, secretos, permisos, dependencias | `security-engineer` | No | Sí |
| **Gate** | Validación de negocio contra el brief | `client-validator` | **Sí** | Sí (último OK) |

`Plan` entra solo si un hallazgo exige rediseño (no para QA de rutina).
`general-purpose` y `claude-code-guide` quedan fuera del ciclo.

---

## 3. Pipeline por módulo (secuencial, por el navegador compartido)

```
Carlos abre la tarea del módulo
  │
  ├─ F0  Explore ............ inventario         → docs/qa/inventarios/<mod>.md
  ├─ F1  backend-laravel .... L1 + L6a           → verde o lista de 🔴
  │        └─ si hay 🔴 → corrige aquí mismo y repite F1
  ├─ F2  cro-validator ...... L2·L3·L4·L5a  🔒   → docs/qa/<mod>.md   [BLOQUEANTE]
  ├─ F3  maquetador ......... L5b           🔒   → sección "visual" del mismo reporte
  ├─ F4  corrección ......... backend (datos/lógica) → maquetador (visual)
  ├─ F5  cro-validator ...... re-verifica SOLO lo fallado + humo  🔒
  ├─ F6  security-engineer .. L6b                → docs/qa/seguridad-<mod>.md
  ├─ F7  client-validator ... gate de negocio 🔒 → veredicto en lenguaje de negocio
  └─ Carlos cierra: ESTADO.md + commit en qa/<modulo>
```

🔒 = usa el navegador. Nunca dos 🔒 a la vez.

**Contrato de handoff**: cada fase lee el entregable de la anterior y escribe el suyo antes de
devolver el turno. Si una fase no produce archivo, la siguiente no arranca — así nadie
re-descubre lo que otro ya encontró.

---

## 4. Ruteo de hallazgos

| Nivel | A quién va |
|---|---|
| 🔴 🟠 de datos, CMS, lógica, validaciones | `backend-laravel` |
| 🔴 🟠 de layout, responsive, overlap, estilos | `maquetador-frontend` |
| 🟠 🔴 de seguridad | `security-engineer` diagnostica, `backend-laravel` corrige |
| 🟡 menores | backlog, se agrupan y se corrigen al final del módulo |
| 🔵 contenido | `client-validator` los consolida y te los presenta a ti. **Ningún agente los inventa.** |

---

## 5. Intensidad por módulo (no todos merecen el pipeline completo)

Correr las 8 fases en 7 módulos es carísimo. Reparto propuesto:

| Módulo | Pipeline | Motivo |
|---|---|---|
| Panel Filament | **Completo** | si el CMS falla, todo lo demás hereda el bug |
| Ficha de tour | **Completo** | modelo más rico; destapa fallos que afectan Home y Tours |
| Reserva | **Completo** | es el flujo que genera dinero + formulario expuesto |
| Tours (listado) | Sin security por módulo | solo lectura, filtros |
| Blog | Sin security por módulo | solo lectura |
| Home | Sin security por módulo | agregador: se verifica cuando sus fuentes están sanas |
| Contacto | Ligero + security (tiene formulario) | superficie pequeña, pero recibe input |
| Nosotros | Ligero (F0·F1·F2·F3) | estático |

Y **una auditoría global de `security-engineer` antes del deploy**, cubra o no cada módulo.

---

## 6. Prompts listos para pegar

### Carlos (arranque del módulo)
> Carlos, abre la verificación del módulo `<NOMBRE>` siguiendo `docs/qa/PROTOCOLO.md` y
> `docs/qa/DELEGACION.md`. Respeta el pipeline del §3 y la regla del token de navegador (§1 R2).
> Reparte fase por fase, exige el entregable de cada una antes de pasar a la siguiente, y
> repórtame al cerrar: estado del módulo, hallazgos por nivel, y el backlog 🔵.
> No mergees a `main` ni hagas push sin mi visto bueno.

### Explore (F0)
> Solo lectura. Inventaría el módulo `<NOMBRE>`: rutas, controllers, vistas Blade, modelos,
> Filament Resources y **la lista completa de campos** de cada Resource con su tipo y si es
> requerido. Escribe `docs/qa/inventarios/<mod>.md`. No edites nada.

### backend-laravel (F1 + L6a)
> Ejecuta L1 y L6a del protocolo sobre `<NOMBRE>`, usando el inventario de F0. Sin navegador:
> rutas por test, `laravel.log` limpio, assets resueltos, cero `dd()`/`dump()`.
> Escribe/actualiza la suite de humo en `tests/Feature/SmokeTest.php` y déjala verde.
> Reporta 🔴 y corrígelos antes de devolver el turno.

### cro-validator (F2 + F5)
> Tienes el navegador. Ejecuta L2, L3, L4 y L5a sobre `<NOMBRE>` con el inventario de F0 como
> checklist: **cada campo listado debe quedar marcado como probado**, no vale "probé todos".
> Prefijo `QA_` en todo registro de prueba. Clasifica con el §4 del protocolo.
> Escribe `docs/qa/<mod>.md` con el formato del §8. No corrijas código: reporta.
> Eres bloqueante: si hay 🔴 o 🟠, el módulo vuelve.

### maquetador-frontend (F3)
> Recibes el navegador después del CRO. Ejecuta L5b sobre `<NOMBRE>`: 375 / 768 / 1440,
> overlaps (ojo con el FAB), scroll horizontal, texto cortado, `alt` en imágenes.
> Añade tu sección al reporte existente `docs/qa/<mod>.md`. Corrige solo lo visual.

### security-engineer (F6)
> Audita `<NOMBRE>`: OWASP Top 10 en su superficie, rutas de admin sin sesión, mass assignment,
> XSS en campos de texto rico, secretos en el repo, permisos.
> Escribe `docs/qa/seguridad-<mod>.md`. Diagnostica y prioriza; la corrección la aplica
> `backend-laravel`. Eres bloqueante antes de producción.

### client-validator (F7)
> Entra como cliente no técnico al panel y al sitio. Valida `<NOMBRE>` contra el brief en
> lenguaje de negocio: ¿se entiende, se puede reservar, se puede editar sin ayuda técnica?
> Consolida los 🔵 de contenido en `docs/qa/BACKLOG-CONTENIDO.md` sin inventar datos.
> Tu veredicto es el último OK antes de "listo para producción".

---

## 7. Modo nocturno

Encadenar módulos completos sin supervisión funciona, con dos límites:

1. **Se detiene en el primer 🔴 que no pueda corregir en su propia fase**, y espera. No sigue
   al módulo siguiente arrastrando un bloqueante.
2. **No corre F7 (`client-validator`) de noche.** El gate de negocio implica decisiones tuyas
   sobre contenido; que se ejecute contigo despierto evita 4 horas de trabajo sobre supuestos.

Orden nocturno recomendado: Panel → Ficha → Tours → Reserva → Blog → Home → Contacto → Nosotros.
Reporte al final: una línea por módulo con estado y conteo de hallazgos por nivel.
