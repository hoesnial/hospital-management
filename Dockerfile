FROM php:8.3-fpm-alpine AS build

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    curl-dev \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    zip \
    gd \
    bcmath \
    exif \
    pcntl

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader --no-scripts

FROM php:8.3-fpm-alpine AS app

RUN addgroup -g 1000 -S appgroup \
    && adduser -u 1000 -S appuser -G appgroup

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    curl-dev \
    nginx \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    zip \
    gd \
    bcmath \
    exif \
    pcntl

RUN pecl install redis && docker-php-ext-enable redis

RUN mkdir -p /var/log/supervisor /var/log/nginx /var/www/html

WORKDIR /var/www/html

COPY --from=build --chown=appuser:appgroup /app/vendor /var/www/html/vendor

COPY --chown=appuser:appgroup . .

RUN rm -rf node_modules .env .env.example \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R appuser:appgroup storage bootstrap/cache public/images

COPY deploy/docker/php.ini $PHP_INI_DIR/conf.d/99-security.ini
COPY deploy/docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY deploy/docker/nginx.conf /etc/nginx/nginx.conf
COPY deploy/docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY deploy/docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

EXPOSE 80 443

USER appuser

ENTRYPOINT ["/entrypoint.sh"]
