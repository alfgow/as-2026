# Autenticación de la API

## 1. Login (POST /api/auth/login)
El login autentica a los clientes de automatización (n8n, scripts internos, etc.) mediante credenciales emitidas por el equipo de plataforma. La petición debe enviarse a `POST /api/auth/login` con payload JSON usando `Content-Type: application/json`.

### Payload requerido
| Campo | Tipo | Obligatorio | Descripción |
|-------|------|-------------|-------------|
| `client_id` | string | Sí | Identificador único entregado por plataforma. |
| `client_secret` | string | Sí | Secreto asociado al `client_id`, almacenado de forma segura en el orquestador. |
| `audience` | string | Sí | Valor fijo `n8n-integrations` para validar el consumo correcto de la API. |
| `scopes` | array<string> | Opcional | Lista de scopes solicitados; si se omite se asigna el mínimo necesario. |

### Ejemplo JSON
```json
{
  "client_id": "n8n-staging",
  "client_secret": "s3cr3t",
  "audience": "n8n-integrations",
  "scopes": [
    "tenants.read",
    "policies.read"
  ]
}
```

### Respuesta
- **200 OK:** Devuelve `access_token`, `refresh_token`, `token_type` (Bearer), `expires_in` (segundos) y el `jti` asociado para auditoría.
- **401 Unauthorized:** Credenciales inválidas o cliente suspendido.

## 2. Refresh (POST /api/auth/refresh)
`POST /api/auth/refresh` permite obtener un nuevo `access_token` cuando el anterior expira sin pedir credenciales otra vez.

### Requisitos
1. **Token refresh válido:** Se debe incluir el `refresh_token` vigente en el cuerpo.
2. **Cabeceras obligatorias:**
   - `Authorization: Bearer <access_token_expirado_o_por_expirar>`
   - `Content-Type: application/json`
3. **Uso único:** Cada `refresh_token` se consume una sola vez; la respuesta siempre regresa un par nuevo (`access_token` y `refresh_token`).

### Payload mínimo
```json
{
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

### Respuesta
- **200 OK:** Nuevo par de tokens con `jti` actualizado.
- **401 Unauthorized:** Token vencido, revocado o cabeceras faltantes.

## 3. Revocación
Los tokens pueden invalidarse de dos maneras complementarias:

1. **Por `jti`:** Cada `access_token` y `refresh_token` incluye un identificador único (`jti`). Si un cliente se compromete, se registra el `jti` en la lista de revocados para bloquear su uso inmediato. Esta acción es granular y no afecta otros clientes.
2. **Rotando el `secret`:** Para eventos críticos (por ejemplo, filtración de múltiples tokens) se debe rotar el `client_secret` y el `JWT_SIGNING_SECRET`. Esto invalida cualquier token firmado con la llave anterior, obligando a los clientes a solicitar credenciales nuevas.

Recomendaciones: documentar la razón de la revocación, registrar fecha/hora y notificar a los responsables del flujo automatizado.

## 4. Headers estándar
Todas las peticiones a la API deben incluir los encabezados siguientes:

| Header | Descripción |
|--------|-------------|
| `Authorization` | Obligatorio en endpoints protegidos. Formato `Bearer <access_token>`. |
| `Content-Type` | Para peticiones con cuerpo JSON debe ser `application/json; charset=utf-8`. |
| `Accept` | Recomendada: `application/json` para respuestas consistentes. |
| `Idempotency-Key` | Opcional en operaciones `POST` y `PUT`; ayuda a evitar duplicados (especialmente en pagos y creación de inquilinos). |

## 5. Scopes disponibles
La siguiente tabla describe los scopes emitidos y los endpoints que los requieren. Todos los clientes deben solicitar únicamente los scopes mínimos necesarios.

| Scope | Descripción | Endpoints |
|-------|-------------|-----------|
| `auth.tokens` | Permite refrescar y revocar tokens. | `POST /api/auth/refresh`, `POST /api/auth/revoke` |
| `tenants.read` | Lectura de información de inquilinos. | `GET /api/v1/tenants` |
| `tenants.write` | Alta o actualización de inquilinos. | `POST /api/v1/tenants` |
| `policies.read` | Consulta de pólizas y relaciones. | `GET /api/v1/policies/{id}` |
| `payments.write` | Registro de pagos ligados a pólizas. | `POST /api/v1/policies/{id}/payments` |
| `documents.read` | Acceso a URLs firmadas de documentos. | `GET /api/v1/documents/{uuid}` |
| `webhooks.ingest` | Envío de eventos externos. | `POST /api/v1/events/ingress` |

> Nota: Para flujos que requieren múltiples scopes (por ejemplo, leer pólizas y registrar pagos) deben solicitar ambos (`policies.read`, `payments.write`) en el login inicial para evitar errores `403 Forbidden`.
