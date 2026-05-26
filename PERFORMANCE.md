# Rendimiento y escalado

> **Contexto de ranking con `score` (IA):** [DOCUMENTACION.md](DOCUMENTACION.md) · Feed detallado: [ARCHITECTURE.md](ARCHITECTURE.md#feed-del-muro-wallfeedservice).

**Última revisión:** 2026-05-05.

## Terminología UI vs backend

Los chips del muro se llaman **Recientes**, **Populares**, **Tendencia**; el backend solo entiende `sort=recent|popular|trending`. La barra **FYP / Siguiendo** controla `following`, no el nombre del chip.

## Cuellos de botella (prioridad típica)

| Área | Cuello | Cuándo duele |
|------|--------|----------------|
| Feed mixto 70/30 | Varias consultas + `OFFSET` en páginas altas | Scroll profundo |
| Feed global | `withCount` por página | Picos de lectura anónima |
| Notificaciones | Lista + `unread_count` | Muchas filas por usuario |
| Disco | `storage/app/public` sin objeto compartido | Varios nodos PHP |
| `notifications` | Crecimiento sin purga | Listados lentos |

---

## 1. Feed (`WallFeedService`)

### Modos

- **Exploración global** (`globalExploreFeed`): `simplePaginate`, columnas acotadas, filtros con subconsulta para etiquetas AND.
- **Mixto 70/30** (`mixedOrGlobalRecent`): usuario autenticado, FYP, `sort=recent`, con seguidos — dos trozos (seguidos recientes + global por **engagement + score de EcoAnálisis** en el tramo de descubrimiento) + padding opcional. Ver [ARCHITECTURE.md](ARCHITECTURE.md#feed-del-muro-wallfeedservice).
- **Siguiendo** (`followingFeed`): filtro por `follows`, mismo `applySort` según `sort`.

### Paginación conocida

En modo mixto, offsets por página degradan en profundidad. Evolución posible: cursores o ranking unificado.

### Caché

- **Invitados**, exploración global: respuesta JSON cacheada si `WALL_GUEST_FEED_CACHE_TTL > 0` (p. ej. 45 s en `config/performance.php`). Clave incluye `sort`, página, filtros y búsqueda.
- **Autenticados:** sin caché de página completa en esta capa (likes propios y contexto).

`WALL_GUEST_FEED_CACHE_TTL=0` desactiva.

### Ideas avanzadas

- Ranking unificado o columna `feed_score` mantenida por jobs.
- Contadores desnormalizados en `posts` si `withCount` deviene costoso a escala.

---

## 2. Base de datos

Índices y queries a vigilar: ver [DATABASE.md](DATABASE.md). Búsqueda `LIKE` en título/descripción/tags puede migrar a FULLTEXT o motor externo si crece el volumen.

---

## 3. Notificaciones

Listado con columnas selectas; payload API filtrado. Índice compuesto en `notifications` para lecturas por usuario y `read_at`. Comando `notifications:prune` + scheduler ([PRODUCTION.md](PRODUCTION.md)).

---

## 4. Caché y Redis (producción)

| Dato | Mecanismo |
|------|-----------|
| Catálogo de tags | `Tag::cachedCatalog()` |
| Feed invitados | TTL en `WallFeedService` |
| Sesión / cola | Redis recomendado multi-instancia |

---

## 5. Frontend

Vite con imports dinámicos; `wall.js` usa Axios. Ante 429/5xx en feed, mejoras incrementales posibles (backoff, mensajes).

---

## 6. Observabilidad

Medir p95 de `GET /posts/filter`, tamaño de JSON, slow queries en MySQL. Métricas de aplicación: [PRODUCTION.md](PRODUCTION.md).

---

## Escalabilidad sugerida (orden)

1. Redis: caché + sesión + colas.
2. Índices alineados con queries reales.
3. Caché feed invitados + ajuste TTL.
4. Búsqueda full-text o servicio externo si aplica.
5. Objeto (S3) + CDN para medios.
6. Refactor paginación del mixto si el producto exige scroll estable a gran volumen.
