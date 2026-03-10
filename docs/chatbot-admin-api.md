# Chatbot Admin API (WhatsApp / Python)

## Base y seguridad

- Base URL: `https://DOMINIO/api/chatbot/admin`
- Metodo de seguridad: token estatico (`CHATBOT_ADMIN_TOKEN`)
- Header permitido:
  - `X-Chatbot-Token: <TOKEN>`
  - o `Authorization: Bearer <TOKEN>`

Si el token no esta configurado en servidor, la API responde `503`.
Si el token es invalido, la API responde `401`.

---

## Endpoints disponibles

### 1) Eventos (resumen)

`GET /events`

Query params:
- `q` (opcional): filtra por nombre del evento.
- `scope` (opcional): `all | upcoming | past | today`
- `limit` (opcional): 1-200

Respuesta:
- `meta`
- `data[]` con costos, disponibilidad y ventas por evento.

---

### 2) Proximos eventos

`GET /events/upcoming`

Query params:
- `q` (opcional)
- `limit` (opcional): 1-200

---

### 3) Detalle de un evento

`GET /events/{evento_uuid}`

Respuesta:
- `event` (resumen)
- `ticket_breakdown[]`
- `registration_breakdown`

---

### 4) Resumen global de ventas

`GET /sales/overview`

Query params:
- `event_id` (opcional UUID)
- `from` (opcional, `YYYY-MM-DD`)
- `to` (opcional, `YYYY-MM-DD`)

---

### 5) Buscar ventas (tickets + registros)

`GET /sales/search`

Query params:
- `type` (opcional): `all | ticket | registration`
- `q` (opcional): busqueda libre por nombre/email/referencia.
- `name` (opcional): nombre parcial.
- `email` (opcional): email parcial.
- `event_id` (opcional UUID)
- `date` (opcional `YYYY-MM-DD`): fecha exacta de compra/registro.
- `from` + `to` (opcionales `YYYY-MM-DD`): rango.
- `limit` (opcional): 1-200

Respuesta:
- `meta`
- `filters`
- `data[]` con:
  - `sale_type`
  - `instance_id`
  - `event_id`, `event_name`
  - `concept`
  - `customer_name`, `customer_email`, `customer_phone`
  - `price`
  - `payment_method`
  - `reference`
  - `sold_at`
  - `status`
  - `pdf_url` (URL directa de reimpresion PDF para WhatsApp)

---

### 6) Ultimas ventas (contactos + precio)

`GET /sales/latest`

Query params:
- `event_id` (opcional UUID)
- `limit` (opcional, default 10, max 50)

Respuesta:
- `data[]` con `customer_name`, `customer_email`, `price`, `sold_at`, `reference`, `pdf_url`.

---

### 7) Disponibilidad para venta de taquilla

`GET /availability`

Query params:
- `event_id` (opcional UUID)
- `q` (opcional)
- `scope` (opcional): `all | upcoming | today`
- `limit` (opcional): 1-200

Respuesta:
- `data[]` por evento:
  - `tickets[]` con `id`, `unit_price`, `stock_available`, `can_sell_cash`
  - `registration` con `enabled`, `unit_price`, `slots_available`, `can_sell_cash`
  - `sellable_items[]` listo para construir carrito de venta.

---

### 8) Venta de taquilla en efectivo

`POST /taquilla/sell-cash`

Body JSON:

```json
{
  "event_id": "UUID_EVENTO",
  "buyer_name": "Nombre comprador",
  "buyer_email": "correo@dominio.com",
  "buyer_phone": "9991234567",
  "registration_form": {},
  "cart": [
    { "type": "ticket", "id": "UUID_TICKET", "qty": 2 },
    { "type": "registration", "qty": 1, "price": 1500 }
  ]
}
```

Reglas:
- `type=ticket` requiere `id`.
- `payment_method` se fuerza a efectivo (`cash`).
- valida stock de tickets y cupo de inscripciones antes de crear.

Respuesta `201`:
- `sale.reference`
- `sale.total_amount`, `sale.total_items`
- `sale.reprint_pdf_url` (para enviar PDF por WhatsApp)
- `items.tickets[]` y `items.registrations[]`

---

## Ejemplos rapidos (Python)

```python
import requests

BASE = "https://TU-DOMINIO/api/chatbot/admin"
HEADERS = {"X-Chatbot-Token": "TU_TOKEN"}

# Buscar ventas por email y fecha
r = requests.get(
    f"{BASE}/sales/search",
    headers=HEADERS,
    params={"email": "cliente@", "date": "2026-03-10", "limit": 50}
)
print(r.json())

# Ultimas 10 ventas
r = requests.get(f"{BASE}/sales/latest", headers=HEADERS)
print(r.json())

# Disponibilidad para taquilla
r = requests.get(f"{BASE}/availability", headers=HEADERS, params={"scope": "upcoming"})
print(r.json())

# Venta en efectivo
payload = {
    "event_id": "UUID_EVENTO",
    "buyer_name": "Comprador WhatsApp",
    "buyer_email": "cliente@dominio.com",
    "cart": [{"type": "ticket", "id": "UUID_TICKET", "qty": 1}]
}
r = requests.post(f"{BASE}/taquilla/sell-cash", headers=HEADERS, json=payload)
print(r.json())
```

---

## Flujo recomendado para WhatsApp

1. Consultar disponibilidad en `/availability`.
2. Construir carrito con `sellable_items`.
3. Ejecutar venta en `/taquilla/sell-cash`.
4. Tomar `sale.reprint_pdf_url` y enviarlo por WhatsApp.
5. Para reenvios posteriores, usar `/sales/search` o `/sales/latest` y extraer `pdf_url`.
