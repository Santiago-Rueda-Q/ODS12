# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

## [Unreleased]

### Added

- **`docker-compose.yml`**: variable de entorno opcional **`DOCKER_ALWAYS_BUILD_ASSETS`** pasada al servicio `app` (coherente con `docker/start.sh`).
- **Moderación automática IA**: `AIService`, `AnalyzePostJob`, `PostRejectedNotification`, `PostModerationUpdatedBroadcast`, estados de post (`status`, `analysis_status`, `analysis_result`, `moderation_reason`) y soft delete en `posts`.
- **Edición reutilizable de publicaciones**: el mismo compositor Blade/JS para crear y editar (Axios), con modo `create|edit`, precarga de etiquetas y reanálisis al actualizar.
- **Accessor en Post**: `display_country` con fallback de país (tag del post -> `user.country`) para mantener bandera consistente.

### Changed

- **Documentación** (2026-05-05): revisión transversal de **todos los `.md`**: referencias cruzadas a **[DOCUMENTACION.md](DOCUMENTACION.md)** (guía técnica integral), fechas de revisión unificadas, corrección del nombre **`PostAnalysisGeneratedBroadcast`** en [BACKEND.md](BACKEND.md), tabla índice en `DOCUMENTACION.md`, [DOCKER.md](DOCKER.md) actualizado con **Node/npm en imagen**, **`docker/start.sh`** (build de assets si falta `public/build/manifest.json`, variable **`DOCKER_ALWAYS_BUILD_ASSETS`**, modo completo con **`db:seed`** de catálogo), [DATABASE.md](DATABASE.md) alineado con **`DatabaseSeeder`** (prod vs local), [ARCHITECTURE.md](ARCHITECTURE.md) con **`GET /posts/{post}/reanalyze`** (redirección anti-405).
- **UI notificaciones**: scroll del panel con track transparente (sin fondo opaco).
- **Documentación** (2026-05-04): alineación con **sin Alpine.js** (módulos vanilla en `resources/js/ui/`), **Laravel Reverb** + variables `VITE_REVERB_*`, **worker de colas en Supervisor** ([DOCKER.md](DOCKER.md)), columna **`posts.ai_analysis`**, ranking **Populares/Tendencia/mixto** con `engagementWithEcoAnálisisExpression()`, ruta **`POST /posts/{post}/reanalyze`**, broadcast **`post.analysis.generated`**, y checklist de producción (colas + broadcasting).
- **Documentación** (`README.md`, `ARCHITECTURE.md`, `BACKEND.md`, `FRONTEND.md`, `DATABASE.md`, `PERFORMANCE.md`, `SECURITY.md`, `PRODUCTION.md`): revisión completa alineada con el comportamiento real del sistema — **WallFeedService** (ramas «siguiendo», mixto 70/30 con `sort=recent`, exploración global y `sort` popular/trending), parámetros HTTP `sort` y `following`, observabilidad y Docker; referencias cruzadas sin duplicar contenido entre archivos.
- **UX del feed (documentado)**: la experiencia «tipo para ti» se explica como combinación de **FYP** (fuente del feed) + chip **Recientes** (`sort=recent`, mezcla 70/30 cuando aplica); los chips se denominan **Recientes / Populares / Tendencia** para no repetir el concepto del **FYP** en la misma pantalla.

### Added

