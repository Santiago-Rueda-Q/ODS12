# Documentación técnica — EcoShare

Documento orientado a desarrolladores y evaluación académica. Describe el estado **real** del sistema en el repositorio (Laravel, Blade, colas, IA, Docker).

**Revisión documental:** 2026-05-05.

## Índice de documentación del repositorio

| Archivo | Enfoque |
|---------|---------|
| **DOCUMENTACION.md** (este) | Visión integral: arquitectura, flujo IA → BD → frontend, colas, modelo `posts.ai_analysis`, broadcasting. |
| [README.md](README.md) | Stack, puesta en marcha, índice de docs, feed (tabla resumen). |
| [ARCHITECTURE.md](ARCHITECTURE.md) | WallFeedService, FYP/Siguiendo, `sort`, mixto 70/30, rutas, IA en una sección, tiempo real. |
| [BACKEND.md](BACKEND.md) | Controladores, requests, `EcoAnálisisAiAnalysisService`, jobs, policies, broadcasting detallado. |
| [FRONTEND.md](FRONTEND.md) | Vite, módulos `resources/js/ui`, Echo, `wall.js`, EcoAnálisis flip, CSP. |
| [DATABASE.md](DATABASE.md) | Tablas, pivotes, migraciones, `ai_analysis`, índices. |
| [DOCKER.md](DOCKER.md) | Compose, Supervisor, Reverb, worker de colas, **build de assets en arranque**, variables `DOCKER_*`. |
| [PRODUCTION.md](PRODUCTION.md) | Checklist despliegue, Redis, colas, salud, métricas, scheduler. |
| [SECURITY.md](SECURITY.md) | CSRF, CSP, throttles, auditoría de riesgos. |
| [PERFORMANCE.md](PERFORMANCE.md) | Cuellos de botella feed, caché invitados, índices. |
| [CHANGELOG.md](CHANGELOG.md) | Historial de cambios documentados y técnicos. |

---

## 1. Introducción del sistema

### Propósito

**EcoShare** es una plataforma tipo red social donde los usuarios publican **EcoAnálisiss**: combinaciones de práctica y impacto descritas en texto (y opcionalmente imagen), enmarcadas en **experiencias culturales** y personales. El proyecto surge en el contexto COIL entre México y Colombia, priorizando intercambio cultural además de contenido sostenible.

### Problema que resuelve

Centraliza la creación, descubrimiento e interacción (likes, comentarios, feed con variantes de ordenación) alrededor de relatos de EcoAnálisis. Un diferenciador técnico es el **análisis asistido por IA** del texto del usuario: resume EcoAnálisis en dimensiones estructuradas y una **puntuación**, persistidas en servidor para no repetir llamadas costosas ni exponer claves al cliente.

### Enfoque cultural y tecnológico

- **Cultural:** descripciones libres, etiquetas de catálogo (país, tipo de práctica, impacto, experiencia) y perfiles con preferencias.
- **Tecnológico:** backend monolítico Laravel; UI con Blade + Tailwind + módulos JavaScript; persistencia relacional; colas para IA; **WebSockets (Reverb / compatible Pusher)** para notificar al cliente cuando el análisis está listo.

---

## 2. Arquitectura general

### Descripción

El sistema sigue el patrón **MVC** de Laravel: controladores delgados, **Form Requests** de validación, **Policies** de autorización, **Eloquent** para acceso a datos. La generación de análisis **no** se ejecuta en la petición HTTP de creación del post: se **encola** un `Job` que invoca un **servicio HTTP** (`EcoAnálisisAiAnalysisService`) contra una API estilo **OpenAI** (`POST …/chat/completions`). El resultado se guarda en **`posts.ai_analysis`** (JSON). Opcionalmente se **broadcast** un evento para actualizar la UI sin polling.

### Diagrama lógico (texto)

```
[Cliente web]
    │  HTTPS: crear post (JSON multipart), ver post, comentar, like
    ▼
[Laravel HTTP] — PostController, políticas, StorePostRequest
    │  Transacción: insert post, sync tags(pivot), dispatch Job tras commit
    ▼
[Queue worker] — GeneratePostAnalysisJob
    │  Lee Post → llama EcoAnálisisAiAnalysisService → HTTP al proveedor IA
    │  Valida payload → persiste ai_analysis → broadcast (si broadcasting ≠ null)
    ▼
[MySQL] — posts.ai_analysis JSON
    │
[Echo/Reverb] — canal post.{id}, evento post.analysis.generated
    ▼
[Cliente] — EcoAnálisisFlip.js escucha y repinta el panel de análisis
```

