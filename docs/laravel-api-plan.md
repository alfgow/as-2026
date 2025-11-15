# Laravel API Plan for n8n Integrations

## 1. Contexto y objetivo
El backend actual en `Backend/` combina panel administrativo, vistas y lógica de negocio en un solo proyecto PHP estilo MVC, por lo que no existe una capa de servicios desacoplada. El objetivo es crear un nuevo repositorio con Laravel orientado solo a API para exponer datos y acciones de negocio hacia n8n sin depender del despliegue del panel existente.

## 2. Principios de diseño
- **Repositorio independiente:** Todo el código de la API vivirá en un repositorio nuevo (por ejemplo, `api-servicios`), con su propio versionado y pipeline de despliegue para que evolucione sin bloquear cambios del panel existente.
- **Separación de responsabilidades:** El nuevo servicio solo entregará endpoints REST; la administración seguirá en el repositorio actual.
- **Compatibilidad con la base de datos existente:** Reutilizar las tablas de MySQL ya provisionadas sin duplicar datos.
- **Orientación a automatizaciones:** Cada endpoint debe ser idempotente y con respuestas claras para facilitar los flujos en n8n.
- **Seguridad primero:** Autenticación basada en tokens, logging y límites para minimizar riesgos al exponer la API.

## 3. Arquitectura propuesta
1. **Framework y repositorio:** Inicializar un repositorio dedicado e instalar Laravel 11 con el preset API (`laravel new api-servicios --api`).
2. **Estructura de módulos:**
   - `app/Modules/Tenants` para inquilinos (`inquilinos_2025`).
   - `app/Modules/Landlords` para arrendadores.
   - `app/Modules/Policies` para pólizas e historial de pagos.
   - `app/Modules/Documents` para interacción con S3.
   - `app/Modules/Auth` para emisión y gestión de tokens.
3. **Capas:** Controladores finos, servicios para reglas de negocio, repositorios para consultas complejas y Jobs para tareas diferidas.
4. **Infraestructura compartida:**
   - Conexión MySQL reutilizando credenciales actuales.
   - AWS S3 para archivos.
   - Redis opcional para rate limiting y colas.

## 4. Modelado de datos
- Mapear cada tabla relevante a un modelo Eloquent, manteniendo nombres y llaves primarias existentes.
- Documentar relaciones detectadas en el backend actual (por ejemplo, un inquilino pertenece a una póliza, una póliza tiene muchos pagos).
- Crear form requests y recursos (`JsonResource`) para encapsular validaciones y serialización consistente.

## 5. Endpoints iniciales sugeridos
| Recurso | Endpoint | Descripción |
|---------|----------|-------------|
| Auth | `POST /api/v1/auth/token` | Genera token de acceso para n8n (Laravel Sanctum con tokens personales). |
| Inquilinos | `GET /api/v1/tenants` | Lista paginada con filtros por estatus, propiedad o fechas. |
| Inquilinos | `POST /api/v1/tenants` | Alta de inquilino desde formularios externos. |
| Pólizas | `GET /api/v1/policies/{id}` | Recupera datos completos con relaciones y pagos. |
| Pagos | `POST /api/v1/policies/{id}/payments` | Registra pagos y adjuntos. |
| Documentos | `GET /api/v1/documents/{uuid}` | Devuelve URL firmada temporal desde S3. |
| Webhooks | `POST /api/v1/events/ingress` | Recepción de eventos disparados por n8n para procesos inversos. |

## 6. Integración con n8n
- **Autenticación:** Utilizar tokens personales generados en Laravel Sanctum; almacenar el token en credenciales seguras dentro de n8n.
- **Configuración de nodos:** Proveer colección Postman/Swagger para importar en n8n y facilitar el armado de flujos.
- **Idempotencia:** Incluir encabezado `Idempotency-Key` para operaciones `POST` y `PUT`; validar en middleware y registrar claves en Redis.
- **Manejo de errores:** Respuestas JSON con código y mensaje claro (`code`, `message`, `details`) para que n8n pueda enrutar errores.
- **Rate limiting:** Establecer límites por token (ej. 60 req/min) configurables; notificar en encabezados `X-RateLimit-*`.

## 7. Seguridad y cumplimiento
- Usar HTTPS obligatorio desde el load balancer.
- Registrar auditoría básica (quién llamó, qué hizo, payload relevante) en tabla `api_logs`.
- Activar CORS solo para dominios necesarios (instancia n8n y staging).
- Configurar políticas IAM dedicadas para acceso S3 con privilegios mínimos.

## 8. Observabilidad
- Integrar Laravel Telescope solo en ambientes de desarrollo.
- En producción, usar Monolog hacia CloudWatch o ELK.
- Exponer métricas básicas (latencia, tasas de error) vía endpoint `/api/v1/health` y, si aplica, integrar con Prometheus.

## 9. Estrategia de despliegue
1. **Ambientes:** Desarrollo local con Sail, staging conectado a base de datos de pruebas, producción apuntando a la base actual.
2. **CI/CD:** Pipeline GitHub Actions o GitLab dentro del nuevo repositorio que ejecute linting (`phpcs`), migraciones en ambiente de staging y despliegue vía SSH o contenedores.
3. **Infraestructura:**
   - Opción A: Laravel Forge sobre VPS administrado.
   - Opción B: Contenedores en ECS Fargate compartiendo VPC con la base de datos.
   - Opción C: Vapor si se desea serverless y ya se usa AWS de forma intensiva.

## 10. Roadmap recomendado
1. **Semana 1:** Setup del proyecto, configuración `.env`, autenticación básica y módulo de inquilinos (lectura).
2. **Semana 2:** Endpoints de creación/actualización, documentación Swagger, módulo de pólizas y pagos.
3. **Semana 3:** Integración con S3, pruebas integrales con n8n, robustecimiento de seguridad (rate limit, auditoría).
4. **Semana 4:** Preparar pipelines, monitoreo y despliegue a producción.

## 11. Próximos pasos inmediatos
- Validar con el equipo de datos la disponibilidad de credenciales de lectura/escritura.
- Definir qué flujos n8n serán prioritarios para ajustar los endpoints iniciales.
- Recopilar ejemplos de payload actuales (de formularios o panel) para usarlos como base de las pruebas manuales.

