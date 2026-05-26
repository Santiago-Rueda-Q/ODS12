# Frontend — Blade, Vite y JavaScript

> **Guía integral (Echo, análisis sin polling):** [DOCUMENTACION.md](DOCUMENTACION.md)

**Última revisión:** 2026-05-05.

## Principios

- **Sin Alpine.js**: la interactividad global (menú móvil, dropdowns de cuenta, modales Breeze, panel de notificaciones, mensajes flash) vive en **`resources/js/ui/`** (vanilla ES modules): compatible con **CSP estricta** sin `unsafe-eval` (no `eval`, no `new Function()`).
- **Tailwind CSS** para layout y transiciones (`transition`, `duration`, `opacity`, `transform`).
- **Flowbite** solo como **plugin de Tailwind** para utilidades de diseño; el JS global de Flowbite **no** se importa en `app.js`.

## Empaquetado

- **Entrada:** `resources/js/app.js` importa Bootstrap (Axios), Cropper.js, CSS, inicializa módulos UI (`mobileNav`, `dropdowns`, `modals`, `flashMessages`) y hace **code splitting** por página.
- **Módulos UI** (`resources/js/ui/`):
  - `mobileNav.js` — menú fullscreen móvil, bloqueo de scroll, evento `close-mobile-menu`.
  - `dropdowns.js` — dropdown del avatar (`data-dropdown*`).
  - `modals.js` — modales (`data-modal-root`, `data-open-modal`, `data-close-modal`).
  - `flashMessages.js` — mensajes con `data-flash-auto-hide`.
- **Code splitting** por DOM:
  - `#posts-container` → `resources/js/wall.js`
  - `#post-show-page` → `post-show.js`
  - `#profile-posts-grid` → `profilePosts.js`
  - `#nav-notifications-root` → `notificationsNav.js`

## Integración Laravel

- Meta **CSRF** en layout; `resources/js/bootstrap.js` configura Axios (`X-CSRF-TOKEN`, `withCredentials`).
- **Vite:** `@vite(['resources/css/app.css', 'resources/js/app.js'])` en layouts.
- **Config inyectada:** `wallConfig`, `postShowConfig`, etc. desde controladores; rutas relativas donde aplica para cookies de sesión.

### Laravel Echo y Reverb

- **Inicialización:** `resources/js/echo.js`. Crea Echo solo si existe **`VITE_REVERB_APP_KEY`** (o fallback **`VITE_PUSHER_APP_KEY`** para Pusher/Soketi).
- Variables típicas en `.env` / build: `VITE_REVERB_*` alineadas con **`REVERB_APP_KEY`**, host y puerto del servidor WebSocket (en Docker suele usarse el puerto publicado **9090** directamente; Nginx puede hacer proxy de `/app` y `/apps` al proceso Reverb — véase [DOCKER.md](DOCKER.md)).
- **Detalle de post** (`post-show.js`): canal `post.{id}`, evento `.post.analysis.generated` para actualizar el análisis de EcoAnálisis sin polling; likes/comentarios según payloads existentes.
- **Muro** (`wall.js` / `EcoAnálisisFlip.js`): tarjeta de EcoAnálisis con estado controlado por JS (flip, reanalizar); Echo opcional si broadcasting está activo.
- **Notificaciones** (`notificationsNav.js`): `Echo.private('user.{id}')` y `.notification.created`; panel del dropdown gestionado en JS (sin Alpine); `Echo.leave` en `beforeunload`.

## Muro (`wall.js`) — estado del feed

### Dos niveles de navegación (no mezclar)

| UI | Estado JS | Efecto |
|----|-----------|--------|
| Toggle **FYP** / **Siguiendo** (`[data-navbar-feed]`) | `state.following` | Añade `following=1` a la query cuando corresponde; «Siguiendo» sin sesión redirige a login. |
| Chips **Recientes** / **Populares** / **Tendencia** (`[data-sort]`) | `state.sort` | Valores internos: `recent`, `popular`, `trending` — deben coincidir con el backend. |

Los chips **no** usan la etiqueta «Para ti» para evitar duplicar el concepto del **FYP**.

### Peticiones

- Cliente: **Axios** `GET` a la URL del feed (`config.filterUrl`, típicamente `/posts/filter`).
- Query construida en `buildQuery()`: `sort`, `following`, `search`, `page`, `per_page`.
- Al cambiar chip o toggle: `scheduleFetch()` resetea paginación (`page = 1`) y vuelve a cargar.
- Scroll infinito: solicita la página siguiente con los **mismos** parámetros de modo.

### Ayuda contextual y feedback

- Textos de ayuda bajo los chips según `state.sort` y `state.following` (`SORT_UX` / `SORT_UX_FOLLOWING_HINT`).
- La URL del navegador se sincroniza con `history.replaceState` (`syncFeedQueryParam`).

### Archivos de vista

- `resources/views/wall/index.blade.php` — barra de búsqueda, tablist de orden, franja de contexto «Orden activo».

## UX / UI global

- Layout: Tailwind; rutas de muro/perfil con tema oscuro (`bg-slate-950`).
- Open Graph en detalle de post para enlaces compartidos.

## CSP (Content Security Policy)

El frontend evita librerías que compilan expresiones en tiempo de ejecución en el HTML. La política puede mantener **`script-src 'self'`** sin **`unsafe-eval`** si los scripts van empaquetados por Vite desde el código fuente revisado.

Detalle de cabeceras: [SECURITY.md](SECURITY.md).

## Hallazgos y mejoras opcionales

| Tema | Nota |
|------|------|
| CDN (Font Awesome) | Latencia y CSP; opción autopublicar en `public/`. |
| Accesibilidad | `aria-expanded`, `aria-controls`, roles en menús y modales; revisión sistemática de foco/teclado. |

## Referencias

- Comportamiento servidor del feed: [ARCHITECTURE.md](ARCHITECTURE.md).
- Seguridad CSRF y CSP: [SECURITY.md](SECURITY.md).
- Docker y Echo/Reverb: [DOCKER.md](DOCKER.md).
