#!/bin/sh
set -e

cd /var/www/html

echo "[start] Inicializando aplicacion..."

# Crear SQLite si aplica y no existe
if [ "${DB_CONNECTION}" = "sqlite" ]; then
  mkdir -p database
  if [ ! -f database/database.sqlite ]; then
    echo "[start] Creando database/database.sqlite..."
    touch database/database.sqlite
  fi
fi

# Permisos para runtime de Laravel
echo "[start] Configurando permisos..."
chmod -R 775 database storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data database storage bootstrap/cache 2>/dev/null || true

# Forzar permisos 777 en el directorio público por si el volumen de CapRover pertenece a root
chmod -R 777 storage/app/public 2>/dev/null || true

if [ -f package.json ]; then
  NEED_ASSETS=0
  if [ ! -f public/build/manifest.json ]; then
    NEED_ASSETS=1
  fi
  case "${DOCKER_ALWAYS_BUILD_ASSETS:-}" in
    1|true|yes) NEED_ASSETS=1 ;;
  esac
  if [ "$NEED_ASSETS" = "1" ]; then
    echo "[start] Compilando assets front-end (npm ci && npm run build)..."
    npm ci --no-audit --no-fund
    npm run build
    chown -R www-data:www-data public/build 2>/dev/null || true
    chmod -R ug+rwX public/build 2>/dev/null || true
    if [ -d node_modules ]; then
      chown -R www-data:www-data node_modules 2>/dev/null || true
    fi
  else
    echo "[start] Assets: public/build/manifest.json presente (omite build; DOCKER_ALWAYS_BUILD_ASSETS=1 para forzar)."
  fi
fi

LIGHT_START=""
case "${DOCKER_LIGHT_START:-}" in
  1|true|yes) LIGHT_START=1 ;;
  0|false|no) LIGHT_START=0 ;;
esac
if [ -z "${LIGHT_START}" ]; then
  if [ "${APP_ENV:-}" = "local" ]; then
    LIGHT_START=1
  else
    LIGHT_START=0
  fi
fi

if [ -f artisan ]; then
  if [ "${LIGHT_START}" = "1" ]; then
    echo "[start] Modo desarrollo (DOCKER_LIGHT_START / APP_ENV=local): sin config:route:view cache en arranque."
    php artisan migrate --force

    if [ "${DOCKER_RUN_SEED:-}" = "1" ]; then
      echo "[start] DOCKER_RUN_SEED=1: ejecutando seeders..."
      php artisan db:seed --force || true
    fi
  else
    echo "[start] Modo arranque completo: limpiando cache y regenerando..."
    php artisan optimize:clear || true

    echo "[start] Generando caches..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo "[start] Ejecutando migraciones..."
    php artisan migrate --force

    echo "[start] Ejecutando seeders (catálogo: países y tags; usuarios existentes no se borran)..."
    php artisan db:seed --force
  fi

  php artisan storage:link --force >/dev/null 2>&1 || true
fi

echo "[start] App lista. Iniciando supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
