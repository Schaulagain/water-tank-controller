FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM php:8.4-cli-alpine
RUN docker-php-ext-install pcntl
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
COPY --from=assets /app/public/build ./public/build

# Placeholders so artisan can boot during the build; the real values come from compose.
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_DATABASE=/data/database.sqlite \
    PHP_CLI_SERVER_WORKERS=4 \
    REVERB_APP_ID=water \
    REVERB_APP_KEY=water-key \
    REVERB_APP_SECRET=change-me

RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover \
    && chmod +x docker/entrypoint.sh

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["serve"]
