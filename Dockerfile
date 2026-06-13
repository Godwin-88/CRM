# Laravel App Dockerfile for Render
FROM php:8.4-fpm-alpine AS php

RUN apk add --no-cache \
    supervisor \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libzip-dev \
    icu-dev \
    libxml2-dev \
    linux-headers \
    postgresql-dev \
    nginx \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo \
        pdo_pgsql \
        zip \
        xml \
        dom

RUN git clone https://github.com/phpredis/phpredis.git /usr/src/php/ext/redis \
    && docker-php-ext-install redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY package.json package-lock.json ./
RUN npm ci --production=false

COPY . .

RUN composer dump-autoload --optimize \
    && npm run build \
    && rm -rf node_modules

RUN mkdir -p storage/framework/{cache,sessions,views} \
    && mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/app/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/app/supervisord.conf /etc/supervisord.conf
COPY docker/app/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]