- **Docker (desarrollo)**: arranque ligero por defecto con `APP_ENV=local` (`docker/start.sh`: sin `config:cache` / `route:cache` / `view:cache` en cada `up`); variables `DOCKER_LIGHT_START`, `DOCKER_RUN_SEED`; OPcache + realpath en `docker/php/local.ini`; guía de rendimiento en [DOCKER.md](DOCKER.md).
- **Observabilidad SRE**: canal de logs **`structured`** (JSON); eventos `OperationalLogger` (posts, auth); métricas Redis por minuto (`OperationalMetrics`: posts, requests, **5xx**); `GET /health` (DB/cache/cola, token opcional); `GET /internal/metrics`; middleware `RecordOperationalMetrics`; documentación en [PRODUCTION.md](PRODUCTION.md) y [ARCHITECTURE.md](ARCHITECTURE.md).
- **Rendimiento**: índices `posts(user_id, created_at)` y `notifications(notifiable_type, notifiable_id, read_at)`; caché de respuesta del feed de **exploración global solo para invitados** (`WALL_GUEST_FEED_CACHE_TTL`, `config/performance.php`); listado de notificaciones con columnas seleccionadas explícitamente.
- **Middleware `SecurityHeaders`**: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` y **CSP** en producción (`config/security.php`, grupo `web`).
- **Rate limiting con nombre** unificado (feed, tags, comentarios, likes, notificaciones, auth login/register/reset, actualización de contraseña, escritura de perfil); definiciones en `AppServiceProvider`.
- **`NotificationApiPayload`**: respuesta JSON de `/notifications` solo expone claves permitidas del campo `data` (contrato API frente a fugas futuras).
- **Comando `notifications:prune`**: elimina notificaciones de BD ya leídas y antiguas (opciones `--days`, `--dry-run`); programación diaria en `routes/console.php`.
- **`TRUSTED_PROXIES`**: si está definida en `.env`, configura proxies de confianza en `bootstrap/app.php` (despliegue detrás de balanceador/CDN).
- Rate limiting **`follow-toggle`**: 30 alternaciones seguir/dejar de seguir por minuto por usuario (`AppServiceProvider` + rutas `users.follow.*`).
- Rate limiting **`create-post`**: 5 creaciones de post por minuto por usuario (`POST /posts`).
- Form Request **`UserPostsRequest`**: `per_page` entre 1 y 30 y `page` ≥ 1 en `GET /users/{username}/posts`, alineado con el feed.
- Documentación **`PRODUCTION.md`** (checklist amplio), **`SECURITY.md`** (auditoría de riesgos), **`PERFORMANCE.md`**, referencia **`.env.production.example`**; actualizaciones en `ARCHITECTURE.md` y `README.md` (producción/madurez).
- Tests de validación de `per_page` en el JSON de publicaciones de perfil.

### Security

- Mitigación de spam en creación de posts y abuso de follows mediante **throttle** dedicados.
- Referencia explícita de configuración de sesión segura para producción (véase `PRODUCTION.md` y `.env.production.example`).

### Notes

- **Feed mixto 70/30**: comportamiento y limitaciones de paginación documentados en `ARCHITECTURE.md` (**Feed del muro — WallFeedService**).
- **Madurez (referencia interna)**: con checklist de `PRODUCTION.md`, rate limits actuales y tests en verde, el sistema se sitúa en torno a **8/10** para un piloto o producción pequeña; subir hacia **9+** exige operación real: TrustProxies, colas y workers, observabilidad centralizada, retención de notificaciones y hardening de cabeceras (CSP), según `SECURITY.md`.

### Documentación (histórico)

- Añadidos `ARCHITECTURE.md`, `BACKEND.md`, `FRONTEND.md`, `DATABASE.md` y este `CHANGELOG.md`.
- `README.md` orientado al producto «EcoShare» con índice de documentación.

### Auditoría técnica (2026-05-02)

Registro de hallazgos para seguimiento; no implica que todos estén resueltos en código.

#### Correcto

- Feed con eager loading y límites de página validados en `FilterPostsRequest`.
- Validación de comentarios hijos acotada al mismo post.
- Tests de características sociales y auth en verde.
- Uso de `PostResource` como formato único de serialización.
- Caché de catálogo de tags sin serializar modelos Eloquent en driver que rompería al hidratar.

#### Mejora recomendada (pendiente o parcial)

- Reducir dependencia de CDN para iconos o documentar política CSP.
- TrustProxies / retención de notificaciones / colas asíncronas en operación real (documentadas como pendientes en `SECURITY.md` y `PRODUCTION.md`).

#### Problema crítico

- Ninguno bloqueante detectado en revisión estática y ejecución de tests en el entorno local de auditoría.
