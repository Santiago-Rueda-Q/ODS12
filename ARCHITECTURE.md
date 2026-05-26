# Arquitectura — EcoShare

> **Guía integral (IA, persistencia, UX):** [DOCUMENTACION.md](DOCUMENTACION.md)

Decisiones técnicas y estructura lógica. **Última revisión documental:** 2026-05-05.

## Visión

Aplicación web tipo red social sostenible (COIL México–Colombia): muro con feed, publicaciones con etiquetas, interacciones (likes, comentarios anidados, seguimiento) y perfiles públicos. Autenticación y ajustes de cuenta vía **Laravel Breeze**.

## Stack

| Capa | Tecnología |
|------|------------|
| Backend | Laravel 13, PHP 8.4 |
| Frontend servidor | Blade, Vite |
| Frontend cliente | JavaScript modular (`resources/js/ui/`, import dinámico), Axios, Tailwind, Cropper.js (sin Alpine.js; CSP-friendly) |
| Estilos | Tailwind CSS |
| Datos | MySQL (SQLite en tests), migraciones versionadas |

## Capas y flujo general

```
HTTP → routes/web.php (+ auth.php)
     → Middleware (web, auth, throttle)
     → Controllers (delgados)
     → Form Requests (validación de entrada)
     → Servicios (WallFeedService para el feed del muro)
     → Models / Eloquent
     → API Resources (PostResource, …)
     → JSON / Blade
```

**Separación de responsabilidades**

| Pieza | Rol |
|-------|-----|
| `WallController` | Recibe `FilterPostsRequest`, delega la respuesta JSON del feed en `WallFeedService::respond()`. |
| `FilterPostsRequest` | Valida `sort`, `following`, `page`, `per_page`, etiquetas y búsqueda. |
| `WallFeedService` | Único lugar donde se define **qué posts** salen y **en qué orden** según modo y `sort`. |
| `PostResource` | Formato estable del JSON de cada post (likes, tags, comentarios en otras rutas). |

## Feed del muro (`WallFeedService`)

### Dos ejes independientes

1. **Fuente del feed** (barra superior del layout / navbar): **FYP** vs **Siguiendo**, mapeado a `following` en la query (`boolean`).
2. **Criterio de orden / ranking** (chips bajo la búsqueda): **Recientes**, **Populares**, **Tendencia**, mapeado a `sort` ∈ `{ recent, popular, trending }`.

El backend **no** usa la etiqueta «Para ti» en código; el concepto «para ti» en producto corresponde sobre todo al **FYP** (exploración). Los chips usan **`sort`** para no duplicar ese mensaje en UI.

### Entrada HTTP relevante

- `GET /posts/filter?sort=recent|popular|trending&page=&per_page=&following=1&search=&tag_ids[]=…`

Validación: `FilterPostsRequest` (`sort` nullable, valores permitidos los tres anteriores).

### Ramas principales del servicio

Orden lógico en `respond()`:

1. Si **`following=1`** → `followingFeed()`: solo posts de usuarios seguidos; se aplica `applySort($sort)` sobre esa base. Invitado sin sesión: respuesta vacía con meta indicativa.
2. Si **no** siguiendo y **`sort === recent`** y hay **usuario autenticado** → `mixedOrGlobalRecent()` (feed inteligente; véase abajo).
3. En cualquier otro caso → `globalExploreFeed()` exploración global con filtros y `applySort($sort)` (invitados con `sort=recent` entran aquí; usuarios con `popular` o `trending` también).

### Ordenación (`applySort`)

| `sort` | Comportamiento |
|--------|----------------|
| `recent` | `latest` por `posts.created_at` (cuando no entra en la rama mixta). |
| `popular` | Ranking por **engagement + EcoAnálisis**: `likes_count * 2 + comments_count * 3` más **`score` del JSON `posts.ai_analysis` × 2** (si no hay análisis, el término IA es 0); desempate `created_at` DESC. |
| `trending` | Posts de los **últimos 30 días**, mismo criterio combinado que **Populares**. |

Los contadores vienen de `withCount`; el `score` se extrae del campo JSON con funciones según el driver SQL (MySQL en Docker/prod, SQLite en tests).

### Feed inteligente (~70 % / ~30 %)

Solo cuando: usuario autenticado, **FYP** (no «Siguiendo»), **`sort=recent`**.

- Con **al menos un seguido**: ~70 % de slots de posts recientes de seguidos + ~30 % de descubrimiento global ordenado por **engagement + EcoAnálisis** (misma fórmula que **Populares**). Implementación: dos trozos con offsets por página + fusión; meta `feed_mode`: `mixed_70_30`.
- **Sin seguidos**: lista global reciente (`feed_mode`: `global_recent_no_follows`).

**Limitaciones aceptadas:** paginación por offset en trozos separados (no una única SQL de ranking global); posible ligera irregularidad entre páginas; `has_more` heurístico. Detalle en [PERFORMANCE.md](PERFORMANCE.md).

### Meta JSON (`meta.feed_mode`)

Sirve para depuración y tests; valores típicos: `following`, `mixed_70_30`, `global_recent_no_follows`, `explore_recent`, `explore_popular`, `explore_trending`, etc.

### Caché (solo invitados)

Exploración global puede cachearse por TTL si `WALL_GUEST_FEED_CACHE_TTL > 0`; la clave incluye `sort`, página y filtros. Usuarios autenticados no usan esta caché de página completa (necesitan estado de like propio).

### API JSON emimpacto

