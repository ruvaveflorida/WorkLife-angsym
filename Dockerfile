# Usa PHP 8.2 con CLI y extensiones comunes
FROM php:8.2-cli

# Instala extensiones necesarias
RUN apt-get update && apt-get install -y \
    zip unzip git libonig-dev libicu-dev \
    && docker-php-ext-install pdo_mysql intl mbstring

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Carpeta de trabajo
WORKDIR /app

# Copia todo el proyecto
COPY . .

# Instala dependencias de Symfony
RUN composer install --no-dev --optimize-autoloader

# Expone puerto 10000
EXPOSE 10000

# Arranca Symfony
CMD ["php", "-S", "0.0.0.0:10000", "-t", "symfony/public"]