# syntax=docker/dockerfile:1
# Production image: Apache + PHP 8.3, assets prebuilt, one container runs web + queue worker + scheduler.

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

FROM node:22-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
# Tailwind also scans vendor/ (pagination views), so the PHP deps must be present.
COPY --from=vendor /app/vendor vendor
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources resources
RUN npm run build

FROM php:8.3-apache
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql pdo_pgsql zip bcmath intl opcache \
 && a2enmod rewrite headers remoteip \
 && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && printf '<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
 && a2enconf laravel

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor vendor
COPY --from=assets /app/public/build public/build
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod +x docker/entrypoint.sh

# The platform's load balancer terminates TLS; trust its forwarded headers.
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr TRUSTED_PROXIES=*
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s CMD curl -fsS "http://127.0.0.1:${PORT:-80}/up" || exit 1
ENTRYPOINT ["docker/entrypoint.sh"]