Las rutas suelen servir Blade con config JS (`wallConfig`, …) usando **rutas relativas** (`route(..., false)`) cuando hace falta coherencia de cookies con el origen real del navegador.

### Catálogo de etiquetas

`Tag::cachedCatalog()` almacena filas como array en caché; invalidación en eventos del modelo.

## Rutas destacadas

| Ruta | Uso |
|------|-----|
| `GET /dashboard` | Muro (vista Blade + bundle `wall.js`). |
| `GET /posts/filter` | JSON del feed (throttle `feed-filter`). |
| `GET /posts/{post}` | Detalle HTML o JSON según `Accept`. |
| `POST /posts` | Crear publicación (policy + throttle `create-post`). |
| `POST /posts/{post}/reanalyze` | Reencolar análisis de EcoAnálisis (dueño, throttle `EcoAnálisis-reanalyze`). |
| `GET /posts/{post}/reanalyze` | Redirección segura (evita 405 si se abre como enlace); el cierre real del análisis es vía POST. |
| `GET /health` | Salud ampliada (DB, caché, cola); token opcional. |
| `GET /internal/metrics` | Snapshot de métricas operativas; token opcional. |

## Escalabilidad y operación

- Por defecto en `.env.example`: `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`; producción suele usar Redis para caché, sesión y colas; en **Docker** de desarrollo es habitual `QUEUE_CONNECTION=database` con **worker en Supervisor** para jobs de IA y broadcasting ([DOCKER.md](DOCKER.md)).
- Índices en follows, post_tag, likes, posts — ver [DATABASE.md](DATABASE.md).

## Análisis de EcoAnálisis (IA)

- **Almacenamiento:** columna JSON **`posts.ai_analysis`** (p. ej. `score`, texto explicativo, metadatos según el servicio).
- **Generación:** job encolado (`GeneratePostAnalysisJob`) tras crear o actualizar un post y cuando el propietario solicita **reanalizar**; al completar con datos válidos se emite **`PostAnalysisGeneratedBroadcast`** (Echo: `post.analysis.generated`). Proveedor configurable (`MARIDAJE_AI_*` en `.env`, p. ej. API compatible OpenAI/DeepSeek); sin API válida el comportamiento depende del job/servicio — ver [BACKEND.md](BACKEND.md).
- **HTTP:** `POST /posts/{post}/reanalyze` (autenticado, dueño del post, throttle `EcoAnálisis-reanalyze`) encola de nuevo el análisis.
- **Impacto en el feed:** el ranking **Populares**, **Tendencia** y el tramo ~30 % del mixto **70/30** ponderan el `score` del análisis junto al engagement (`WallFeedService::engagementWithEcoAnálisisExpression()`).

## Tiempo real (broadcasting)

Solo donde aporta UX clara: **notificaciones** (badge + toast), **likes**, **comentarios** y **análisis de EcoAnálisis listo** en la **vista de detalle de un post**. El feed del muro completo **no** se actualiza por WebSockets (sigue siendo HTTP paginado).

| Canal | Tipo | Uso |
|-------|------|-----|
| `post.{id}` | público (`Echo.channel`) | Likes, comentarios y evento `post.analysis.generated` cuando el análisis IA está disponible. |
| `user.{id}` | privado (`Echo.private`) | Notificaciones del usuario autenticado; autorización en `routes/channels.php`. |

**Privado vs público:** el canal del post es público porque cualquier visitante puede ver la página del post; no expone datos sensibles (solo conteos y el comentario nuevo ya formateado como en la API). Las notificaciones van en canal **privado** para que solo el destinatario pueda suscribirse tras `/broadcasting/auth`.

Con `BROADCAST_CONNECTION=null` no se emiten eventos; la aplicación sigue funcionando con respuestas HTTP habituales.

**Arquitectura:** el dominio emite eventos (`PostLiked`, `CommentCreated`, `NotificationRecorded`); listeners dedicados construyen los payloads `*Broadcast` y llaman a `broadcast()`. Así el HTTP no depende del proveedor WS y el contrato Echo queda acotado a `App\Events\Broadcasting\*`.

Detalle de configuración: [BACKEND.md](BACKEND.md#broadcasting-y-eventos), [FRONTEND.md](FRONTEND.md#laravel-echo-y-reverb), [PRODUCTION.md](PRODUCTION.md#tiempo-real-reverb-soketi--pusher).

## Observabilidad

- **Salud**: `GET /up`; `GET /health` (token opcional `HEALTH_CHECK_TOKEN`).
- **Logs**: canal `structured` (JSON por línea); eventos de negocio y auth (`OperationalLogger`); excepciones opcionales (`LOG_STRUCTURED_EXCEPTIONS`).
- **Métricas**: Redis por ventana de minuto cuando `CACHE_STORE=redis`; endpoint `GET /internal/metrics` con `METRICS_TOKEN`. Middleware `RecordOperationalMetrics` excluye rutas de probe.

Detalle operativo: [PRODUCTION.md](PRODUCTION.md#salud-y-observabilidad-operativa).

## Auditoría — decisiones a vigilar

| Tema | Nota |
|------|------|
| Feed mixto | Comportamiento y límites en sección **Feed del muro** y [PERFORMANCE.md](PERFORMANCE.md). |
| Políticas | `PostPolicy` para crear/actualizar posts; rutas de borrado/edición pueden no estar expuestas. |
| Migración grande | `refactor_posts_for_tags_and_comment_threads` — revisar reversibilidad en runbooks. |
