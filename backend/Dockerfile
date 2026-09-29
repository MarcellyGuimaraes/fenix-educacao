FROM php:8.4-fpm-alpine

# System dependencies
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    sqlite-dev \
    linux-headers \
    $PHPIZE_DEPS

# PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pdo_sqlite zip intl bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis

# PCOV: driver de cobertura de testes
RUN pecl install pcov && docker-php-ext-enable pcov

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Entrypoint waits for DB, installs deps, runs migrations + seed, then starts php-fpm
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
