# EcoShare — desarrollo con Docker

> **Narrativa IA + colas (contexto producto):** [DOCUMENTACION.md](DOCUMENTACION.md)

Stack: **PHP 8.4 (FPM)**, **Nginx**, **MySQL 8**, **phpMyAdmin** y **Redis** (caché/colas o futuros workers). La imagen incluye **Node.js y npm** para poder ejecutar `npm ci` + `npm run build` **dentro del contenedor** cuando haga falta (p. ej. volumen que oculta `public/build`).

**Última revisión:** 2026-05-05.

## Rendimiento en desarrollo (Docker)

**Contexto típico:** desarrollo en **Windows** con Docker Desktop (motor **Linux/WSL2**). El mayor impacto en lentitud suele ser **dónde vive el código respecto al VM Linux**, no solo PHP.

### Causas frecuentes (orden de impacto habitual)

| Causa | Por qué duele | Qué hacer |
|-------|----------------|-----------|
| **Bind mount desde `C:\…` hacia Linux** | Traducción NTFS ↔ VM y miles de operaciones `stat`/lectura (`vendor`, `node_modules`) | Clonar el repo **dentro del sistema de archivos de WSL** (`\\wsl$\Ubuntu\home\tu\entre-sabores` o `~/proyecto`), no en `/mnt/c/...`. |
| **Arranque con `config:cache` + `route:cache` + `view:cache` en cada `up`** | Recompilar y escribir muchos archivos en el volumen | Por defecto, con `APP_ENV=local`, `docker/start.sh` usa **arranque ligero** (sin esas caches). Arranque completo: `DOCKER_LIGHT_START=0` en `.env` (raíz, para sustitución de Compose). |
| **Seed en cada arranque** | Migraciones + seeders repetidos | Los seeders automáticos en modo ligero solo corren si `DOCKER_RUN_SEED=1`. Para una vez: `docker compose exec app php artisan db:seed`. |
| **OPcache desactivado o mal ajustado** | PHP reparsea miles de archivos | Imagen: `docker/php/local.ini` habilita OPcache con `validate_timestamps=1` (adecuado para dev). |
| **Xdebug** | Sobrecarga notable si está cargado | Esta imagen **no** incluye Xdebug. Si lo añades, desactívalo cuando no depures. |
| **Vite (`npm run dev`) en el host** | Muchos archivos observados en disco lento | Misma regla: proyecto en disco **Linux de WSL**, no en `C:\`. |
| **`vendor` sincronizado por bind mount** | Miles de archivos | Opción avanzada: volumen nombrado solo para `vendor` (Composer solo en contenedor); mejora I/O pero el IDE en Windows puede no ver `vendor` sin truco — valorar solo si el cuello es claro. |

### Variables útiles (`.env` en la raíz del repo)

Docker Compose sustituye estas claves al leer `docker-compose.yml`:

- **`DOCKER_LIGHT_START`** — `1` = arranque rápido (sin `config:cache` / `route:cache` / `view:cache` en el `start`; solo `migrate --force`). `0` = modo producción en contenedor: `optimize:clear`, caches Laravel, `migrate`, **`db:seed`** (catálogo en prod; demos solo en `local`).
- **`DOCKER_RUN_SEED`** — `1` = ejecutar `db:seed` en arranque **solo si** el modo ligero está activo (útil la primera vez en dev).
- **`DOCKER_ALWAYS_BUILD_ASSETS`** — `1` | `true` | `yes` = forzar `npm ci && npm run build` aunque exista `public/build/manifest.json`.

### Cómo medir mejoras

- **Tiempo de primera respuesta** tras `docker compose up`: debe caer mucho al quitar caches en cada arranque (cambio ya aplicado para `APP_ENV=local`).
- **`curl -o /dev/null -s -w '%{time_total}\n' http://localhost:8080/dashboard`** (ajusta URL/puerto): comparar antes/después de mover el repo a disco WSL.
- **`docker compose logs app`** y tiempos en **Nginx access log** si los activas.
- **PHP**: si instalas Xdebug “solo cuando haga falta”, compara `php artisan about` o un request representativo.

### Redis y MySQL en desarrollo

- **Redis**: en `.env` puedes usar `REDIS_HOST=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis` para aliviar disco en sesión/caché. La imagen actual **no** incluye la extensión **phpredis**; para usar Redis como driver en Laravel hace falta añadirla al `Dockerfile` (PECL) o instalar el paquete **predis/predis** y configurar `REDIS_CLIENT=predis`.
- **MySQL**: el volumen `mysql_data` ya evita recrear datos en cada `up`. Para entornos **solo dev** se puede relajar InnoDB en el `command` del servicio (trade-off durabilidad); no está aplicado por defecto para no sorprender.

---


| Servicio     | Uso en el host (por defecto) |
|-------------|------------------------------|
| Aplicación  | <http://localhost:8080>      |
| phpMyAdmin  | <http://localhost:8081>      |
| MySQL       | `127.0.0.1:3307` (mapeo por defecto) |
| Redis       | `127.0.0.1:6380` (mapeo por defecto; en la red interna el puerto del servicio sigue siendo 6379) |
| **Reverb (WebSockets)** | Puerto host **`${REVERB_PORT_EXPOSED:-9090}`** → proceso Reverb en el contenedor (`REVERB_SERVER_PORT`, típicamente 9090). Nginx dentro del contenedor hace **proxy** de `/app/` y `/apps/` a `127.0.0.1:9090` para clientes que usan la misma origin que HTTP ([docker/nginx/web.conf](docker/nginx/web.conf)). |

