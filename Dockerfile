# Usa PHP con extensiones necesarias
FROM php:8.2-cli

# Instala extensiones y herramientas necesarias
RUN apt-get update && apt-get install -y zip unzip libonig-dev git
RUN docker-php-ext-install pdo_mysql

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Carpeta de trabajo
WORKDIR /app

# Copia todo el proyecto
COPY . .

# Instala dependencias de Symfony
RUN composer install --no-dev --optimize-autoloader

# Expone puerto para el servidor PHP
EXPOSE 10000

# Comando para arrancar Symfony
CMD ["php", "-S", "0.0.0.0:10000", "-t", "symfony/public"]