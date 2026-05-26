# Backend — Laravel

> **Guía integral (servicio IA, job, JSON):** [DOCUMENTACION.md](DOCUMENTACION.md)

Convenciones, puntos de extensión y contratos del feed. **Última revisión:** 2026-05-05.

## Estructura relevante

| Ruta / clase | Función |
|--------------|---------|
| `app/Http/Controllers/WallController` | Muro: vista + `FilterPostsRequest` → `WallFeedService::respond()`. |
| `app/Services/WallFeedService.php` | **Núcleo del feed**: ramas «siguiendo», mixto 70/30, exploración global; filtros y orden. |
| `app/Http/Requests/FilterPostsRequest.php` | Valida `sort` ∈ `recent`, `popular`, `trending`; `following`; `page`/`per_page`; etiquetas; búsqueda. |
| `app/Http/Resources/PostResource.php` | Formato JSON único de posts en respuestas públicas. |
| `app/Policies/PostPolicy.php` | Autorización crear/actualizar posts. |
| `app/Http/Middleware/SecurityHeaders.php` | Cabeceras + CSP (producción). |
| `config/security.php` | CSP y overrides por `.env`. |

Otros requests destacados: `StorePostRequest`, `StorePostCommentRequest`, `UserPostsRequest`, etc.

## EcoAnálisis IA (servicio y jobs)

| Pieza | Rol |
|-------|-----|
| `config/services.php` / `.env` | Claves `MARIDAJE_AI_*` (URL base, modelo, API key, timeouts, límites de reintentos). |
| `EcoAnálisisAiAnalysisService` | Llama al proveedor HTTP; devuelve estructura normalizada para guardar en `posts.ai_analysis`. |
| `GeneratePostAnalysisJob` | Encola el análisis; al terminar actualiza el post y puede emitir `PostAnalysisGeneratedBroadcast` → WebSocket (`post.analysis.generated`). |
| `PostController::reanalyze` | `POST /posts/{post}/reanalyze` — solo el dueño; throttle **`EcoAnálisis-reanalyze`** (8/min por usuario). |

