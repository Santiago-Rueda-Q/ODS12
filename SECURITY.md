# Seguridad

> **Buenas prácticas y secretos (API IA solo servidor):** [DOCUMENTACION.md](DOCUMENTACION.md)

Política práctica y superficie de ataque. **Última revisión:** 2026-05-05.

## Medidas implementadas (resumen)

| Área | Implementación |
|------|----------------|
| Autenticación | Laravel Breeze, sesión web |
| CSRF | Meta tag + cabeceras Axios (`resources/js/bootstrap.js`); middleware CSRF en rutas web |
| Proxies | `TRUSTED_PROXIES` → `trustProxies` en `bootstrap/app.php` |
| Cabeceras HTTP | `SecurityHeaders`: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`; **CSP** en producción por defecto (`config/security.php`). El front usa **JavaScript empaquetado por Vite** sin Alpine/`eval`, lo que permite políticas **sin `unsafe-eval`** si el resto de assets (CDN, inline) lo permiten — véase [FRONTEND.md](FRONTEND.md#csp-content-security-policy). |
| Posts | `throttle:create-post` — 5/min por usuario |
| Follows | `throttle:follow-toggle` — 30/min por usuario |
| Feed y social | Limitadores con nombre en `AppServiceProvider` (tabla abajo) |
| Auth (login, registro, reset) | Throttles dedicados en `routes/auth.php` |
| Perfil | `throttle:settings-write` en escritura de perfil |
| Notificaciones JSON | `NotificationApiPayload::forApi` — solo claves permitidas |
| Políticas | `PostPolicy`; comentarios con `parent_id` validado al mismo post |
| Contraseñas | Hasheadas (`User`) |

El comportamiento del feed (orden, límites de lectura) es servidor-side; el cliente solo envía parámetros validados — véase [ARCHITECTURE.md](ARCHITECTURE.md) y [BACKEND.md](BACKEND.md).

### Rate limiting por nombre (referencia)

| Nombre | Ventana | Clave | Rutas / uso |
|--------|---------|-------|-------------|
| `login` | 10/min | IP | `POST /login` |
| `register` | 5/min | IP | `POST /register` |
| `password-email` | 5/min | IP | `POST /forgot-password` |
| `password-store` | 10/min | IP | `POST /reset-password` |
| `password-update` | 10/min | usuario | `PUT /password` |
| `feed-filter` | 120/min | usuario o IP | `GET /posts/filter` |
| `tags-index` | 120/min | IP | `GET /tags` |
| `tags-search` | 90/min | IP | `GET /tags/search` |
| `comment-store` | 30/min | usuario | `POST …/comments` |
| `like-toggle` | 60/min | usuario | `POST …/likes/toggle` |
| `create-post` | 5/min | usuario | `POST /posts` |
| `follow-toggle` | 30/min | usuario | seguir / dejar de seguir |
| `notifications-api` | 90/min | usuario | `/notifications/*` |
| `profile-posts-json` | 120/min | usuario o IP | `GET /users/{username}/posts` |
| `username-check` | 30/min | usuario o IP | disponibilidad de username |
| `settings-write` | 25/min | usuario | PATCH/DELETE perfil |
| `EcoAnálisis-reanalyze` | 8/min | usuario | `POST /posts/{post}/reanalyze` |

Variables sensibles: [.env.production.example](.env.production.example); checklist: [PRODUCTION.md](PRODUCTION.md).

---

## Auditoría de riesgos (clasificación)

Criterio: impacto en **confidencialidad, integridad, disponibilidad** y coste operativo.

### Crítico — bloquearía despliegue serio si no se atiende

**Ninguno detectado** en revisión estática + tests verdes, **asumiendo** `APP_DEBUG=false`, HTTPS, sesión endurecida y secretos fuera del repo.

### Importante

| Hallazgo | Riesgo | Mitigación |
|----------|--------|------------|
| Proxies / HTTPS | IP y esquema incorrectos tras balanceador | `TRUSTED_PROXIES` en `bootstrap/app.php` |
| Notificaciones `data` | Fuga futura si se guardan claves internas | `NotificationApiPayload` — ampliar `ALLOWED_KEYS` con disciplina |
| Colas síncronas | Latencia en request | Redis + worker en prod |
| Tabla `notifications` | Crecimiento | `notifications:prune` programado ([PRODUCTION.md](PRODUCTION.md)) |

### Mejora

| Tema | Notas |
|------|-------|
| CSP / CDN | Endurecer cuando Font Awesome y fuentes estén autopublicados |
| Likes | A alto QPS valorar optimización de conteos |
| Índices `notifications` | Ajustar según queries reales en prod |

### Infraestructura (checklist mental)

| Variable | Producción |
|----------|------------|
| `APP_DEBUG` | `false` |
| `APP_ENV` | `production` |
| Sesión | `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, Redis si hay réplicas |
| HTTPS | Terminación real + proxies de confianza |

El header `X-XSS-Protection` está deprecado; la mitigación principal es **CSP** + escape en vistas.

### Correcto — mantener

- Validación de comentarios hijos al mismo post.
- Imágenes de post y avatar con reglas de tamaño/MIME.
- Escape en búsqueda `LIKE` del feed.

---

## Próximos endurecimientos (backlog)

- Políticas explícitas si se exponen editar/borrar post o moderación.
- Revisión de campos en `PostResource` para perfiles públicos.
- WAF / reglas en edge complementarias al throttle.
