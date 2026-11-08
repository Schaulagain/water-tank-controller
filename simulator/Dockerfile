FROM php:8.4-cli-alpine

RUN docker-php-ext-install pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /sim
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

COPY src ./src
COPY bin ./bin

CMD ["php", "bin/simulate.php"]