Sin worker de colas activo, los jobs de análisis y los `ShouldBroadcast` no se procesan — ver [DOCKER.md](DOCKER.md#supervisor-un-solo-contenedor-app) y [PRODUCTION.md](PRODUCTION.md#colas).

## Moderación IA de contenido (automática)

| Pieza | Rol |
|-------|-----|
| `AIService` | Construye prompt de moderación, llama API IA y normaliza JSON (`flagged`, `reasons`, `confidence`, `summary`). |
| `AnalyzePostJob` | Flujo asíncrono de moderación: `pending -> processing -> completed/failed`; puede marcar `status=rejected` y aplicar soft delete. |
| `PostRejectedNotification` | Notifica al autor cuando su post se retira automáticamente por políticas. |
| `PostModerationUpdatedBroadcast` | Evento WS (`post.moderation.updated`) para actualizar UI sin recarga. |

Estados usados en `posts`:

- `status`: `pending`, `active`, `rejected`
- `analysis_status`: `pending`, `processing`, `completed`, `failed`
- `analysis_result` (json), `moderation_reason` (json nullable), `deleted_at` (soft deletes)

## Feed HTTP — contrato

- **Endpoint:** `GET /posts/filter`
- **Throttle:** `feed-filter` (véase [SECURITY.md](SECURITY.md#rate-limiting-por-nombre-referencia)).
- **Parámetros clave:**
  - `sort` — orden/ranking (ver [ARCHITECTURE.md](ARCHITECTURE.md#feed-del-muro-wallfeedservice)).
  - `following=1` — solo cuentas seguidas (requiere sesión para contenido).
  - `page`, `per_page` (límite superior acotado en el Form Request).

La lógica **no** debe duplicarse en el controlador: mantenerla en `WallFeedService`.

## Rate limiting

Los limitadores con nombre se registran en `AppServiceProvider` y se asocian a rutas en `routes/web.php` / `routes/auth.php`. Tabla completa: [SECURITY.md](SECURITY.md#rate-limiting-por-nombre-referencia).

Ejemplos relevantes al dominio social:

- `create-post` — creación de publicaciones.
- `feed-filter` — lectura del muro.
- `follow-toggle` — seguir / dejar de seguir.
- `comment-store`, `like-toggle`, `notifications-api`, etc.

## Policies y autorización

- Posts: `PostPolicy` en rutas que lo declaren.
- Comentarios: validación de `parent_id` acotada al mismo post en `StorePostCommentRequest`.
- Edición de posts: `PostController::update` usa `authorize('update', $post)` (solo dueño).

## Edición de posts (create vs update)

- Ruta creación: `POST /posts`
- Ruta edición: `PATCH /posts/{post}` (y `PUT /posts/{post}` compatible)
- Request de edición: `UpdatePostRequest` (título, descripción, etiquetas y campos opcionales).
- Tras editar: se invalida análisis previo y se reenfila moderación + EcoAnálisis.

## Buenas prácticas observadas

- **Eager loading** en el feed: usuario, etiquetas con columnas acotadas, `withCount` para likes/comentarios, `withExists` para «like propio» si hay sesión.
- **Búsqueda:** comodines escapados en `LIKE`.
- **Etiquetas múltiples:** subconsulta con `HAVING COUNT(DISTINCT …) = N`.
- **Notificaciones API:** `NotificationApiPayload::forApi` filtra claves del JSON `data`.

## Comandos útiles

```bash
composer dev    # servidor + cola + vite (según composer.json)
php artisan test
composer lint / composer format   # Laravel Pint
```

## Broadcasting y eventos

### Capas

| Capa | Rol |
|------|-----|
| **Eventos de dominio** | `PostLiked`, `CommentCreated`, `NotificationRecorded` — solo datos de negocio; los despachan controladores u observers (`::dispatch(...)`). |
| **Listeners** (`App\Listeners\Broadcasting\`) | Traducen dominio → WS: construyen `App\Events\Broadcasting\*Broadcast` (`ShouldBroadcast`), `broadcast(...)->toOthers()` donde aplica, métricas y log `broadcast.emitted`. |
| **Payloads WS** | `PostLikedBroadcast`, `CommentCreatedBroadcast`, `NotificationCreatedBroadcast` — contrato estable con Echo (nombres de canal y `broadcastAs`). |

**Ventajas:** los controladores no conocen Soketi/Pusher; los tests pueden fijar eventos de dominio con `Event::fake()`; los payloads WS se prueban aparte (`tests/Unit/BroadcastingPayloadTest.php`).

- **Configuración:** `config/broadcasting.php`; variable `BROADCAST_CONNECTION` (`null` | `pusher` | `reverb` | …). Con **Laravel Reverb** en Docker, el cliente Echo usa el protocolo Pusher y variables `VITE_REVERB_*` alineadas con `REVERB_APP_KEY` y el host/puerto del WS. Con `null`, los listeners no llaman a `broadcast()` (el contador `users.unread_notifications_count` sí se mantiene).
- **Colas:** los `*Broadcast` implementan `ShouldBroadcast` (emisión **encolada**). Con `QUEUE_CONNECTION=redis` o **`database`**, hace falta **worker** (`php artisan queue:work …`). En Docker, Supervisor ya incluye `queue-worker` — asegúrate de que `QUEUE_CONNECTION` no sea `sync` si quieres IA y WS en segundo plano. Con `sync` (tests), el job corre en el mismo request.
- **Fallos:** tabla `failed_jobs` migrada; revisar con `php artisan queue:failed`, reintentar con `queue:retry`.
- **Contador no leídas:** columna `users.unread_notifications_count` — incremento en `DatabaseNotificationObserver`, decremento al marcar una como leída, reset en «marcar todas». Reparar drift: `php artisan notifications:sync-unread-counts`.
- **Rutas:** `BroadcastServiceProvider` registra `POST /broadcasting/auth` con middleware `web` + `auth`. Canales en `routes/channels.php`:
  - `user.{userId}` — solo el usuario cuyo `id` coincide sesión ↔ parámetro.
- **Registro:** `AppServiceProvider` enlaza dominio → listeners (`Event::listen`).
- **Observabilidad:** `OperationalLogger::broadcastEmitted` (canal `structured`, clave `broadcast.emitted`) y `OperationalMetrics::incrementBroadcastsEmitted` (Redis por minuto si `CACHE_STORE=redis`).
- **Payloads WS** (`ShouldBroadcast`):
  - `PostLikedBroadcast` → canal `post.{postId}`, `post.like.updated`.
  - `CommentCreatedBroadcast` → `post.{postId}`, `post.comment.created`.
  - `PostAnalysisGeneratedBroadcast` → `post.{postId}`, `post.analysis.generated` (payload con `ai_analysis`).
  - `NotificationCreatedBroadcast` → `PrivateChannel('user.{userId}')`, `notification.created`.
- **Disparo dominio:** `PostLikeController::toggle`, `PostCommentController::store`, `DatabaseNotificationObserver::created`.
- **`toOthers()`** en like y comentario: el cliente envía `X-Socket-Id` (Axios); la pestaña que originó la acción no recibe el mismo evento por WS.

## Referencias cruzadas

- Diseño del feed y ramas: [ARCHITECTURE.md](ARCHITECTURE.md).
- Rendimiento y paginación: [PERFORMANCE.md](PERFORMANCE.md).
- Seguridad y throttles: [SECURITY.md](SECURITY.md).