### Relación entre componentes

| Componente | Rol |
|------------|-----|
| `PostController::store` | Crea post, sincroniza etiquetas, despacha `GeneratePostAnalysisJob::dispatch(...)->afterCommit()`. |
| `GeneratePostAnalysisJob` | Implementa `ShouldQueue`; orquesta llamada al servicio, validación, persistencia y broadcast. |
| `EcoAnálisisAiAnalysisService` | Único punto de integración HTTP con el proveedor; normaliza JSON; sin claves en frontend. |
| `PostAnalysisGeneratedBroadcast` | Evento `ShouldBroadcast` en canal público `post.{postId}`. |
| `resources/js/social/EcoAnálisisFlip.js` | Flip card «publicación / análisis»; suscripción Echo; estado de carga y fallback visual. |

---

## 3. Flujo de creación de post y análisis

1. **Usuario** envía `POST /posts` con título, descripción, etiquetas (`tags[]`) e imagen opcional (`StorePostRequest`).
2. **Backend** autoriza `create`, guarda `posts` (`title`, `description`, `image_path`), **asocia etiquetas** en la tabla pivot `post_tag` (`sync`).
3. **Tras commit** de BD se encola **`GeneratePostAnalysisJob`** con el `id` del post (`afterCommit()` evita jobs huérfanos si falla la transacción).
4. **Worker** ejecuta el job: carga el `Post`. Si **`ai_analysis` ya no es null**, sale sin llamar a la IA (**idempotencia** ante condiciones de carrera).
5. **`EcoAnálisisAiAnalysisService::analyzeDescription`** envía la **descripción** (preprocesada: sin HTML, longitud limitada, máximo ~1500 caracteres al prompt) al endpoint **`{base_url}/chat/completions`** con `response_format: json_object`.
6. **Normalización:** el servicio exige campos no vacíos y acota `score` entre 1 y 10. Si algo falla, devuelve `null`.
7. **Job:** Si la respuesta es válida según `isValidAnalysisPayload` (claves `historia`, `afinidad`, `equilibrio`, `recomendacion`, `score` con tipos esperados), guarda el JSON en **`posts.ai_analysis`**. Si no, persiste un **fallback** documentado (mensaje genérico, `score: 0`).
8. **Broadcast:** Si el driver de broadcasting no es `null`, emite **`PostAnalysisGeneratedBroadcast`**.
9. **Frontend:** La tarjeta del post incluye datos iniciales (`ai_analysis` puede ser `null`). Mientras tanto muestra **spinner** y texto «Generando…». Al recibir el evento WebSocket, **sustituye el HTML** del análisis sin recargar la página. Si Echo no está disponible, el usuario puede seguir viendo el estado inicial hasta recargar (comportamiento degradado).

**Reanalizar:** el autor puede `POST /posts/{post}/reanalyze` (política `update`): se pone `ai_analysis` en `null`, se vuelve a encolar el mismo job. Existe `GET` de redirección para evitar 405 por enlaces accidentales.

**Nota:** El prompt de IA usa principalmente el texto de **`description`**. Las **etiquetas** no se envían explícitamente en el cuerpo del request al modelo en `EcoAnálisisAiAnalysisService`; forman parte del contexto del post en BD y del feed.

---

## 4. Integración con IA

### Proveedor

Configurable por **variables de entorno** (`config/services.php` → `EcoAnálisis_ai`):

- `MARIDAJE_AI_API_KEY` — obligatoria para llamadas reales; si falta, el servicio devuelve `null` y el job aplica **fallback**.
- `MARIDAJE_AI_BASE_URL` — por defecto `https://api.openai.com/v1`; en `.env.example` aparece también ejemplo **DeepSeek** (`https://api.deepseek.com/v1`).
- `MARIDAJE_AI_MODEL` — p. ej. `gpt-4o-mini` o `deepseek-chat`.
- `MARIDAJE_AI_TIMEOUT` — segundos (p. ej. 90).

Cualquier proveedor **compatible con OpenAI** (`/v1/chat/completions`, Bearer token) es viable sin cambiar la firma del cliente HTTP.

### Endpoint

**`POST {base_url}/chat/completions`** con:

- `Authorization: Bearer <api_key>` (solo servidor; **nunca** expuesto al navegador).
- `model`, `temperature` (0.35), `response_format: { type: json_object }`.
- Mensajes `system` (rol sommelier, salida solo JSON UTF-8 español) y `user` (prompt con la descripción incrustada como cadena JSON citada en el texto del prompt).

### Estructura del prompt (resumen)

El usuario envía instrucciones para devolver un JSON con: **`historia`** (máx. ~80 palabras en la especificación), **`afinidad`**, **`equilibrio`**, **`recomendacion`**, **`score`** (1–10), sin inventar datos, tono técnico, límite de palabras total.

### Formato esperado y normalización

- El cuerpo de la respuesta del modelo se extrae de `choices[0].message.content`.
- Se parsea JSON; si falla, se intenta extraer el primer objeto `{…}` con regex.
- `normalizePayload` recorta longitudes y fuerza **`score`** entero en [1, 10]. Si faltan strings obligatorias, retorna `null`.

### Manejo de errores y fallback

| Situación | Comportamiento |
|-----------|----------------|
| Sin API key | `null` → job → fallback persistido. |
| Descripción vacía o &lt; 12 caracteres | `null` → fallback. |
| Error HTTP / red / JSON inválido | `null` o excepción capturada → fallback o fallback tras excepción. |
| Payload job inválido (claves/tipos) | Fallback explícito `invalid_payload`. |
| **Fallback persistido** | Texto fijo de «no se pudo generar», `score: 0`, `afinidad`/`equilibrio` pueden ser `null` en ese objeto (la UI lo contempla). |

La API key **no** aparece en logs de respuesta completos del modelo de forma descontrolada; los errores HTTP loguean un fragmento acotado del body.

---

## 5. Sistema de colas

### Uso de `ShouldQueue`

`GeneratePostAnalysisJob` implementa **`Illuminate\Contracts\Queue\ShouldQueue`**, por lo que la petición de crear post **termina rápido** y el trabajo pesado (HTTP + parsing) ocurre en **proceso worker**.

### Parámetros del job

- `$timeout = 120` segundos.
- `$tries = 3` con **backoff** `[10, 60, 120]` segundos.
- `failed()` registra error permanente en log.

### Configuración

El proyecto soporta típicamente **`QUEUE_CONNECTION=database`** o **`redis`** según `.env`. En **Docker**, Supervisor puede ejecutar `php artisan queue:work` junto a PHP-FPM, Nginx y Reverb (véase `DOCKER.md` / `docker/php/supervisord-laravel.conf`).

### Importancia para rendimiento

Sin colas, cada publicación esperaría varios segundos al proveedor IA, saturando workers HTTP y empeorando UX. La cola **desacopla** latencia percibida del tiempo del modelo.

---

## 6. Modelo de datos (posts y análisis)

Tras las migraciones consolidadas, la tabla **`posts`** incluye entre otros:

| Campo | Descripción |
|-------|-------------|
| `id` | Identificador. |
| `user_id` | Autor (FK usuarios). |
| `title` | Título del EcoAnálisis. |
| `description` | Texto principal (base del análisis IA). |
| `image_path` | Ruta en disco `public` si hay imagen. |
| `ai_analysis` | **JSON nullable** — objeto con al menos las claves usadas en UI y ranking: `historia`, `afinidad`, `equilibrio`, `recomendacion`, `score`. |
| `timestamps` | `created_at`, `updated_at`. |

Las **etiquetas** no son columnas en `posts`: relación **many-to-many** `posts` ↔ `tags` mediante tabla pivot.

Índices adicionales (p. ej. `(user_id, created_at)`) optimizan feeds.

---

## 7. Frontend y UX

### Presentación del análisis

- **`EcoAnálisisFlip.js`** envuelve la tarjeta del post en un **«flip»**: cara frontal (publicación + estadísticas + botón **Ver análisis**), cara trasera (**Análisis del EcoAnálisis** con secciones y puntuación).
- **`renderAiAnalysisSectionsHtml`** pinta bloques (historia, afinidad, equilibrio, recomendación, score). Si `ai_analysis` es ausente, muestra **spinner** y mensaje de generación en curso.
- **Score 0** se interpreta en UI como **resultado de respaldo** (banner ámbar y copia explicativa).

### Sin polling para el análisis

No hay intervalo AJAX para refrescar el análisis: se usa **Laravel Echo** sobre canal **`post.{id}`**, escuchando **`.post.analysis.generated`**. Al llegar el payload, se actualiza el DOM del slot del análisis.

