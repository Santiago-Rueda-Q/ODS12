FROM node:22-bookworm-slim AS assets
WORKDIR /app

COPY package.json ./
RUN npm install

COPY . .
ENV NODE_ENV=production
RUN npm run build

FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    ca-certificates \
    zip \
    unzip \
    nodejs \
    npm \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    nginx \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    intl \
    opcache \
    zip \
    gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/local.ini $PHP_INI_DIR/conf.d/99-local.ini
COPY docker/php/docker-app-entrypoint.sh /usr/local/bin/docker-app-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-app-entrypoint.sh
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

COPY docker/nginx/web.conf /etc/nginx/sites-available/laravel
RUN ln -sf /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/laravel \
    && rm -f /etc/nginx/sites-enabled/default

COPY docker/php/supervisord-laravel.conf /etc/supervisor/conf.d/laravel.conf

WORKDIR /var/www/html

COPY . .

COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/app/public storage/logs storage/framework/sessions storage/framework/views storage/framework/cache/data bootstrap/cache

RUN cp .env.example .env \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && rm -f .env \
    && chown -R www-data:www-data storage bootstrap/cache vendor database public/build \
    && chmod -R ug+rwX database

RUN nginx -t

ENV SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync

EXPOSE 80 9090

ENTRYPOINT ["/usr/local/bin/docker-app-entrypoint.sh"]
CMD ["/start.sh"]