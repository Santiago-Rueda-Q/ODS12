# EcoShare

Red social sostenible orientada al intercambio cultural (proyecto COIL México–Colombia): publicaciones con etiquetas, muro con exploración y modo «siguiendo», likes, comentarios en hilos, perfiles públicos, notificaciones y **análisis de EcoAnálisis asistido por IA** (opcional según configuración de API). La documentación técnica está centralizada y enlazada desde **[DOCUMENTACION.md](DOCUMENTACION.md)** y los demás `.md` del repositorio (arquitectura, backend, frontend, Docker, seguridad, etc.).

## Feed del muro (lectura rápida)

| En la UI | Parámetro HTTP | Comportamiento backend (resumen) |
|----------|----------------|----------------------------------|
| **FYP** / **Siguiendo** | `following` ausente vs `following=1` | Fuente: exploración global vs solo cuentas que sigues (invitado en «Siguiendo» ve mensaje de login). Detalle en [ARCHITECTURE.md](ARCHITECTURE.md#feed-del-muro-wallfeedservice). |
| **Recientes** / **Populares** / **Tendencia** | `sort=recent` \| `popular` \| `trending` | Orden y reglas de ranking; con usuario autenticado en FYP y **Recientes**, aplica la **mezcla ~70 % seguidos + ~30 % global por engagement** si tiene seguidos. En **Populares** / **Tendencia**, si existe `ai_analysis.score`, el ranking puede ponderar engagement + EcoAnálisis (véase [ARCHITECTURE.md](ARCHITECTURE.md)). |

Los nombres de chips no coinciden literalmente con los valores de `sort` (son etiquetas de producto). El contrato de API sigue siendo **`sort` en inglés**.

## Entorno y producción

- **Local**: PHP 8.4, Composer, Node; base de datos según `.env` (MySQL recomendado alineado con Docker).
- **Producción**: seguir [PRODUCTION.md](PRODUCTION.md) (checklist) y [SECURITY.md](SECURITY.md). Referencia de variables: [.env.production.example](.env.production.example).
- **Madurez**: validaciones, policies, rate limits diferenciados, tests de características sociales; observabilidad (logs JSON, métricas Redis, `/health`, `/internal/metrics`) — ver [PRODUCTION.md](PRODUCTION.md).
- **Docker**: PHP, Nginx, MySQL, Redis, phpMyAdmin; **Supervisor** gestiona FPM, Nginx, **Laravel Reverb** (WebSockets) y **worker de colas** — [DOCKER.md](DOCKER.md).

## Stack

| Área | Tecnología |
|------|------------|
| Backend | Laravel 13, PHP 8.4 |
| Auth / scaffolding | Laravel Breeze |
| Frontend | Blade, Vite, Tailwind CSS, **JavaScript modular (vanilla, CSP-friendly; sin Alpine.js)** |
| HTTP cliente | Axios |
| Tiempo real | Laravel Echo + protocolo Pusher; **Laravel Reverb** en Docker (alternativa: Pusher Cloud / Soketi) |
| Base de datos | MySQL (desarrollo con Docker; véase abajo) |

## Documentación del repositorio

| Documento | Contenido |
|-----------|-----------|
| [DOCUMENTACION.md](DOCUMENTACION.md) | **Guía técnica integral** (arquitectura, flujo IA, colas, modelo de datos, UX, broadcasting, buenas prácticas) — lectura recomendada para entrega o revisión académica |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Decisiones de arquitectura, **WallFeedService**, feed 70/30, **IA EcoAnálisis**, broadcasting |
| [BACKEND.md](BACKEND.md) | Requests, servicios, policies, rate limiting, jobs y broadcasting |
| [FRONTEND.md](FRONTEND.md) | Vite, módulos UI (`resources/js/ui/`), Echo, muro (`wall.js`), CSP |
| [DATABASE.md](DATABASE.md) | Tablas (`posts.status`, `analysis_status`, `analysis_result`, `moderation_reason`, `ai_analysis`, soft deletes), migraciones |
| [CHANGELOG.md](CHANGELOG.md) | Historial |
| [PRODUCTION.md](PRODUCTION.md) | Despliegue, colas, Reverb, Redis, salud, métricas, backups |
| [SECURITY.md](SECURITY.md) | Superficie de seguridad, CSP |
| [PERFORMANCE.md](PERFORMANCE.md) | Feed, caché invitados, índices |
| [DOCKER.md](DOCKER.md) | Stack, Supervisor, colas, puertos |
| [.env.production.example](.env.production.example) | Variables de referencia para producción |

## Requisitos rápidos

- PHP 8.4, Composer, Node.js/npm
- Base de datos configurada en `.env` (copiar desde `.env.example`)

## Puesta en marcha (local sin Docker)

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install
npm run build   # o npm run dev durante desarrollo
php artisan serve
```

Para **colas** (`QUEUE_CONNECTION=database`), en otra terminal: `php artisan queue:work`. El script `composer run dev` incluye servidor, cola, logs y Vite según `composer.json`.

Script Composer útil: `composer setup` (instala dependencias, migraciones, build de front).

## Moderación automática (IA)

- Al crear/editar un post, el registro entra en `status=pending` y `analysis_status=pending`.
- Se encola el job de moderación (`AnalyzePostJob`) y, en paralelo, el análisis de EcoAnálisis (`GeneratePostAnalysisJob`).
- Si la moderación marca `flagged=true`, el post pasa a `rejected`, guarda `moderation_reason`/`analysis_result` y se aplica soft delete.
- Si pasa moderación, el post queda `active` con `analysis_status=completed`.
- Frontend (Blade + Axios) muestra estado **“Analizando contenido…”** y actualiza por WebSocket cuando termina.

## Pruebas

```bash
php artisan test
```

## Docker

Desarrollo con PHP, Nginx, MySQL, phpMyAdmin y Redis: consulta [DOCKER.md](DOCKER.md) y levanta el stack con `docker compose up -d` desde la raíz del proyecto.

## Licencia

MIT (plantilla Laravel); el contenido específico del proyecto EcoShare pertenece al equipo del proyecto según los acuerdos académicos aplicables.
