FROM node:22-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        bash \
        icu-libs \
        libxml2 \
        libzip \
        nginx \
        oniguruma \
        sqlite-libs \
        supervisor \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libxml2-dev \
        libzip-dev \
        oniguruma-dev \
        sqlite-dev \
    && docker-php-ext-install dom intl mbstring opcache pdo_mysql pdo_sqlite zip \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
COPY conf/php/docker-vars.ini /usr/local/etc/php/conf.d/docker-vars.ini
COPY conf/php/www-overrides.conf /usr/local/etc/php-fpm.d/zz-overrides.conf
COPY conf/nginx/nginx-site.conf /etc/nginx/http.d/default.conf
COPY conf/supervisor/supervisord.conf /etc/supervisord.conf
COPY conf/start.sh /start.sh

WORKDIR /var/www/html
COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
        composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader \
    && chmod +x /start.sh \
    && chown -R www-data:www-data storage bootstrap/cache database

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

EXPOSE 80

CMD ["/start.sh"]