### Eventos y degradación

Si `ensureEcho()` devuelve `null` (broadcasting desactivado o sin configuración cliente), no se suscribe el canal; el usuario puede **recargar** la página para ver `ai_analysis` ya persistido.

### Responsive e interacción

Estilos **Tailwind** con grillas y tipografía adaptable; botones con estados focus visibles. El autor del post puede **Analizar de nuevo** (POST reanalyze), que limpia el análisis y muestra de nuevo el estado de carga hasta nuevo evento o recarga.

---

## 8. Problemas típicos y soluciones implementadas

| Problema | Causa probable | Solución en código |
|----------|----------------|---------------------|
| Análisis no aparece o queda «cargando» | Worker de cola no ejecutándose | Ejecutar `queue:work` / Supervisor en Docker. |
| Siempre texto de respaldo | `MARIDAJE_AI_API_KEY` vacía o `config:cache` desactualizado | Revisar `.env` y `php artisan config:clear` / `config:cache`. |
| Respuesta IA no válida | JSON truncado o campos faltantes | `decodeModelJson` + validación en servicio; job aplic **`invalid_payload`** → fallback. |
| Doble análisis / carreras | Reintentos o doble dispatch | Job **sale temprano** si `ai_analysis` ya existe. |
| Fallo al broadcast | Reverb caído o credenciales Echo | Log `broadcast_failed`; **persistencia del análisis ya ocurrió** — la UI puede refrescar manualmente. |

---

## 9. Buenas prácticas implementadas

- **Separación de responsabilidades:** HTTP del proveedor solo en `EcoAnálisisAiAnalysisService`; orquestación y persistencia en el job; políticas en `PostPolicy`.
- **Jobs asíncronos** con reintentos y timeouts acordes a llamadas LLM.
- **Manejo de errores** por capas (HTTP, parseo, validación de payload) con logs estructurados y fallback explícito.
- **Secretos solo en servidor** (`MARIDAJE_AI_*`); el frontend nunca llama a la IA.
- **Persistencia** del resultado para ranking (`WallFeedService` usa `score` en JSON para ordenaciones **popular/trending**) y para evitar recomputar.

---

## 10. Posibles mejoras futuras

Ideas alineadas con el código actual, sin compromiso de roadmap:

- **Recomendaciones** entre usuarios según etiquetas o similitud de embeddings (requiere diseño de datos y privacidad).
- **Ranking** más rico combinando engagement + EcoAnálisis (ya hay base con `score`).
- **Cache** de feeds o de respuestas IA por hash de descripción (invalidación cuidadosa).
- **Sistema de seguidores** — ya existe feed «Siguiendo» y mezcla 70/30 documentada en `ARCHITECTURE.md`; podría extenderse con sugerencias.
- **Analítica** agregada (métricas ya referenciadas en `PRODUCTION.md` / `/internal/metrics`).

---

## Referencias en el repositorio

| Archivo / ruta | Contenido relacionado |
|----------------|------------------------|
| `app/Jobs/GeneratePostAnalysisJob.php` | Flujo completo del job y fallback. |
| `app/Services/EcoAnálisisAiAnalysisService.php` | Prompt, HTTP, normalización. |
| `app/Events/Broadcasting/PostAnalysisGeneratedBroadcast.php` | Contrato WebSocket. |
| `resources/js/social/EcoAnálisisFlip.js` | UX flip + Echo. |
| `config/services.php` | Claves `EcoAnálisis_ai`. |
| `database/migrations/*add_ai_analysis*` | Columna JSON. |
| `README.md` | Entrada, stack, índice de toda la documentación. |
| `ARCHITECTURE.md` | Feed, WallFeedService, rutas, broadcasting (resumen). |
| `BACKEND.md` | APIs, jobs, eventos, políticas. |
| `FRONTEND.md` | Vite, Echo, módulos UI. |
| `DATABASE.md` | Esquema, migraciones, seeders. |
| `DOCKER.md` | Compose, Supervisor, build de assets, variables `DOCKER_*`. |
| `PRODUCTION.md` | Despliegue, salud, colas, métricas. |
| `SECURITY.md` | Throttles, CSP, riesgos. |
| `PERFORMANCE.md` | Caché feed, índices, cuellos de botella. |
| `CHANGELOG.md` | Historial de cambios. |

---

*Última revisión: 2026-05-05 — análisis de EcoAnálisis vía cola, JSON en `posts.ai_analysis`, broadcasting opcional.*
