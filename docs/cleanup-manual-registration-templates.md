# Limpieza de plantillas manuales de registro

Objetivo: cuando terminen los eventos de plantilla manual (`dia_padres_cumbres`, `whatsapp_direct`, `golf_team`, `cena_gala`), retirar su código sin afectar:
- venta de tickets normal
- registros con `form builder` (`registration_form_mode = builder`)

## Requisitos previos

1. Confirmar que no existan eventos activos con `registration_form_mode = manual`.
2. Confirmar que reportes históricos de eventos manuales ya no se necesiten con formato especial.
3. Respaldar base de datos antes de limpiar.

## Qué conservar

- `DirectRegistrationController::store()` para flujo directo genérico de registros sin plantilla.
- `RegistrationFormSchemaService` y flujo `builder`.
- Ruta `registration.direct.store` si habrá registros `builder` con costo `0` (registro directo sin pasarela).

## Métodos y bloques a remover

### 1) `app/Http/Controllers/DirectRegistrationController.php`

Remover:

1. Constante:
   - `WHATSAPP_GROUP_LINK`
2. En `store(Request $request, Eventos $event)`:
   - Bloque `if ($event->template_form === 'dia_padres_cumbres') { ... }`
   - Bloque `if ($event->template_form === 'whatsapp_direct') { ... }`
3. Métodos completos:
   - `storeDiaPadresCumbres(...)`
   - `storeWhatsappDirect(...)`

Conservar:

- `store(...)` flujo genérico
- `extractBaseRegistrant(...)`
- `normalizePhone(...)`
- `normalizeEmail(...)`
- `canBypassOnlineStop(...)`

### 2) `app/Services/EventReportService.php`

Remover:

1. En `buildRowsForTransaction(...)`:
   - uso de `$diaPadresSummary`
   - condición especial que asigna `record_data` desde `buildDiaPadresSummary(...)`
2. En `buildRegistrationEntries(...)`:
   - bloque que detecta `template_form === 'dia_padres_cumbres'`
   - retorno de `buildDiaPadresCumbresEntries(...)`
3. Métodos:
   - `buildDiaPadresSummary(...)`
   - `buildDiaPadresCumbresEntries(...)`

Conservar:

- flujo estándar de `registrations`, `participants`, `players` y fallback plano.

### 3) `app/Mail/DirectRegistrationMail.php`

Remover:

1. Constante:
   - `WHATSAPP_GROUP_LINK`
2. En `build()`:
   - cálculo condicional por `template_form`
   - envío de `whatsappLink` al view

Conservar:

- envío de correo genérico de confirmación.

### 4) `resources/views/emails/direct-registration.blade.php`

Remover:

1. Condición especial:
   - `$isDiaPadres`
2. Sección específica de WhatsApp/Brawl.
3. Sección de resumen específico de Día Padres.

Conservar:

- plantilla de correo genérica para registros builder.

### 5) `resources/views/events/iframe.blade.php`

Remover:

1. Includes manuales:
   - `registration-cena-gala`
   - `registration-golf-team`
   - `registration-whatsapp-direct`
   - `registration-dia-padres-cumbres`
2. Variables JS de disponibilidad especiales si ya no hay formularios manuales que las usen:
   - `window.registrationAvailability`
3. Mantener solo:
   - include `registration-builder` cuando aplique
   - flujo tickets (seat-map y ticket-selects)

### 6) `public/assets/js/iframe.js`

Remover:

1. En inicialización de stock para registro:
   - condición por `templateForm === 'golf_team'`
   - condición por `templateForm === 'whatsapp_direct'`
   - condición por `templateForm === 'dia_padres_cumbres'`
2. Si ya no se usa:
   - helper `isWhatsappDirectRegistration()`
   - referencia a ese helper en `useDirectRegistrationFlow()`

Conservar:

- `isZeroPriceRegistration()` y flujo directo genérico para builder sin costo.

### 7) `resources/views/events/create.blade.php` y `resources/views/events/edit.blade.php`

Remover del select `template_form`:

1. `golf_team`
2. `cena_gala`
3. `whatsapp_direct`
4. `dia_padres_cumbres`

Opcional recomendado:

- si ya no habrá manuales, ocultar o eliminar opción `registration_form_mode = manual` y dejar únicamente `builder`.

### 8) Parciales Blade manuales

Remover archivos:

1. `resources/views/events/partials/registration-cena-gala.blade.php`
2. `resources/views/events/partials/registration-golf-team.blade.php`
3. `resources/views/events/partials/registration-whatsapp-direct.blade.php`
4. `resources/views/events/partials/registration-dia-padres-cumbres.blade.php`

## Orden recomendado de limpieza

1. Quitar opciones manuales en `create/edit`.
2. Dejar `iframe` sirviendo solo builder/tickets.
3. Eliminar métodos template-specific del controller y mail.
4. Eliminar casos especiales del reporte.
5. Eliminar parciales blade manuales.
6. Probar:
   - compra normal de tickets
   - registro builder de pago
   - registro builder sin costo (si aplica)
   - exportación de reportes CSV.

## Riesgos si se elimina fuera de orden

1. Errores 500 por includes blade inexistentes en `iframe`.
2. Errores en reportes históricos si aún existen registros con `template_form = dia_padres_cumbres`.
3. Fallo en registro directo de builder sin costo si se elimina por completo la ruta/controlador.
