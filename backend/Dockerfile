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

# PCOV: driver de cobertura de testes (desligado por padrão, ver app.ini)
RUN pecl install pcov && docker-php-ext-enable pcov

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Padrões do runtime otimizado; o docker-compose.dev.yml sobrescreve
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_PCOV_ENABLED=0 \
    COMPOSER_ALLOW_SUPERUSER=1

COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

# Dependências primeiro, para aproveitar o cache de camadas.
# As dev deps ficam na imagem: a suíte de testes roda neste container.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

# Código da aplicação + autoload otimizado (roda package:discover)
COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

# Entrypoint: espera o banco, migra + seed, cacheia config/rotas e sobe o php-fpm
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
