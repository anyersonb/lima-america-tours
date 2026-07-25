# Matriz de estado QA — Lima América Tours

Fuente de verdad del avance de verificación. Mantener actualizada (la actualiza `project-manager`).
Leyenda capas: ✅ pasa · 🔴/🟠 con hallazgos · — no corrido. Estados: PENDIENTE · EN CORRECCIÓN · VERIFICADO · VERIFICADO c/ deuda.

> Nota: la validación de 2026-07-24/25 fue **ad-hoc** (CRO + QA experto), NO bajo este protocolo formal.
> Por eso todos arrancan en PENDIENTE (protocolo). Los hallazgos ad-hoc ya corregidos están en el commit `81ba722`.

| Módulo | L1 | L2 | L3 | L4 | L5 | L6 | Estado | Reporte |
|---|---|---|---|---|---|---|---|---|
| Panel Filament | — | — | — | — | — | — | PENDIENTE | |
| Ficha de tour | — | — | — | — | — | — | PENDIENTE | |
| Tours (listado) | — | — | — | — | — | — | PENDIENTE | |
| Reserva | — | — | — | — | — | — | PENDIENTE | |
| Blog | — | — | — | — | — | — | PENDIENTE | |
| Home | — | — | — | — | — | — | PENDIENTE | |
| Contacto | — | — | — | — | — | — | PENDIENTE | |
| Nosotros | — | — | — | — | — | — | PENDIENTE | |

## Orden de verificación (§10 del protocolo)
Panel Filament → Ficha de tour → Tours → Reserva → Blog → Home → Contacto → Nosotros

## Prep de entorno pendiente antes de correr el protocolo
- [ ] Crear conexión/entorno `qa` (`.env.qa` + BD `lima_america_qa`) para `migrate:fresh --seed --env=qa` (§2).
- [ ] `tests/Feature/SmokeTest.php` en verde (lo crea `backend-laravel` en F1).
- [ ] Purga de datos de prueba heredados (LVT-*, cro.test@example.com, "Carlos Prueba", FAQ de prueba en huacachina).