## Requisitos

- Docker y Docker Compose v2
- Archivo `.env` en la raíz (a partir de `.env.example`)

## Puesta en marcha

1. **Variables de entorno** (ajusta alineando usuario/clave y `APP_KEY` si hace falta):

   ```bash
   cp .env.example .env
   # Si APP_KEY está vacía:
   php artisan key:generate
   ```

   O, solo con contenedores:

   ```bash
   docker compose run --rm app php artisan key:generate
   ```

2. Asegúrate de que en `.env` tengas credenciales coherentes con el servicio `mysql` (o usa las por defecto del ejemplo: base `entre_sabores`, usuario `entre_sabores`, clave `secret`, root `DB_ROOT_PASSWORD=root`).

   **`docker-compose.yml` fuerza `DB_CONNECTION=mysql` en el contenedor `app`** (las variables del bloque `environment` prevalecen sobre `env_file`). Así no se usa SQLite dentro de Docker, donde el archivo `database/database.sqlite` en volumen compartido suele quedar **solo lectura** para PHP-FPM y fallan login, caché (`CACHE_STORE=database`), sesión en BD, etc. Si necesitas SQLite en Docker, quita esa línea y arregla permisos del fichero y del directorio `database/` dentro del contenedor.

3. **Levantar todo**

   ```bash
   docker compose up -d --build
   ```

4. **Migraciones**

   ```bash
   docker compose exec app php artisan migrate
   ```

5. **Composer** (el primer arranque instala `vendor` si no existe; para actualizaciones:)

   ```bash
   docker compose exec app composer update
   ```

6. **Frontend (Vite):**
   - **En el host:** `npm install`, `npm run dev` (desarrollo) o `npm run build` (genera `public/build`).
   - **En el contenedor:** `docker/start.sh` ejecuta automáticamente **`npm ci && npm run build`** si falta `public/build/manifest.json` o si `DOCKER_ALWAYS_BUILD_ASSETS=1` (la imagen incluye Node/npm). Así se recupera el build cuando un bind mount tapa los assets empaquetados en la imagen.

## Variables útiles (`.env` o entorno al invocar compose)

- `DB_ROOT_PASSWORD` — clave de `root` en MySQL (debe coincidir con el `healthcheck` y con lo que uses en phpMyAdmin).
- `APP_PORT` — mapea el puerto de Nginx (por defecto 8080).
- `PMA_PORT` — phpMyAdmin (por defecto 8081).
- `DB_PORT_EXPOSED` / `REDIS_PORT_EXPOSED` — si necesitas evitar colisiones con instancias locales.
- `DOCKER_LIGHT_START`, `DOCKER_RUN_SEED`, `DOCKER_ALWAYS_BUILD_ASSETS` — ver sección *Rendimiento en desarrollo* arriba y `docker/start.sh`.

## Conexión desde el host a MySQL

- Host: `127.0.0.1`
- Puerto: el publicado (por defecto 3307).
- Usuario/contraseña: los de `DB_USERNAME` / `DB_PASSWORD` o `root` / `DB_ROOT_PASSWORD`.

Dentro de la red Docker, Laravel usa `DB_HOST=mysql` (fijado en `docker-compose`).

## Supervisor (un solo contenedor `app`)

[docker/php/supervisord-laravel.conf](docker/php/supervisord-laravel.conf) levanta, entre otros:

| Programa | Rol |
|----------|-----|
| `php-fpm` | Procesamiento PHP para Nginx. |
| `nginx` | HTTP estático y proxy a Reverb en `/app/` y `/apps/`. |
| `reverb` | `php artisan reverb:start` — servidor WebSocket interno (p. ej. puerto 9090). |
| `queue-worker` | `php artisan queue:work` (usuario `www-data`) — **necesario** si usas colas reales (`QUEUE_CONNECTION=database` o `redis`) para jobs de IA, correo y `ShouldBroadcast`. |

Para que el análisis de EcoAnálisis y los broadcasts no se queden en cola sin consumir, en `.env` del contenedor alinea `QUEUE_CONNECTION` (p. ej. `database` con la tabla `jobs` migrada) y comprueba logs: `docker compose logs -f app`.

## Escalado futuro (referencia)

- **Más capacidad de cola:** añade réplicas de `queue:work` (otro contenedor o más procesos en Supervisor) si el backlog crece; **Redis** + `QUEUE_CONNECTION=redis` es el patrón típico en producción ([PRODUCTION.md](PRODUCTION.md)).
- Usa el servicio `redis` y en `.env` define `REDIS_HOST=redis`, y opcionalmente `CACHE_STORE=redis` y `QUEUE_CONNECTION=redis` (Laravel admite Predis o extensión `phpredis` si la añades a la imagen).
- Tareas programadas: contenedor con `php artisan schedule:work` o un cron sidecar.
- Añade volúmenes o servicio de objetos (MinIO, S3) según almacenamiento de archivos.

## Nginx y PHP-FPM

Nginx y PHP-FPM van **en el mismo contenedor** (`supervisord`). `fastcgi_pass` apunta a **`127.0.0.1:9000`** (`docker/nginx/web.conf`). Si ves **502**, revisa logs (`docker compose logs app`) y que **php-fpm** esté arriba; un `docker compose restart app` suele bastar.

## Comandos frecuentes

```bash
docker compose logs -f app
docker compose exec app php artisan tinker
docker compose down
```

Para borrar la base y volver a empezar (datos de MySQL en volumen `mysql_data`):

```bash
docker compose down -v
```
