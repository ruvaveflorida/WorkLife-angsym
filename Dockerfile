FROM php:8.2-cli

# Extensiones necesarias para Symfony
RUN apt-get update && apt-get install -y \
    zip unzip git libonig-dev libicu-dev \
    && docker-php-ext-install pdo_mysql intl mbstring

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

WORKDIR /app/symfony

# Instala dependencias de Symfony
RUN composer install --no-dev --optimize-autoloader

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000", "-t", "symfony/public"]