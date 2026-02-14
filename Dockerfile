FROM php:8.2-cli

# Extensiones necesarias para Symfony
RUN apt-get update && apt-get install -y \
    zip unzip git libonig-dev libicu-dev \
    && docker-php-ext-install pdo_mysql intl mbstring

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Carpeta de trabajo
WORKDIR /app

# Copia todos los archivos del backend
COPY symfony/ .

# Instala dependencias de Symfony ignorando ext-http
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-http --no-scripts

# Expone puerto
EXPOSE 10000

# Servidor PHP integrado apuntando a public
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]